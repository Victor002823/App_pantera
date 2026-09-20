<?php
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/rastreo_conexion.php';
require_once __DIR__ . '/model/RastreoModel.php';

$token = $_GET['token'] ?? '';
if (!$token || !preg_match('/^[a-f0-9]{32}$/', $token)) {
    http_response_code(400);
    echo json_encode(['error' => 'Token inválido']);
    exit;
}

$model = new RastreoModel($pdo);
$liga = $model->obtenerLigaPorToken($token);

if (!$liga) {
    http_response_code(404);
    echo json_encode(['error' => 'Liga no encontrada']);
    exit;
}
if (method_exists($model, 'esLigaValida') && !$model->esLigaValida($liga)) {
    http_response_code(410);
    echo json_encode(['error' => 'expirado', 'estado' => $liga['estado']]);
    exit;
}

// Atajo: si el servicio ya cerro (completado/fallido) y ya tenemos una
// foto guardada de los datos finales, la servimos directo sin volver a
// llamar a la API de Shipday. Esto evita pegarle al rate limit cada vez
// que alguien reabre una liga de un servicio ya terminado.
if (in_array($liga['estado'], ['completado', 'fallido'], true)) {
    $snapshot = $model->obtenerSnapshot($liga);
    if ($snapshot) {
        echo json_encode([
            'estado_liga' => $liga['estado'],
            'terminado' => true,
            'mensaje_cierre' => 'El viaje ha finalizado y la sesión ha expirado.',
            'orden' => $snapshot['orden'] ?? null,
            'chofer' => $snapshot['chofer'] ?? null,
        ]);
        exit;
    }
    // Si no hay snapshot aun (primera vez que se detecta el cierre),
    // sigue el flujo normal de abajo para generarlo.
}

$orden = $model->obtenerOrden((int)$liga['shipday_order_id']);
if (!$orden) {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo consultar Shipday']);
    exit;
}

$evaluacion = $model->evaluarEstadoOrden($orden);
if ($evaluacion['terminado']) {
    try {
        $model->marcarEstado((int)$liga['id'], $evaluacion['estado']);
    } catch (Exception $e) {
        // Silenciar error en base de datos al finalizar
    }
}

// La ubicacion del chofer solo se comparte si realmente ACEPTO la orden
// (Shipday reporta esto en orderStatus.accepted). Mientras este pendiente
// de aceptar, o si la rechaza y vuelve a NOT_ASSIGNED, no se muestra
// ningun chofer - sin rescatar datos previos de la BD.
$ordenAceptada = $orden['orderStatus']['accepted'] ?? false;

$carrierId = null;
if ($ordenAceptada) {
    $carrierId = $orden['assignedCarrierId']
             ?? $orden['driverId']
             ?? $orden['carrierId']
             ?? ($orden['carrier']['id'] ?? null)
             ?? ($orden['driver']['id'] ?? null)
             ?? null;

    if (empty($carrierId) || $carrierId == 0 || $carrierId == '0') {
        $carrierId = null;
    }

    if ($carrierId && $carrierId != $liga['shipday_carrier_id']) {
        try {
            $stmtUpdate = $pdo->prepare("UPDATE rastreo_links SET shipday_carrier_id = ? WHERE id = ?");
            $stmtUpdate->execute([(int)$carrierId, (int)$liga['id']]);
            $liga['shipday_carrier_id'] = (int)$carrierId;
        } catch (Exception $e) {
            // Silenciar error
        }
    }
} elseif (!empty($liga['shipday_carrier_id'])) {
    // No aceptada (pendiente o rechazada): limpiar cualquier chofer viejo
    // guardado, para que no se siga mostrando por error.
    try {
        $stmtLimpiar = $pdo->prepare("UPDATE rastreo_links SET shipday_carrier_id = NULL WHERE id = ?");
        $stmtLimpiar->execute([(int)$liga['id']]);
        $liga['shipday_carrier_id'] = null;
    } catch (Exception $e) {
        // Silenciar error
    }
}

$carrier = null;
if ($carrierId) {
    $carrier = $model->obtenerCarrier((int)$carrierId);
}

$ordenSalida = [
    'numero' => $orden['orderNumber'] ?? null,
    'estadoAdmin' => $orden['orderStatusAdmin'] ?? null,
    'etaTime' => $orden['expectedDeliveryTime']
              ?? ($orden['activityLog']['expectedDeliveryTime'] ?? null)
              ?? $orden['eta']
              ?? null,
    'origen' => [
        'nombre' => $orden['restaurant']['name'] ?? null,
        'direccion' => $orden['restaurant']['address'] ?? null,
        'lat' => $orden['restaurant']['latitude'] ?? null,
        'lng' => $orden['restaurant']['longitude'] ?? null,
    ],
    'destino' => [
        'nombre' => $orden['customer']['name'] ?? null,
        'direccion' => $orden['customer']['address'] ?? null,
        'lat' => $orden['customer']['latitude'] ?? null,
        'lng' => $orden['customer']['longitude'] ?? null,
    ],
    'activityLog' => $orden['activityLog'] ?? null,
];

$choferSalida = $carrier ? [
    'nombre' => $carrier['name'] ?? null,
    'telefono' => $carrier['phoneNumber'] ?? null,
    'foto' => $carrier['carrierPhoto'] ?? null,
    'lat' => $carrier['carrrierLocationLat'] ?? null,
    'lng' => $carrier['carrrierLocationLng'] ?? null,
    'enTurno' => $carrier['isOnShift'] ?? null,
] : null;

// Si el servicio acaba de cerrar en esta misma consulta, guardamos la
// foto final para que las proximas visitas no vuelvan a tocar Shipday.
if ($evaluacion['terminado']) {
    try {
        $model->guardarSnapshot((int)$liga['id'], [
            'orden' => $ordenSalida,
            'chofer' => $choferSalida,
        ]);
    } catch (Exception $e) {
        // Silenciar error
    }
}

echo json_encode([
    'estado_liga' => $evaluacion['estado'],
    'terminado' => $evaluacion['terminado'],
    'mensaje_cierre' => $evaluacion['terminado']
        ? (!empty($evaluacion['cierre_forzado'])
            ? 'El viaje se cerró automáticamente al no recibir confirmación de entrega.'
            : 'El viaje ha finalizado y la sesión ha expirado.')
        : null,
    'orden' => $ordenSalida,
    'chofer' => $choferSalida,
]);
