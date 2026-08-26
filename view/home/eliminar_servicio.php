<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../../config/db.php");

$conexionDB = new db();
$pdo = $conexionDB->conexion();

if (is_string($pdo)) {
    echo json_encode(['success' => false, 'error' => $pdo]);
    exit;
}

$id = $_POST['id'] ?? '';

if (!$id || !is_numeric($id)) {
    echo json_encode(['success' => false, 'error' => 'ID inválido o no proporcionado']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM servicios WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se encontró el registro con ese ID']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
