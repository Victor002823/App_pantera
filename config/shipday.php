<?php
// La clave vive en private_config/, un nivel arriba de public_html/,
// para que nunca sea accesible por URL bajo ningún escenario.
//
// Estructura real en el hosting:
//   domains/control.mudanzasellince.com/
//     private_config/
//       shipday_key.php
//     public_html/
//       config/
//         shipday.php   <- este archivo
//
// __DIR__ aqui es .../public_html/config
// dirname(__DIR__) es .../public_html
// dirname(dirname(__DIR__)) es .../control.mudanzasellince.com  (donde vive private_config/)

$apiKey = require dirname(dirname(__DIR__)) . '/private_config/shipday_key.php';

return [
    'base_url' => 'https://api.shipday.com',
    'auth_header' => 'Basic ' . $apiKey,
    'timeout' => 8,
];
