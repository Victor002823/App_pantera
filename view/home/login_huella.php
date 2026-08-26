<?php
session_start();
require_once("../../controller/homeController.php");

$obj = new homeController();

$input = json_decode(file_get_contents('php://input'), true);
$correo = $input['correo'] ?? '';

$usuario = $obj->obtenerUsuarioPorCorreo($correo);

if ($usuario) {

    $_SESSION['usuario'] = [
        'correo' => $usuario['correo'],
        'nombre_usuario' => $usuario['nombre_usuario'] ?? 'Asesor'
    ];

    // 🔥 AQUÍ ESTABA EL ERROR
    $_SESSION['rol'] = $usuario['rol'] ?? 'usuario';

    $_SESSION['last_activity'] = time();

    echo json_encode([
        'success' => true,
        'rol' => $_SESSION['rol']
    ]);

} else {

    echo json_encode([
        'success' => false,
        'message' => 'Usuario no encontrado'
    ]);
}