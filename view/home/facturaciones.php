<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

$host = "Localhost";
$db   = "fletehxn_login";
$user = "fletehxn_login";
$pass = "L64tk6MaDqvusRRsp2DW";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$id_servicio = $_POST['id'] ?? null;

if(!$id_servicio) {
    echo json_encode(['success' => false, 'error' => 'Falta id del servicio']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Insertar en facturas copiando de servicios
    $sql = "
        INSERT INTO facturas (servicio_id, cliente, producto, cargadores, cantidad, precio, fecha_transaccion)
        SELECT 
            s.id,
            s.nombre_cliente,
            CONCAT(s.direccion_origen, ' -> ', s.direccion_destino),
            s.cargadores,
            1,
            s.total,
            NOW()
        FROM servicios s
        WHERE s.id = :id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_servicio]);

    // Marcar como facturado en servicios
    $sqlUpdate = "UPDATE servicios SET facturado = 1 WHERE id = :id";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([':id' => $id_servicio]);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
