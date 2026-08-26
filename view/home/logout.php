<?php
session_start();

// Limpia todas las variables de sesión
$_SESSION = array();

// Destruye la sesión
session_destroy();

// Redirige al login
header("Location: /index.php");
exit;
?>