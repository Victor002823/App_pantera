<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://control.mudanzasellince.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require_once(__DIR__ . "/../../config/db.php");

$CLAVE_SECRETA = getenv('LINCE_TRANSFER_KEY') ?: '';

$input = json_decode(file_get_contents('php://input'), true);
$claveRecibida = $input['clave'] ?? '';

if (!hash_equals($CLAVE_SECRETA, $claveRecibida)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$s = $input['data'] ?? null;
if (!$s || empty($s['nombre_cliente'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();
    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

    $stmt = $pdo->prepare("
        INSERT INTO servicios
        (nombre_cliente, telefono, correo, tipo_servicio, inmueble, destino, direccion_origen, direccion_destino, tipo_camioneta, inventario, cargadores, maniobra, total)
        VALUES
        (:nombre_cliente, :telefono, :correo, :tipo_servicio, :inmueble, :destino, :direccion_origen, :direccion_destino, :tipo_camioneta, :inventario, :cargadores, :maniobra, :total)
    ");
    $stmt->execute([
        ':nombre_cliente' => $s['nombre_cliente'] ?? '',
        ':telefono' => $s['telefono'] ?? null,
        ':correo' => $s['correo'] ?? null,
        ':tipo_servicio' => $s['tipo_servicio'] ?? '',
        ':inmueble' => $s['inmueble'] ?? null,
        ':destino' => $s['destino'] ?? null,
        ':direccion_origen' => $s['direccion_origen'] ?? '',
        ':direccion_destino' => $s['direccion_destino'] ?? '',
        ':tipo_camioneta' => $s['tipo_camioneta'] ?? '',
        ':inventario' => $s['inventario'] ?? null,
        ':cargadores' => $s['cargadores'] ?? 0,
        ':maniobra' => $s['maniobra'] ?? 0,
        ':total' => $s['total'] ?? 0,
    ]);

    $nuevoId = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'nuevo_id' => $nuevoId]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
