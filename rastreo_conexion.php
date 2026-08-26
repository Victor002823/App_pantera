<?php
require_once __DIR__ . '/config/db.php';

$pdo = null;
try {
    $pdo = (new db())->conexion();
} catch (Exception $e) {
    error_log('Error de conexión a la base de datos: ' . $e->getMessage());
    $pdo = null;
}

if (!($pdo instanceof PDO)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Fallo de conexión a la base de datos']);
    exit;
}
