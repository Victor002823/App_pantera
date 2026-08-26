<?php
header('Content-Type: application/json');

$host = "Localhost";
$db   = "fletehxn_login";
$user = "fletehxn_login";
$pass = "L64tk6MaDqvusRRsp2DW";
$charset = 'utf8mb4';

// Conexión usando mysqli
$conn = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $conn->connect_error]);
    exit;
}

// Recibir ID
$id = $_POST['id'] ?? '';

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    exit;
}

if (!is_numeric($id)) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

// Preparar y ejecutar DELETE
$stmt = $conn->prepare("DELETE FROM servicios WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se encontró el registro con ese ID']);
    }
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>