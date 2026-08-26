<?php
header('Content-Type: application/json');

$host = "Localhost";
$db   = "fletehxn_login";
$user = "fletehxn_login";
$pass = "L64tk6MaDqvusRRsp2DW";
$charset = 'utf8mb4';


$correo = $_GET['correo'] ?? '';
if(!$correo){
    echo json_encode(['credentialId' => null, 'nombre_usuario' => null]);
    exit;
}

$conexion = new mysqli($host, $user, $pass, $db);
if($conexion->connect_error){
    echo json_encode(['credentialId' => null, 'nombre_usuario' => null]);
    exit;
}
$conexion->set_charset($charset);

// Obtener huella
$stmt = $conexion->prepare("SELECT u.nombre_usuario, h.credentialId 
    FROM usuarios u 
    LEFT JOIN usuarios_huella h ON u.correo = h.correo 
    WHERE u.correo=? LIMIT 1");
$stmt->bind_param("s", $correo);
$stmt->execute();
$stmt->bind_result($nombre_usuario, $credentialId);
$stmt->fetch();
$stmt->close();
$conexion->close();

echo json_encode([
    'credentialId' => $credentialId ?? null,
    'nombre_usuario' => $nombre_usuario ?? null
]);