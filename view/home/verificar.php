<?php
ob_start();

// =======================================================
// 🔑 PARÁMETROS DE SESIÓN PERSISTENTE (10 HORAS)
// =======================================================
$tiempo_vida = 36000; // 10 horas en segundos
$fecha_expiracion = time() + $tiempo_vida;

if (session_status() === PHP_SESSION_NONE) {
    // Configurar los parámetros de la cookie antes de iniciar la sesión
    session_set_cookie_params([
        'lifetime' => $tiempo_vida,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => true,     // Servidor cuenta con SSL/HTTPS
        'httponly' => true,   // Protección contra lecturas externas de JS
        'samesite' => 'Lax'
    ]);

    ini_set('session.cookie_lifetime', $tiempo_vida);
    ini_set('session.gc_maxlifetime', $tiempo_vida);

    session_start();
}

// Enviar la cabecera física con fecha de expiración UNIX para el WebView de Android
if (session_id()) {
    header("Set-Cookie: PHPSESSID=" . session_id() . "; Expires=" . gmdate('D, d M Y H:i:s', $fecha_expiracion) . " GMT; Path=/; Secure; HttpOnly; SameSite=Lax", false);
}

require_once(__DIR__ . "/../../controller/homeController.php"); 

$obj = new homeController();

$correo = $obj->limpiarcorreo($_POST['correo'] ?? '');
$contraseña = $obj->limpiarcadena($_POST['contraseña'] ?? '');

// 1. Verificación tradicional de correo y contraseña
$bandera = $obj->verificarusuario($correo, $contraseña);

if ($bandera) {

    $usuario = $obj->obtenerUsuarioPorCorreo($correo);

    if (!$usuario) {
        header("Location: /index.php?error=usuario_no_encontrado");
        exit;
    }

    // 2. Comprobar si el usuario cuenta con registro biométrico activo
    // Usamos el método que mapeamos en el homeController
    $huella = $obj->obtenerHuella($correo);

    if ($huella && !empty($huella['credentialId'])) {
        // 🔒 LOCKOUT BIOMÉTRICO: Tiene huella, congelamos el acceso directo
        // Guardamos los datos de forma temporal para que la pantalla de validación sepa quién es
        $_SESSION['intento_biometrico'] = [
            'correo' => $usuario['correo'],
            'nombre_usuario' => $usuario['nombre_usuario'] ?? 'Asesor',
            'rol' => $usuario['rol'] ?? 'usuario'
        ];
        
        // Redirigimos a la pantalla encargada de despertar el lector de huellas o la APK
        header("Location: /view/home/validar_biometrico.php");
        exit;
    } else {
        // 🔓 ACCESO TRADICIONAL DIRECTO: No tiene huella configurada aún, entra normal
        $_SESSION['usuario'] = [
            'correo' => $usuario['correo'],
            'nombre_usuario' => $usuario['nombre_usuario'] ?? 'Asesor'
        ];

        $_SESSION['rol'] = $usuario['rol'] ?? 'usuario';
        $_SESSION['last_activity'] = time(); // Sincronizado con tu validador de vistas

        header("Location: panel_control.php");
        exit;
    }

} else {
    $error = "<li>Las claves son incorrectas</li>";
    header("Location: /index.php?error=" . urlencode($error));
    exit;
}

ob_end_flush();
?>
