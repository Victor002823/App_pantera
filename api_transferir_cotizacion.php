<?php
header('Content-Type: application/json');

$CLAVE_SECRETA = '600babb4f567ea354226e024c19748ccf892badf3c51f064f85c0145da0fa292';

$claveRecibida = $_GET['clave'] ?? '';
if (!hash_equals($CLAVE_SECRETA, $claveRecibida)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$id = $_GET['id'] ?? '';
if (!$id || !is_numeric($id)) {
    echo json_encode(['success' => false, 'error' => 'ID invalido']);
    exit;
}

$host = "Localhost";
$db   = "fletehxn_login";
$user = "fletehxn_login";
$pass = "L64tk6MaDqvusRRsp2DW";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $stmt = $pdo->prepare("SELECT * FROM servicios WHERE id = ?");
    $stmt->execute([$id]);
    $servicio = $stmt->fetch();

    if (!$servicio) {
        echo json_encode(['success' => false, 'error' => 'Cotizacion no encontrada']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $servicio]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
