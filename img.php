<?php
// img.php - sirve siempre el archivo real, sin importar el sufijo de la URL
$imagePath = __DIR__ . '/asset/rastreo_lince.png';

if (!file_exists($imagePath)) {
    http_response_code(404);
    exit;
}

header('Content-Type: image/png');
header('Content-Length: ' . filesize($imagePath));
header('Cache-Control: no-cache, must-revalidate');
readfile($imagePath);