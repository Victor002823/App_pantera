<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Verificar conexión a base de datos de forma segura
    $dbConfigPath = __DIR__ . '/../../config/db.php';
    if (!file_exists($dbConfigPath)) {
        throw new Exception("No se encontró el archivo db.php en la ruta: " . $dbConfigPath);
    }
    include $dbConfigPath;

    // 2. Carga opcional y segura de RastreoModel
    $rastreoPath = __DIR__ . '/../../model/RastreoModel.php';
    if (file_exists($rastreoPath)) {
        require_once $rastreoPath;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    error_log("SESSION USUARIO: " . print_r($_SESSION['usuario'], true));
    $nombre = $_SESSION['usuario']['nombre_usuario'] ?? 'Asesor';

    // Función de Shipday encapsulada
    function dispararAShipday($pdo, $servicio_id, $clienteNombre) {
        $stmtS = $pdo->prepare("SELECT * FROM servicios WHERE id = ?");
        $stmtS->execute([$servicio_id]);
        $servicio = $stmtS->fetch(PDO::FETCH_ASSOC);

        if (!$servicio) {
            return ['success' => false, 'error' => 'Servicio ID ' . $servicio_id . ' no encontrado en la BD'];
        }

        $authHeader = "Basic " . (getenv("SHIPDAY_API_KEY") ?: "CAMBIAR_SHIPDAY_KEY");
        $url = "https://api.shipday.com/orders";

        // CDMX no tiene horario de verano desde 2022, es fijo UTC-6 todo el año.
        // Shipday NO convierte zonas horarias: toma el string tal cual y lo
        // guarda como si fuera UTC. Por eso hay que convertir nosotros mismos
        // de hora CDMX a UTC antes de mandarla, o el ETA le llega desfasado
        // 6 horas al cliente.
        $fechaServicio = $servicio['fecha_servicio'] ?? date('Y-m-d');
        $horaServicio = $servicio['hora_servicio'] ?? '00:00:00';

        try {
            $zonaCDMX = new DateTimeZone('America/Mexico_City');
            $zonaUTC = new DateTimeZone('UTC');
            $fechaHoraLocal = new DateTime("{$fechaServicio} {$horaServicio}", $zonaCDMX);
            $fechaHoraLocal->setTimezone($zonaUTC);

            $expectedDeliveryDateUTC = $fechaHoraLocal->format('Y-m-d');
            $expectedDeliveryTimeUTC = $fechaHoraLocal->format('H:i:s');
        } catch (Exception $eTz) {
            // Si algo falla en la conversion, se manda tal cual como respaldo
            // (mejor una hora posiblemente desfasada que tronar la creacion de la orden)
            $expectedDeliveryDateUTC = $fechaServicio;
            $expectedDeliveryTimeUTC = $horaServicio;
        }

        $payload = json_encode([
            "orderNumber" => "SERV-" . $servicio_id,
            "orderType" => "Delivery",
            "customerName" => $clienteNombre,
            "customerAddress" => $servicio['direccion_destino'] ?? 'Sin destino',
            "customerPhoneNumber" => $servicio['telefono'] ?? "0000000000",
            "restaurantName" => $clienteNombre,
            "restaurantAddress" => $servicio['direccion_origen'] ?? 'Sin origen',
            "restaurantPhoneNumber" => $servicio['telefono'] ?? "0000000000",
            "expectedDeliveryDate" => $expectedDeliveryDateUTC,
            "expectedPickupTime" => $expectedDeliveryTimeUTC,
            "expectedDeliveryTime" => $expectedDeliveryTimeUTC,
            "orderNotes" => "ORIGEN: " . ($servicio['direccion_origen'] ?? '') . " | DESTINO: " . ($servicio['direccion_destino'] ?? '')
        ]);

        error_log("PAYLOAD SHIPDAY: " . $payload);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => [
                "Authorization: $authHeader",
                "Content-Type: application/json"
            ]
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        error_log("SHIPDAY RESPUESTA [HTTP $httpCode]: " . $result . " | cURL error: " . $curlError);

        if ($result === false) {
            return ['success' => false, 'error' => "Error cURL Shipday: $curlError"];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return ['success' => false, 'error' => "Shipday respondió Código $httpCode: $result"];
        }

        $decoded = json_decode($result, true);
        return [
            'success' => true,
            'shipday_order_id' => $decoded['orderId'] ?? null,
            'telefono' => $servicio['telefono'] ?? null
        ];
    }

    // 3. Obtener y validar el JSON de entrada
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    error_log("PAYLOAD RECIBIDO: " . $rawInput);

    if (!is_array($input)) {
        throw new Exception("El payload recibido no es un JSON válido. Recibido: " . substr($rawInput, 0, 100));
    }

    $carrito = $input['carrito'] ?? null;
    if (!is_array($carrito) || empty($carrito)) {
        throw new Exception("El carrito está vacío o no tiene el formato correcto.");
    }

    $servicio_id = $input['servicio_id'] ?? null;
    $fecha_servicio = $input['fecha_servicio'] ?? date('Y-m-d');
    $hora_servicio = $input['hora_servicio'] ?? '00:00:00';
    $anticipo = $input['anticipo'] ?? 0;

    // 4. Conectar a Base de Datos
    $db = new db();
    $pdo = $db->conexion();
    if (!$pdo) {
        throw new Exception("No se pudo establecer la conexión PDO con la base de datos.");
    }

    // 5. Deduplicar carrito
    $carritoUnico = [];
    foreach ($carrito as $item) {
        $producto = $item['producto'] ?? 'Producto';
        $cliente = $item['cliente'] ?? 'Cliente';
        $clave = $cliente . '||' . $producto;

        if (!isset($carritoUnico[$clave])) {
            $carritoUnico[$clave] = [
                'cliente'  => $cliente,
                'producto' => $producto,
                'cantidad' => 0,
                'total'    => 0
            ];
        }

        $carritoUnico[$clave]['cantidad'] += (float)($item['cantidad'] ?? 1);
        $carritoUnico[$clave]['total']    += (float)($item['total'] ?? 0);
    }
    $carritoUnico = array_values($carritoUnico);

    // 6. Transacción y Guardado en BD
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO facturaciones
        (
            fecha,
            cliente,
            producto,
            cantidad,
            total,
            asesor,
            fecha_servicio,
            hora_servicio,
            anticipo,
            servicio_id
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
        ON DUPLICATE KEY UPDATE
            cantidad        = VALUES(cantidad),
            total           = VALUES(total),
            fecha           = VALUES(fecha),
            cliente         = VALUES(cliente),
            asesor          = VALUES(asesor),
            fecha_servicio  = VALUES(fecha_servicio),
            hora_servicio   = VALUES(hora_servicio),
            anticipo        = VALUES(anticipo)
    ");

    foreach ($carritoUnico as $item) {
        $stmt->execute([
            date('Y-m-d'),
            $item['cliente'],
            $item['producto'],
            $item['cantidad'],
            $item['total'],
            $nombre,
            $fecha_servicio,
            $hora_servicio,
            $anticipo,
            $servicio_id
        ]);
    }

    $pdo->commit();

    // 7. Disparar Shipday y Rastreo
    $ligaRastreo = null;
    $telefonoCliente = null;

    if ($servicio_id) {
        $clienteNombre = $carritoUnico[0]['cliente'] ?? 'Cliente';
        $respuestaShipday = dispararAShipday($pdo, $servicio_id, $clienteNombre);

        if (!$respuestaShipday['success']) {
            error_log("Fallo Shipday servicio " . $servicio_id . ": " . $respuestaShipday['error']);
        } elseif (!empty($respuestaShipday['shipday_order_id'])) {
            error_log("INTENTANDO CREAR LIGA: order_id=" . $respuestaShipday['shipday_order_id'] . " servicio_id=" . $servicio_id . " cliente=" . $clienteNombre);
            if (class_exists('RastreoModel')) {
                try {
                    $rastreoModel = new RastreoModel($pdo);
                    $liga = $rastreoModel->crearLiga(
                        $respuestaShipday['shipday_order_id'],
                        $servicio_id,
                        $clienteNombre,
                        $_SESSION['usuario']['nombre_usuario'] ?? null
                    );
                    $ligaRastreo = $liga['url'] ?? null;
                    error_log("LIGA CREADA OK: " . ($ligaRastreo ?? 'NULL'));
                } catch (Throwable $exModel) {
                    error_log("Error al generar liga de rastreo: " . $exModel->getMessage() . " | archivo: " . $exModel->getFile() . " línea: " . $exModel->getLine());
                }
            } else {
                error_log("RastreoModel NO existe / no se pudo cargar la clase");
            }
            $telefonoCliente = $respuestaShipday['telefono'] ?? null;
        } else {
            error_log("Shipday respondió success pero sin shipday_order_id: " . print_r($respuestaShipday, true));
        }
    } else {
        error_log("No se intentó Shipday/Rastreo porque servicio_id vino vacío");
    }

    error_log("RESPUESTA FINAL AL FRONTEND: liga=" . ($ligaRastreo ?? 'NULL') . " telefono=" . ($telefonoCliente ?? 'NULL'));

    echo json_encode([
        "success" => true,
        "liga_rastreo" => $ligaRastreo,
        "telefono_cliente" => $telefonoCliente
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("ERROR GENERAL EN EL ENDPOINT: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());

    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "success" => false,
        "error" => "Error en línea " . $e->getLine() . ": " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
