<?php
/**
 * sync_shipday_eliminados.php
 * ---------------------------------------------------------------
 * Sustituto de webhook (ORDER_DELETE) para cuentas de Shipday SIN
 * plan de pago. Se ejecuta por CRON cada 5-10 minutos.
 *
 * Qué hace:
 *  1. Toma todos los rastreo_links con estado = 'activo' (con su
 *     shipday_order_id guardado).
 *  2. Le pregunta a la API de Shipday por orderNumber
 *     (GET /orders/SERV-{servicio_id}, el mismo que arma
 *     guardar_facturacion.php al crear la orden).
 *  3. OJO: orderNumber NO es único en Shipday — puede haber varias
 *     órdenes viejas con el mismo "SERV-{id}" (de pruebas o
 *     refacturaciones). Por eso no basta con ver si el array viene
 *     vacío: hay que confirmar que el orderId ESPECÍFICO que
 *     tenemos guardado en rastreo_links.shipday_order_id sigue
 *     apareciendo dentro del array de resultados.
 *  4. Si ese orderId ya no aparece (array vacío, o presente pero sin
 *     ese id), se BORRA ese registro de rastreo_links.
 *
 * IMPORTANTE: solo se borra el registro cuando la respuesta de
 * Shipday fue exitosa y se pudo decodificar. Cualquier error de red,
 * timeout, o respuesta no decodificable se registra en el log pero
 * NO se toca el registro, para evitar borrar por error algo que
 * sigue activo por una falla temporal.
 *
 * Colócalo en la MISMA carpeta que guardar_facturacion.php para
 * que la ruta relativa a config/db.php funcione igual.
 *
 * EJECUCIÓN:
 *  - Por cron (CLI): no requiere token, corre libre.
 *      php /ruta/.../sync_shipday_eliminados.php
 *  - Por HTTP/curl (si no tienes SSH/cron con CLI): requiere el
 *    parámetro ?token=79aa959cf3122b274de9206eb26505c647e2a20d3e7aa994
 *    que coincida con el definido abajo, o responde 403 sin hacer nada.
 *      curl -s "https://app-pantera.onrender.com/view/home/sync_shipday_eliminados.php?token=79aa959cf3122b274de9206eb26505c647e2a20d3e7aa994"
 *
 * *** Si por seguridad prefieres cambiar el token, edita la constante
 * *** SYNC_SHIPDAY_TOKEN más abajo por tu propio valor aleatorio.
 * ---------------------------------------------------------------
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Token para poder disparar el script por HTTP/curl (cron sin SSH).
// CAMBIA este valor por algo largo y aleatorio propio antes de subirlo.
define('SYNC_SHIPDAY_TOKEN', '79aa959cf3122b274de9206eb26505c647e2a20d3e7aa994');

$esPeticionWeb = php_sapi_name() !== 'cli';
if ($esPeticionWeb) {
    header('Content-Type: text/plain; charset=utf-8');
    $tokenRecibido = $_GET['token'] ?? '';
    if (!hash_equals(SYNC_SHIPDAY_TOKEN, $tokenRecibido)) {
        http_response_code(403);
        echo "Acceso denegado.\n";
        exit;
    }
}

function logSync($mensaje) {
    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje . PHP_EOL;
    echo $linea;
    error_log($linea, 3, __DIR__ . '/sync_shipday_eliminados.log');
}

try {
    // 1. Conexión a base de datos (misma ruta que usa guardar_facturacion.php)
    $dbConfigPath = __DIR__ . '/../../config/db.php';
    if (!file_exists($dbConfigPath)) {
        throw new Exception("No se encontró db.php en: " . $dbConfigPath);
    }
    include $dbConfigPath;

    $db = new db();
    $pdo = $db->conexion();
    if (!$pdo) {
        throw new Exception("No se pudo conectar a la base de datos.");
    }

    // Misma credencial que usa guardar_facturacion.php para crear órdenes
    $authHeader = "Basic " . (getenv("SHIPDAY_API_KEY") ?: "CAMBIAR_SHIPDAY_KEY");

    // 2. Traer los rastreos activos con su shipday_order_id (lo necesitamos
    //    para identificar CUÁL orden específica estamos verificando, ya que
    //    orderNumber puede repetirse entre varias órdenes en Shipday)
    $stmt = $pdo->prepare("
        SELECT id, servicio_id, shipday_order_id, cliente_nombre
        FROM rastreo_links
        WHERE estado = 'activo'
          AND servicio_id IS NOT NULL
          AND shipday_order_id IS NOT NULL
    ");
    $stmt->execute();
    $rastreos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rastreos)) {
        logSync("No hay rastreos activos que verificar.");
        exit;
    }

    logSync("Verificando " . count($rastreos) . " rastreo(s) activo(s) contra Shipday...");

    $stmtBorrar = $pdo->prepare("
        DELETE FROM rastreo_links
        WHERE id = ?
    ");

    $totalEliminados = 0;

    foreach ($rastreos as $r) {
        $orderNumber = "SERV-" . $r['servicio_id'];

        $ch = curl_init("https://api.shipday.com/orders/" . urlencode($orderNumber));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                "Authorization: $authHeader",
                "Content-Type: application/json"
            ]
        ]);

        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($respuesta === false) {
            logSync("  [rastreo_links.id={$r['id']}] Error cURL, se omite este ciclo: $curlError");
            continue;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            logSync("  [rastreo_links.id={$r['id']}] Shipday respondió código inesperado {$httpCode} para {$orderNumber}. Se omite este ciclo.");
            continue;
        }

        $decoded = json_decode($respuesta, true);

        if (!is_array($decoded)) {
            logSync("  [rastreo_links.id={$r['id']}] Respuesta no decodificable para {$orderNumber}, se omite este ciclo: " . substr($respuesta, 0, 200));
            continue;
        }

        if (empty($decoded)) {
            // Array vacío [] = no hay NINGUNA orden con ese orderNumber -> se eliminó allá
            $stmtBorrar->execute([$r['id']]);
            $totalEliminados++;
            logSync("  [rastreo_links.id={$r['id']}] servicio_id={$r['servicio_id']} cliente='{$r['cliente_nombre']}' " .
                     "-> {$orderNumber} ya no existe en Shipday (array vacío). Registro BORRADO de rastreo_links.");
            continue;
        }

        // IMPORTANTE: orderNumber NO es único en Shipday. Puede haber varias
        // órdenes distintas (de intentos/pruebas anteriores) con el mismo
        // "SERV-{servicio_id}". Un array no vacío no basta: hay que
        // confirmar que el orderId ESPECÍFICO que tenemos guardado sigue
        // apareciendo entre los resultados devueltos.
        $idBuscado = (string) $r['shipday_order_id'];
        $siguExiste = false;
        foreach ($decoded as $ordenShipday) {
            if (isset($ordenShipday['orderId']) && (string) $ordenShipday['orderId'] === $idBuscado) {
                $siguExiste = true;
                break;
            }
        }

        if (!$siguExiste) {
            $stmtBorrar->execute([$r['id']]);
            $totalEliminados++;
            logSync("  [rastreo_links.id={$r['id']}] servicio_id={$r['servicio_id']} cliente='{$r['cliente_nombre']}' " .
                     "-> orderId {$idBuscado} ({$orderNumber}) ya no aparece entre las órdenes activas " .
                     "(hay otra(s) orden(es) con el mismo orderNumber, pero no esta). Registro BORRADO de rastreo_links.");
            continue;
        }

        // El orderId específico sigue apareciendo: la orden sigue activa, no se toca
    }

    logSync("Sincronización terminada. Total registros borrados: {$totalEliminados}.");

} catch (Throwable $e) {
    logSync("ERROR: " . $e->getMessage() . " (línea " . $e->getLine() . ")");
}
