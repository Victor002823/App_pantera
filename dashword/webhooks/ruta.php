<?php

header('Content-Type: application/json');

$log = __DIR__ . '/openpay_log.txt';

$contenido =
"====================\n".
date('Y-m-d H:i:s')."\n".
"METHOD: ".$_SERVER['REQUEST_METHOD']."\n".
"BODY:\n".file_get_contents("php://input")."\n\n";

file_put_contents($log, $contenido, FILE_APPEND);

echo json_encode([
    "success" => true
]);