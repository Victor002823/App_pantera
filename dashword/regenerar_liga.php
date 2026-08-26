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
require_once __DIR__ . '/../model/RastreoModel.php';

try {
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();

    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID de link inválido.');
    }

    // Verificar que el link exista y que el usuario tenga permiso sobre él
    $stmt = $pdo->prepare("SELECT * FROM rastreo_links WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $liga = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$liga) {
        throw new Exception('El link no existe.');
    }

    $rol = $_SESSION['rol'] ?? 'usuario';
    $nombreUsuario = $_SESSION['usuario']['nombre_usuario'] ?? null;

    $esDueño = ($liga['creado_por'] ?? null) === $nombreUsuario;
    if ($rol !== 'admin' && !$esDueño) {
        http_response_code(403);
        throw new Exception('No tienes permiso para regenerar este link.');
    }

    $rastreoModel = new RastreoModel($pdo);
    $resultado = $rastreoModel->regenerarLiga($id, 48);

    echo json_encode([
        'success' => true,
        'expira_en' => $resultado['expira_en']
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
