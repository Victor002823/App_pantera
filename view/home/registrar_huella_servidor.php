<?php
// Puedes incluir aquí tu archivo de conexión o llamar a tu controlador
require_once("../../controller/homeController.php");

// Recibimos los datos por POST tradicional (application/x-www-form-urlencoded) que envía Android
$correo = $_POST['correo'] ?? '';
$clave_publica = $_POST['clave_publica'] ?? '';

if (!empty($correo) && !empty($clave_publica)) {
    
    // Instancias tu controlador para actualizar el campo en la BD
    $obj = new homeController();
    
    // Aquí adentro deberías hacer un: UPDATE usuarios SET clave_publica = :clave WHERE correo = :correo
    $actualizado = $obj->guardarClavePublicaUsuario($correo, $clave_publica);

    if ($actualizado) {
        http_response_code(200);
        echo json_encode(["status" => "OK", "message" => "Huella vinculada en servidor"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "ERROR", "message" => "No se pudo actualizar la BD"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "ERROR", "message" => "Datos incompletos"]);
}
?>
