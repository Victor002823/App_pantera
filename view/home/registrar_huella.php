<?php
// /view/home/registrar_huella.php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

// 🔐 1. CONTROL DE SESIÓN
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validamos que exista un asesor firmado en el sistema
if (empty($_SESSION['usuario']) || empty($_SESSION['usuario']['correo'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Inicie sesión tradicional primero.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Incluimos el archivo de conexión centralizado
// Nota: Ajusta la ruta relativa si tu archivo db.php se encuentra en otra carpeta (ej. '../../config/db.php')
require_once(__DIR__ . "/../../config/db.php");

// Captura de datos JSON enviados desde JS o desde Android
$input = json_decode(file_get_contents('php://input'), true);

// Limpiamos y estandarizamos el correo a minúsculas
$correo = isset($input['correo']) ? trim(strtolower($input['correo'])) : '';
$credentialId = $input['credentialId'] ?? '';

// Compatible con Android y WebAuthn
$publicKeyData = $input['publicKey'] ?? 'NATIVE_ANDROID_KEY';

// Si falta el correo o el ID de la credencial, detenemos el flujo
if (!$correo || !$credentialId) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos recibidos en el servidor'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 🔒 Validación de identidad (Seguridad del lado del servidor)
$correoSesion = trim(strtolower($_SESSION['usuario']['correo']));
if ($correo !== $correoSesion) {
    echo json_encode([
        'success' => false,
        'message' => 'El correo enviado no coincide con su sesión activa.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 3. Instanciamos la clase y obtenemos el objeto PDO
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();

    // Verificamos si la conexión falló (si devolvió un string de error)
    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

    // Buscar si el usuario ya tiene un registro previo en la tabla dedicada de huellas
    $stmt = $pdo->prepare("SELECT id FROM usuarios_huella WHERE correo = ? LIMIT 1");
    $stmt->execute([$correo]);
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existe) {
        // Actualiza la credencial/llave pública si ya existía
        $stmtUpdate = $pdo->prepare(
            "UPDATE usuarios_huella
             SET credentialId = ?, publicKey = ?
             WHERE correo = ?"
        );
        $stmtUpdate->execute([$credentialId, $publicKeyData, $correo]);
    } else {
        // Registra por primera vez
        $stmtInsert = $pdo->prepare(
            "INSERT INTO usuarios_huella
            (correo, credentialId, publicKey)
            VALUES (?, ?, ?)"
        );
        $stmtInsert->execute([$correo, $credentialId, $publicKeyData]);
    }

    // 🌟 Enviamos éxito de vuelta a la Web / APK
    echo json_encode([
        'success' => true,
        'token' => $credentialId
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
