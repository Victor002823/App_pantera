<?php
header('Content-Type: application/json; charset=utf-8');

// Buffer propio: así, sin importar qué warnings/notices genere PHP
// más abajo (sesión, includes, etc.), siempre podemos limpiarlo
// justo antes de mandar el JSON real.
ob_start();

/**
 * Envía una respuesta JSON limpia y termina la ejecución.
 * Descarta cualquier salida previa acumulada en el buffer (warnings, notices, etc.)
 */
function responder(array $data) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($data);
    exit;
}

// =======================
// CONTROL DE SESIÓN
// =======================
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}
if (empty($_SESSION['usuario'])) {
    responder(["success"=>false, "error"=>"Sesión no válida"]);
}

require_once __DIR__ . '/../config/db.php';

$db = new db();
$pdo = $db->conexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$config = require __DIR__ . '/config/api_keys.php';

$merchant_id = $config['merchant_id'];
$private_key = $config['private_key'];

$base_url = $config['is_production']
    ? "https://api.openpay.mx/v1/"
    : "https://sandbox-api.openpay.mx/v1/";


/* ==========================
   DATOS
========================== */

$id = $_POST['id'] ?? null;

$metodo_input = strtolower(trim($_POST['metodo'] ?? 'spei'));

$asesor = $_SESSION['usuario']['nombre_usuario'] ?? 'Desconocido';


if (!$id) {
    responder(["success"=>false, "error"=>"ID no recibido"]);
}



/* ==========================
   SERVICIO
========================== */

$stmt = $pdo->prepare("
    SELECT *
    FROM servicios
    WHERE id=?
");

$stmt->execute([$id]);

$servicio = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$servicio) {
    responder(["success"=>false, "error"=>"Servicio no encontrado"]);
}


$total = (float)$servicio['total'];
$maniobra = (float)$servicio['maniobra'] ?? 0;

$monto = round(($total + $maniobra) * 0.50, 2);

$nombre = $servicio['nombre_cliente'] ?? "Cliente";


if($monto <= 0){
    responder(["success"=>false, "error"=>"Monto inválido"]);
}



/* ==========================
   METODO Y ENDPOINT OPENPAY
========================== */

$endpoint_path = "charges";
$metodo_final = "bank_account";

if($metodo_input === "paynet"){
    $metodo_final = "store";
} elseif($metodo_input === "link"){
    $metodo_final = "card";
} else {
    $metodo_final = "bank_account";
}


/* ==========================
   FECHA LÍMITE DE PAGO
   Openpay exige mostrarla en el recibo (máx. 30 días).
   Ajusta el número de días según tu política interna.
========================== */
$DIAS_LIMITE_PAGO = 3;
$fecha_limite = date('Y-m-d', strtotime("+{$DIAS_LIMITE_PAGO} days"));

$concepto = "Servicio " . $id;


/* ==========================
   REQUEST OPENPAY
========================== */

if($metodo_input === "link") {
    // Estructura exacta para Link de pago basada en la respuesta de Openpay (/charges con product_type PAYMENT_LINK)
    $data = [
        "confirm" => false,
        "amount" => $monto,
        "product_type" => "PAYMENT_LINK",
        "method" => "card",
        "currency" => "MXN",
        "origin_channel" => "tpv",
        "description" => $concepto,
        "order_id" => "ORD-{$id}-" . date('YmdHis') . "-" . mt_rand(1000, 9999),
        "customer" => [
    "name" => $nombre,
    "last_name" => "Cliente",
    "email" => $servicio['correo'] ?: "pago+{$id}@local.com",
    "phone_number" => $servicio['telefono'] ?: "5512345678"
]
    ];
} else {
    // Estructura de payload para SPEI / Paynet
    $data = [
        "method" => $metodo_final,
        "amount" => $monto,
        "description" => $concepto,
        "order_id" => "ORD-{$id}-" . date('YmdHis') . "-" . mt_rand(1000, 9999),
        "customer" => [
    "name" => $nombre,
    "last_name" => "Cliente",
    "email" => $servicio['correo'] ?: "pago+{$id}@local.com",
    "phone_number" => $servicio['telefono'] ?: "5512345678"
]
    ];
}



