<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

// 🔐 Sesión
session_start();

if (empty($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Sesión no válida']);
    exit;
}

// 1. Incluimos el archivo de conexión centralizado
require_once(__DIR__ . "/../../config/db.php");

try {
    // 2. Instanciamos la clase y obtenemos el objeto PDO
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();

    // Verificamos si la conexión falló (si devolvió un string de error)
    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

// 🔹 DATOS
$id             = $_POST['id'] ?? '';
$fecha          = $_POST['fecha'] ?? '';
$cliente        = $_POST['cliente'] ?? '';
$fecha_servicio = $_POST['fecha_servicio'] ?? '';
$hora_servicio  = $_POST['hora_servicio'] ?? '';
$producto       = $_POST['producto'] ?? '';
$cantidad       = $_POST['cantidad'] ?? 0;
$anticipo       = $_POST['anticipo'] ?? 0;
$subtotal       = $_POST['subtotal'] ?? 0;
$iva            = $_POST['iva'] ?? 0;
$total          = $_POST['total'] ?? 0;

// 🔐 USUARIO REAL (NO del frontend)
$asesor = $_SESSION['usuario']['nombre_usuario'] ?? '';
$rol    = $_SESSION['rol'] ?? '';

if (!$id || !is_numeric($id)) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

// Guardamos los valores ANTERIORES de fecha/hora antes de actualizar, para
// saber si de verdad cambiaron y así evitar llamadas innecesarias a Shipday.
$fechaHoraAnterior = null;
try {
    $stmtAnterior = $pdo->prepare("SELECT fecha_servicio, hora_servicio, servicio_id FROM facturaciones WHERE id = :id LIMIT 1");
    $stmtAnterior->execute([':id' => $id]);
    $fechaHoraAnterior = $stmtAnterior->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Si falla esta consulta previa, no es crítico, seguimos igual
}

// 🔥 Query segura
$sql = "UPDATE facturaciones SET 
    asesor = :asesor,
    fecha = :fecha,
    cliente = :cliente,
    fecha_servicio = :fecha_servicio,
    hora_servicio = :hora_servicio,
    producto = :producto,
    cantidad = :cantidad,
    anticipo = :anticipo,
    subtotal = :subtotal,
    iva = :iva,
    total = :total
    WHERE id = :id";

// 🔐 Si no es admin, solo puede actualizar lo suyo
if ($rol !== 'admin') {
    $sql .= " AND LOWER(TRIM(asesor)) = LOWER(TRIM(:asesor_check))";
}

$stmt = $pdo->prepare($sql);

$params = [
    ':asesor'         => $asesor,
    ':fecha'          => $fecha,
    ':cliente'        => $cliente,
    ':fecha_servicio' => $fecha_servicio,
    ':hora_servicio'  => $hora_servicio,
    ':producto'       => $producto,
    ':cantidad'       => $cantidad,
    ':anticipo'       => $anticipo,
    ':subtotal'       => $subtotal,
    ':iva'            => $iva,
    ':total'          => $total,
    ':id'             => $id
];

// 🔐 Solo si no es admin, agregamos el parámetro de verificación
if ($rol !== 'admin') {
    $params[':asesor_check'] = $asesor;
}

try {
    $stmt->execute($params);

    // Detectamos si de verdad cambió fecha u hora del servicio
    $cambioFechaHora = $fechaHoraAnterior && (
        $fechaHoraAnterior['fecha_servicio'] !== $fecha_servicio ||
        $fechaHoraAnterior['hora_servicio'] !== $hora_servicio
    );

    // ==========================================
    // Sincronizar fecha/hora de servicio con Shipday (solo esos dos campos)
    // ==========================================
    $shipdaySync = null;

    if ($cambioFechaHora && !empty($fechaHoraAnterior['servicio_id'])) {
        $servicioId = (int)$fechaHoraAnterior['servicio_id'];

        try {
            // Buscar la orden de Shipday ligada a este servicio (la más reciente)
            $stmtLiga = $pdo->prepare("SELECT shipday_order_id FROM rastreo_links WHERE servicio_id = :sid ORDER BY id DESC LIMIT 1");
            $stmtLiga->execute([':sid' => $servicioId]);
            $ligaRow = $stmtLiga->fetch(PDO::FETCH_ASSOC);

            if ($ligaRow && !empty($ligaRow['shipday_order_id'])) {
                $shipdayOrderId = (int)$ligaRow['shipday_order_id'];

                // Necesitamos el servicio completo para reconstruir el payload
                // exactamente como se armo al crear la orden originalmente
                $stmtServ = $pdo->prepare("SELECT * FROM servicios WHERE id = ?");
                $stmtServ->execute([$servicioId]);
                $servicio = $stmtServ->fetch(PDO::FETCH_ASSOC);

                if ($servicio) {
                    // Conversion CDMX -> UTC (Shipday no convierte zonas horarias,
                    // toma el valor tal cual como si fuera UTC)
                    try {
                        $zonaCDMX = new DateTimeZone('America/Mexico_City');
                        $zonaUTC = new DateTimeZone('UTC');
                        $fechaHoraLocal = new DateTime("{$fecha_servicio} {$hora_servicio}", $zonaCDMX);
                        $fechaHoraLocal->setTimezone($zonaUTC);
                        $nuevaFechaUTC = $fechaHoraLocal->format('Y-m-d');
                        $nuevaHoraUTC = $fechaHoraLocal->format('H:i:s');
                    } catch (Exception $eTz) {
                        $nuevaFechaUTC = $fecha_servicio;
                        $nuevaHoraUTC = $hora_servicio;
                    }

                    $shipdayConfigPath = __DIR__ . '/../../config/shipday.php';
                    if (file_exists($shipdayConfigPath)) {
                        $shipdayConfig = require $shipdayConfigPath;

                        // Se reconstruye el payload con los MISMOS datos usados al
                        // crear la orden (dispararAShipday), cambiando unicamente
                        // fecha/hora esperada de entrega.
                        $payloadEdit = json_encode([
                            "orderId" => $shipdayOrderId,
                            "orderNo" => "SERV-" . $servicioId,
                            "customerName" => $cliente ?: ($servicio['cliente'] ?? 'Cliente'),
                            "customerAddress" => $servicio['direccion_destino'] ?? 'Sin destino',
                            "customerEmail" => "",
                            "customerPhoneNumber" => $servicio['telefono'] ?? "0000000000",
                            "restaurantName" => $cliente ?: ($servicio['cliente'] ?? 'Cliente'),
                            "restaurantAddress" => $servicio['direccion_origen'] ?? 'Sin origen',
                            "restaurantPhoneNumber" => $servicio['telefono'] ?? "0000000000",
                            "expectedDeliveryDate" => $nuevaFechaUTC,
                            "expectedPickupTime" => $nuevaHoraUTC,
                            "expectedDeliveryTime" => $nuevaHoraUTC,
                        ]);

                        $ch = curl_init($shipdayConfig['base_url'] . "/order/edit/{$shipdayOrderId}");
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_CUSTOMREQUEST => 'PUT',
                            CURLOPT_POSTFIELDS => $payloadEdit,
                            CURLOPT_CAINFO => __DIR__ . '/../../config/cacert.pem',
                            CURLOPT_HTTPHEADER => [
                                "Authorization: " . $shipdayConfig['auth_header'],
                                "Content-Type: application/json"
                            ]
                        ]);
                        $resultEdit = curl_exec($ch);
                        $httpCodeEdit = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        $shipdaySync = ($httpCodeEdit >= 200 && $httpCodeEdit < 300) ? 'ok' : 'error';
                        if ($shipdaySync === 'error') {
                            error_log("Fallo al editar fecha/hora en Shipday, orden {$shipdayOrderId}: HTTP {$httpCodeEdit} - {$resultEdit}");
                        }
                    }
                }
            }
        } catch (Throwable $eSync) {
            error_log("Error al sincronizar fecha/hora con Shipday: " . $eSync->getMessage());
        }
    }

    // ==========================================
    // Programar notificaciones recurrentes (cada 30 min, 2 horas) si cambió
    // la hora del servicio. TODO este bloque está aislado: si algo falla aquí
    // (BD, formato de fecha, etc.) solo se registra en el log y el flujo
    // normal de la factura sigue devolviendo su respuesta sin verse afectado.
    // ==========================================
    $notifSync = null;

    if ($cambioFechaHora) {
        try {
            // Cancelamos notificaciones pendientes previas de esta factura
            // (por si se vuelve a editar la hora antes de que se disparen)
            $pdo->prepare("DELETE FROM notificaciones_programadas WHERE factura_id = :fid AND enviado = 0")
                ->execute([':fid' => $id]);

            $baseDateTime = new DateTime("{$fecha_servicio} {$hora_servicio}");

            $stmtNotif = $pdo->prepare("INSERT INTO notificaciones_programadas
                (factura_id, servicio_id, titulo, mensaje, programado_para)
                VALUES (:fid, :sid, :titulo, :mensaje, :programado)");

            // 5 disparos: 0, 30, 60, 90 y 120 minutos después de hora_servicio
            for ($i = 0; $i < 5; $i++) {
                $momento = clone $baseDateTime;
                $momento->modify('+' . ($i * 30) . ' minutes');

                $stmtNotif->execute([
                    ':fid'        => $id,
                    ':sid'        => $fechaHoraAnterior['servicio_id'] ?? null,
                    ':titulo'     => "Tomar fotos de Servicio de {$cliente}",
                    ':mensaje'    => "Recordatorio: servicio programado a las {$hora_servicio}",
                    ':programado' => $momento->format('Y-m-d H:i:s')
                ]);
            }

            $notifSync = 'ok';

        } catch (Throwable $eNotif) {
            $notifSync = 'error';
            error_log("Error programando notificaciones para factura {$id}: " . $eNotif->getMessage());
        }
    }

    echo json_encode([
        'success'      => true,
        'shipday_sync' => $shipdaySync,
        'notif_sync'   => $notifSync
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
