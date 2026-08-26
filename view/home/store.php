<?php
require_once(__DIR__ . "/../../controller/homeController.php");
$obj = new homeController();

$correo = $_POST['correo'];
$contraseña = $_POST['contraseña'];
$confirmarContraseña = $_POST['confirmarContraseña'];
$nombre_usuario = $_POST['nombre_usuario'] ?? '';
$error = "";

// Validar campos
if(empty($correo) || empty($contraseña) || empty($confirmarContraseña) || empty($nombre_usuario)){
    $error .= "<li>Completa todos los campos</li>";
    header("Location:signup.php?error=".$error."&&correo=".$correo."&&contraseña=".$contraseña."&&confirmarContraseña=".$confirmarContraseña."&&nombre_usuario=".$nombre_usuario);
    exit;
}

// Contraseñas coinciden
if($contraseña !== $confirmarContraseña){
    $error .= "<li>Las contraseñas son diferentes</li>";
    header("Location:signup.php?error=".$error."&&correo=".$correo."&&contraseña=".$contraseña."&&confirmarContraseña=".$confirmarContraseña."&&nombre_usuario=".$nombre_usuario);
    exit;
}

// Guardar usuario
if($obj->guardarUsuario($correo, $contraseña, $nombre_usuario) == false){
    $error .= "<li>El correo ya está registrado</li>";
    header("Location:signup.php?error=".$error."&&correo=".$correo."&&contraseña=".$contraseña."&&confirmarContraseña=".$confirmarContraseña."&&nombre_usuario=".$nombre_usuario);
    exit;
}

// Éxito
header("Location: /index.php");
exit;
?>