$ch = curl_init(
    $base_url . $merchant_id . "/" . $endpoint_path
);


curl_setopt_array($ch, [

    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_USERPWD => $private_key . ":",

    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json"
    ],

    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_SSL_VERIFYPEER => false

]);


$response = curl_exec($ch);

$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$error = curl_error($ch);

curl_close($ch);



if($error){
    responder(["success" => false, "error" => $error]);
}



$res_api = json_decode($response, true);



if($http >= 400){
    responder([
        "success" => false,
        "error" => "Openpay rechazó la solicitud",
        "detalle" => $res_api
    ]);
}



/* ==========================
   GUARDAR PAGO
========================== */


if($metodo_input === "link"){

    // Link de pago (Extraído de payment_method.url según la respuesta real)
    $payment_url = $res_api['payment_method']['url'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO pagos
        (
            cotizacion_id,
            nombre_cliente,
            asesor,
            monto,
            status,
            openpay_id,
            payment_url,
            concepto,
            fecha_limite_pago
        )
        VALUES(?,?,?,?,?,?,?,?,?)
    ");

    $stmt->execute([
        $id,
        $nombre,
        $asesor,
        $monto,
        "pending",
        $res_api['id'],
        $payment_url,
        $concepto,
        $fecha_limite
    ]);

} elseif($metodo_final === "bank_account"){

    // Openpay regresa: clabe, bank (nombre del banco), agreement (convenio CIE,
    // solo si el banco asignado es Bancomer) y name (referencia numérica de 20 dígitos).
    // Confirmado con la documentación oficial:
    // "payment_method": { "type":"bank_transfer", "bank":"BBVA Bancomer",
    //   "agreement":"1411217", "clabe":"...", "name":"11094690394055678934" }
    $clabe        = $res_api['payment_method']['clabe'] ?? null;
    $banco        = $res_api['payment_method']['bank'] ?? null;
    $convenio_cie = $res_api['payment_method']['agreement'] ?? null;
    $referencia   = $res_api['payment_method']['name'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO pagos
        (
            cotizacion_id,
            nombre_cliente,
            asesor,
            monto,
            status,
            clabe,
            banco,
            convenio_cie,
            referencia_spei,
            concepto,
            fecha_limite_pago,
            openpay_id
        )
        VALUES(?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmt->execute([
        $id,
        $nombre,
        $asesor,
        $monto,
        "pending",
        $clabe,
        $banco,
        $convenio_cie,
        $referencia,
        $concepto,
        $fecha_limite,
        $res_api['id']
    ]);

} else {

    $referencia = $res_api['payment_method']['reference'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO pagos
        (
            cotizacion_id,
            nombre_cliente,
            asesor,
            monto,
            status,
            paynet_reference,
            concepto,
            fecha_limite_pago,
            openpay_id
        )
        VALUES(?,?,?,?,?,?,?,?,?)
    ");

    $stmt->execute([
        $id,
        $nombre,
        $asesor,
        $monto,
        "pending",
        $referencia,
        $concepto,
        $fecha_limite,
        $res_api['id']
    ]);

}



$pago_id = $pdo->lastInsertId();



if($metodo_input === "link"){
    responder([
        "success" => true,
        "pago_id" => $pago_id,
        "metodo" => "link",
        "openpay_id" => $res_api['id'],
        "url_pago" => $res_api['payment_method']['url'] ?? null
    ]);
} else {
    responder([
        "success" => true,
        "pago_id" => $pago_id,
        "metodo" => $metodo_final,
        "openpay_id" => $res_api['id'],
        "payment_method" => $res_api['payment_method']
    ]);
}
