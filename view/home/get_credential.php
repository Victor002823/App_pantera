<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../../config/db.php");

$correo = $_GET['correo'] ?? '';
if (!$correo) {
    echo json_encode(['credentialId' => null, 'nombre_usuario' => null]);
    exit;
}

$conexionDB = new db();
$pdo = $conexionDB->conexion();

if (is_string($pdo)) {
    echo json_encode(['credentialId' => null, 'nombre_usuario' => null]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT u.nombre_usuario, h.credentialId
        FROM usuarios u
        LEFT JOIN usuarios_huella h ON u.correo = h.correo
        WHERE u.correo = ? LIMIT 1");
    $stmt->execute([$correo]);
    $row = $stmt->fetch();

    echo json_encode([
        'credentialId' => $row['credentialid'] ?? null,
        'nombre_usuario' => $row['nombre_usuario'] ?? null
    ]);
} catch (PDOException $e) {
    echo json_encode(['credentialId' => null, 'nombre_usuario' => null]);
}
?>
