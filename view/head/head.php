<?php 
if (session_status() === PHP_SESSION_NONE) {
    // 1. Configurar las 10 horas en segundos (36,000)
    $tiempo_vida = 36000; 
    $fecha_expiracion = time() + $tiempo_vida;

    // 2. Inyectar la fecha de caducidad física a la cookie
    session_set_cookie_params([
        'lifetime' => $tiempo_vida,
        'path' => '/',
		'domain' => $_SERVER['HTTP_HOST'] ?? '',					  
        'secure' => true,     // Obligatorio para HTTPS
        'httponly' => true,   // Protección contra scripts externos
        'samesite' => 'Lax'
    ]);

    // 3. Forzar al motor de PHP a respetar el tiempo configurado
    ini_set('session.cookie_lifetime', $tiempo_vida);
    ini_set('session.gc_maxlifetime', $tiempo_vida);

    // 4. Iniciar la sesión de forma segura
    session_start();

    // 5. Enviar la cabecera explícita para que el WebView de Android grabe la fecha en el disco duro
    if (session_id()) {
        header("Set-Cookie: PHPSESSID=" . session_id() . "; Expires=" . gmdate('D, d M Y H:i:s', $fecha_expiracion) . " GMT; Path=/; Secure; HttpOnly; SameSite=Lax", false);
    }
} 
?>

<!doctype html>
<html lang="es">
 <head>
<meta charset="utf-8">
<meta name="google" content="notranslate">   
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Control El Lince</title>

<!-- Manifest ÚNICO -->
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#000000">

<!-- CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/asset/css/style.css">
<link rel="stylesheet" href="/view/home/styles_login.css">
<link rel="stylesheet" href="/view/home/panel.css?v=1.0.1">	 
<link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen/Control.FullScreen.css"> 

<!-- DataTables -->
<link href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">    	 

<!-- Scripts -->


<script src="https://kit.fontawesome.com/65ea5e46f1.js" crossorigin="anonymous"></script>
<script src="/view/home/jquery.js"></script>
<script src="/view/home/datatables.js"></script>

  
</head>

  <body>