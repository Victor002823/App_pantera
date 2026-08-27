<?php
// En Render/Docker las credenciales viven en variables de entorno
// (configuradas en el dashboard del servicio), no en un archivo fuera
// de la carpeta pública como en el hosting tradicional.

$apiKey = getenv('SHIPDAY_API_KEY') ?: '';

return [
    'base_url' => 'https://api.shipday.com',
    'auth_header' => 'Basic ' . $apiKey,
    'timeout' => 8,
];
