<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . "/../../config/db.php");

try {
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();

    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

    $stmt = $pdo->query("SELECT * FROM servicios ORDER BY id DESC");
    $data = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
