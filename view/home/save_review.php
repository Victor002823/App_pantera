<?php
header('Content-Type: application/json');

// ===============================
// Configuración BD
// ===============================
$host = "Localhost";
$db   = "fletehxn_login";
$user = "fletehxn_login";
$pass = "L64tk6MaDqvusRRsp2DW";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de conexión a la base de datos'
    ]);
    exit;
}

// ===============================
// Obtener y validar POST
// ===============================
$rating   = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$phone    = trim($_POST['phone'] ?? '');
$comments = trim($_POST['comments'] ?? '');

// Validación de calificación (1–5)
if ($rating === false || $rating < 1 || $rating > 5) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Calificación inválida'
    ]);
    exit;
}

// Si la calificación es baja, comentarios obligatorios
if ($rating <= 3 && $comments === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Comentarios requeridos para calificación baja'
    ]);
    exit;
}

// Validación básica de teléfono (opcional)
if ($phone !== '' && !preg_match('/^\d{7,15}$/', $phone)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Teléfono inválido'
    ]);
    exit;
}

// ===============================
// Guardar en BD
// ===============================
try {
    $stmt = $pdo->prepare("
        INSERT INTO reviews (rating, phone, comments, created_at)
        VALUES (:rating, :phone, :comments, NOW())
    ");

    $stmt->execute([
        ':rating'   => $rating,
        ':phone'    => $phone,
        ':comments' => $comments
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al guardar la calificación'
    ]);
    exit;
}

// ===============================
// Envío de correo
// ===============================
$to = "naviltc28@gmail.com,acua_cedillo@hotmail.com";
$subject = "Nueva calificación de Fletes y Mudanzas El Lince";

$headers  = "From: Fletes y Mudanzas El Lince <contacto@mudanzasellince.com>\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

$stars = str_repeat('⭐', $rating);

$body = "
<h2>Nueva reseña recibida</h2>
<p><strong>Calificación:</strong> {$stars} ({$rating}/5)</p>
<p><strong>Teléfono:</strong> " . htmlspecialchars($phone ?: 'No proporcionado') . "</p>
<p><strong>Comentarios:</strong><br>" . nl2br(htmlspecialchars($comments ?: 'Sin comentarios')) . "</p>
<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>
";

@mail($to, $subject, $body, $headers);

// ===============================
// Respuesta final
// ===============================
echo json_encode([
    'status' => 'success',
    'message' => 'Calificación guardada correctamente'
]);