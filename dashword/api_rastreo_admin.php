<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

try {
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();

    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

    $rol = $_SESSION['rol'] ?? 'usuario';
    $nombreUsuario = $_SESSION['usuario']['nombre_usuario'] ?? null;

    if ($rol === 'admin') {
        // El admin ve todos los enlaces, de cualquier usuario
        $stmt = $pdo->query("SELECT * FROM rastreo_links ORDER BY id DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Cualquier otro rol solo ve lo que él mismo creó
        $stmt = $pdo->prepare("SELECT * FROM rastreo_links WHERE creado_por = :creado_por ORDER BY id DESC");
        $stmt->execute([':creado_por' => $nombreUsuario]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $baseUrl = require __DIR__ . '/../config/base_url.php';
    $baseUrl = rtrim($baseUrl, '/') . '/rastreo.php?token=';
    $limpiarDatos = array_map(function($row) use ($baseUrl) {
        $row['cliente_nombre'] = $row['cliente_nombre'] ?? '';
        $row['link_rastreo'] = $baseUrl . $row['token'];
        return $row;
    }, $data);

    echo json_encode(['success' => true, 'data' => $limpiarDatos], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
