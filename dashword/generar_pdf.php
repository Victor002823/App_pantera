<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

ob_start();
date_default_timezone_set('America/Mexico_City');

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

// =======================
// CONTROL DE SESIÓN
// =======================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['usuario'])) {
    header("Location: /index.php");
    exit;
}

// =======================
// CONEXIÓN BD
// =======================
require_once __DIR__ . '/../config/db.php';

$logo_path = __DIR__ . '/../icon-512.png';
$logo = file_exists($logo_path) ? base64_encode(file_get_contents($logo_path)) : null;

$pdo = (new db())->conexion();

// =======================
// OBTENER PAGO
// =======================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT *
    FROM pagos
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);
$pago = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pago) {
    die("Pago no encontrado.");
}

// =======================
// DATOS GENERALES
// =======================
$monto_formateado = number_format($pago['monto'] ?? 0, 2, '.', ',');
$fecha_creacion = $pago['created_at'] ?? date('Y-m-d H:i:s');

$fecha_limite_pago = !empty($pago['fecha_limite_pago'])
    ? date('d/m/Y', strtotime($pago['fecha_limite_pago']))
    : null;

$concepto_pago = $pago['concepto'] ?? ("Servicio " . ($pago['cotizacion_id'] ?? ''));

$contacto_correo = 'CAMBIAR_CORREO';
$contacto_tel    = 'CAMBIAR_TELEFONO';

$status = $pago['status'] ?? 'pending';
$status_map = [
    'completed'      => ['label' => 'PAGADO',    'bg' => '#DCFCE7', 'fg' => '#15803D'],
    'charge_success' => ['label' => 'PAGADO',    'bg' => '#DCFCE7', 'fg' => '#15803D'],
    'pending'        => ['label' => 'PENDIENTE', 'bg' => '#FEF3C7', 'fg' => '#B45309'],
    'charge_pending' => ['label' => 'PENDIENTE', 'bg' => '#FEF3C7', 'fg' => '#B45309'],
    'failed'         => ['label' => 'FALLIDO',   'bg' => '#FEE2E2', 'fg' => '#B91C1C'],
    'cancelled'      => ['label' => 'CANCELADO', 'bg' => '#F1F5F9', 'fg' => '#475569'],
];
$status_info = $status_map[$status] ?? ['label' => strtoupper($status), 'bg' => '#F1F5F9', 'fg' => '#475569'];

// =======================
// MÉTODOS DE PAGO
// =======================
$clabe           = !empty($pago['clabe']) ? $pago['clabe'] : null;
$banco_receptor  = !empty($pago['banco']) ? $pago['banco'] : null;
$convenio_cie    = !empty($pago['convenio_cie']) ? $pago['convenio_cie'] : null;
$referencia_spei = !empty($pago['referencia_spei']) ? $pago['referencia_spei'] : null;
$paynet          = !empty($pago['paynet_reference']) ? $pago['paynet_reference'] : null;
$link_pago       = !empty($pago['payment_url']) ? $pago['payment_url'] : null;

$folio = "ORD-" . date('Y-m-d') . "-" . str_pad($pago['id'], 4, '0', STR_PAD_LEFT);

$mostrar_spei   = !empty($clabe) || !empty($convenio_cie);
$mostrar_paynet = !empty($paynet);
$mostrar_link   = !empty($link_pago);

// =======================
// CÓDIGO DE BARRAS PAYNET
// =======================
$barcode = null;
if ($mostrar_paynet) {
    $generator = new BarcodeGeneratorPNG();
    $barcode = base64_encode($generator->getBarcode($paynet, BarcodeGeneratorPNG::TYPE_CODE_128));
}

// =======================
// QR DEL LINK DE PAGO
// =======================
$qr_link = null;
if ($mostrar_link) {
    $result = Builder::create()
        ->writer(new PngWriter())
        ->data($link_pago)
        ->size(300)
        ->margin(5)
        ->build();

    $qr_link = base64_encode($result->getString());
}

// =======================
// OPTIMIZADOR DE LOGOS
// =======================
$cache_dir = __DIR__ . '/assets/Logotipos_cadenas/_cache/';
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

function logoOptimizado(string $rutaOriginal, string $cacheDir, int $anchoMax = 300): ?string
{
    if (!file_exists($rutaOriginal)) return null;

    $nombreCache = $cacheDir . md5($rutaOriginal) . '.jpg';

    if (file_exists($nombreCache)) {
        return base64_encode(file_get_contents($nombreCache));
    }

    $info = @getimagesize($rutaOriginal);
    if (!$info) return base64_encode(file_get_contents($rutaOriginal));

    [$anchoOriginal, $altoOriginal, $tipo] = $info;

    $imagenOrigen = match ($tipo) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($rutaOriginal),
        IMAGETYPE_PNG => imagecreatefrompng($rutaOriginal),
        default => null,
    };

    if (!$imagenOrigen) return base64_encode(file_get_contents($rutaOriginal));

    $ratio = $anchoMax / $anchoOriginal;
    $nuevoAncho = $anchoMax;
    $nuevoAlto = (int)($altoOriginal * $ratio);

    $imagenDestino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
    imagefill($imagenDestino, 0, 0, imagecolorallocate($imagenDestino, 255, 255, 255));
    imagecopyresampled($imagenDestino, $imagenOrigen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $anchoOriginal, $altoOriginal);

    imagejpeg($imagenDestino, $nombreCache, 85);
    imagedestroy($imagenOrigen);
    imagedestroy($imagenDestino);

    return base64_encode(file_get_contents($nombreCache));
}

$logos_dir = __DIR__ . '/assets/Logotipos_cadenas/';

// Cargar Logo de Openpay
$logo_openpay_path = $logos_dir . 'paynet_.png';
$logo_openpay_base64 = logoOptimizado($logo_openpay_path, $cache_dir);

// Cadenas y Bancos
$tiendas_paynet = [
    ['nombre' => 'Walmart',              'logo' => 'walmart.jpg'],
    ['nombre' => 'walmart expres',       'logo' => 'walmart_express.png'],
    ['nombre' => 'Bodega Aurrerá',       'logo' => 'bodegaAurrera.jpg'],
    ['nombre' => '7-Eleven',             'logo' => '7eleven.jpg'],
    ['nombre' => 'Farmacias del Ahorro', 'logo' => 'farmaciaAhorro.jpg'],
];

foreach ($tiendas_paynet as &$tienda) {
    $ruta_logo = $logos_dir . $tienda['logo'];
    $tienda['logo_base64'] = logoOptimizado($ruta_logo, $cache_dir);
    $tienda['mime'] = 'image/jpeg';
}
unset($tienda);

$bancos_spei = [
    ['nombre' => 'BBVA',        'logo' => 'BBVA.png'],
    ['nombre' => 'Citibanamex', 'logo' => 'citibanamex.png'],
    ['nombre' => 'HSBC',        'logo' => 'hsbc.png'],
    ['nombre' => 'Santander',   'logo' => 'santander.png'],
];

$bancos_dir = __DIR__ . '/assets/Logotipos_bancos/';
foreach ($bancos_spei as &$banco) {
    $ruta_logo = $bancos_dir . $banco['logo'];
    $banco['logo_base64'] = logoOptimizado($ruta_logo, $cache_dir);
    $banco['mime'] = 'image/jpeg';
}
unset($banco);

$razon_social = $pago['razon_social'] ?? 'VICTOR IVAN ROJAS DURAN';
$rfc          = $pago['rfc'] ?? 'CAMBIAR_RFC';
$regimen      = $pago['regimen'] ?? 'Persona Fisica con Actividad Empresarial';

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Orden de Pago - Ticket</title>
<style>
@page {
    margin: 0;
}

html, body {
    margin: 0;
    padding: 0;
    font-family: Helvetica, Arial, sans-serif;
    font-size: 8px;
    color: #0f172a;
    background: #ffffff;
}

.ticket {
    padding: 4mm 6mm 6mm 6mm;
    box-sizing: border-box;
}

.text-center { text-align: center; }
.text-right { text-align: right; }
.text-bold { font-weight: bold; }

.brand-logo {
    width: 20mm;
    height: auto;
    display: block;
    margin: 0 auto 1.5mm auto;
}

.brand-eyebrow {
    font-size: 6.5px;
    font-weight: bold;
    letter-spacing: 1px;
    color: #64748b;
    text-transform: uppercase;
    text-align: center;
}

.brand-title {
    font-size: 10px;
    font-weight: bold;
    color: #0b1b3d;
    text-transform: uppercase;
    text-align: center;
    line-height: 1.2;
    margin-top: 0.5mm;
}

.divider-dashed {
    border: none;
    border-top: 1px dashed #94a3b8;
    margin: 2mm 0;
}

.badge-folio {
    display: inline-block;
    background: #0b1b3d;
    color: #ffffff;
    font-size: 8px;
    font-weight: bold;
    padding: 0.8mm 2.5mm;
    border-radius: 6px;
}

.badge-status {
    display: inline-block;
    font-size: 7.5px;
    font-weight: bold;
    padding: 0.6mm 2.5mm;
    border-radius: 6px;
    margin-top: 1mm;
    background: <?= $status_info['bg'] ?>;
    color: <?= $status_info['fg'] ?>;
}

.meta-date {
    font-size: 7px;
    color: #64748b;
    margin-top: 1mm;
}

.kv-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    margin: 1.5mm 0;
}

.kv-table td {
    vertical-align: top;
    padding: 0.8mm 0;
    font-size: 8px;
}

.kv-key {
    width: 30%;
    color: #64748b;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 7px;
}

.kv-val {
    width: 70%;
    text-align: right;
    color: #0f172a;
    font-weight: bold;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.amount-box {
    background: #fffbf5;
    border: 1.5px solid #d97706;
    border-radius: 4px;
    padding: 2mm;
    text-align: center;
    margin: 2mm 0;
}

.amount-label {
    font-size: 7px;
    font-weight: bold;
    color: #b45309;
    text-transform: uppercase;
}

.amount-val {
    font-family: Georgia, "Times New Roman", serif;
    font-size: 15px;
    font-weight: bold;
    color: #0b1b3d;
    margin: 0.5mm 0 0.2mm 0;
}

.amount-limit {
    font-size: 7px;
    color: #b45309;
    border-top: 1px dashed #f2d9a8;
    margin-top: 1.5mm;
    padding-top: 1.5mm;
}

.section-title {
    font-size: 7.5px;
    font-weight: bold;
    text-transform: uppercase;
    color: #0b1b3d;
    background: #f1f5f9;
    padding: 1mm 1.5mm;
    border-left: 3px solid #d97706;
    margin: 2mm 0 1.5mm 0;
}

.method-box {
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 1.8mm;
    margin-bottom: 2mm;
    background: #ffffff;
}

.method-subtitle {
    font-size: 7.5px;
    font-weight: bold;
    color: #0b1b3d;
    margin-bottom: 1mm;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 0.6mm;
}

.item-label {
    font-size: 6.5px;
    font-weight: bold;
    color: #64748b;
    text-transform: uppercase;
    margin-top: 1.2mm;
}

.code-field {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-left: 2.5px solid #d97706;
    border-radius: 3px;
    padding: 1mm 1.5mm;
    font-family: "Courier New", Courier, monospace;
    font-size: 8px;
    font-weight: bold;
    color: #0b1b3d;
    margin-top: 0.5mm;
    word-wrap: break-word;
    word-break: break-all;
}

.instructions-list {
    margin: 1mm 0 1.5mm 3mm;
    padding: 0;
    font-size: 7px;
    color: #475569;
    line-height: 1.25;
}

.instructions-list li {
    margin-bottom: 0.4mm;
}

.barcode-wrap {
    text-align: center;
    margin-top: 1.5mm;
}

.barcode-wrap img {
    max-width: 100%;
    width: 44mm;
    height: auto;
    max-height: 14mm;
}

.barcode-num {
    font-family: "Courier New", Courier, monospace;
    font-size: 8px;
    font-weight: bold;
    margin-top: 0.8mm;
    color: #0f172a;
}

.qr-wrap {
    text-align: center;
    margin: 1.5mm 0;
}

.qr-wrap img {
    width: 24mm;
    height: 24mm;
}

.tag-item {
    display: inline-block;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: 6.5px;
    font-weight: bold;
    padding: 0.3mm 1.2mm;
    border-radius: 3px;
    margin-right: 0.4mm;
    margin-bottom: 0.4mm;
}

.tiendas-logos {
    margin-top: 1.5mm;
    text-align: center;
}

.tienda-logo {
    margin: 0 0.8mm;
    vertical-align: middle;
}

.fiscal-box {
    font-size: 7px;
    color: #475569;
    line-height: 1.3;
    margin-top: 1.5mm;
}

.ticket-footer {
    text-align: center;
    font-size: 7px;
    color: #64748b;
    line-height: 1.3;
    margin-top: 2mm;
    padding-top: 1.5mm;
    border-top: 1px dashed #94a3b8;
}

.ticket-footer strong {
    color: #0b1b3d;
}
</style>
</head>
<body>

<div class="ticket">

    <!-- ENCABEZADO -->
    <div class="text-center">
        <?php if ($logo): ?>
            <img src="data:image/png;base64,<?= $logo ?>" class="brand-logo" alt="Logo">
        <?php endif; ?>
        <div class="brand-eyebrow">Orden de Pago</div>
        <div class="brand-title">FLETES Y MUDANZAS EL LINCE</div>
        
        <div style="margin-top: 1.5mm;">
            <span class="badge-folio"><?= htmlspecialchars($folio) ?></span>
        </div>
        <div>
            <span class="badge-status"><?= htmlspecialchars($status_info['label']) ?></span>
        </div>
        <div class="meta-date">Emitido: <?= date('d/m/Y H:i', strtotime($fecha_creacion)) ?></div>
    </div>

    <hr class="divider-dashed">

    <!-- RESUMEN DE PAGO -->
    <table class="kv-table">
        <tr>
            <td class="kv-key">CLIENTE:</td>
            <td class="kv-val"><?= htmlspecialchars($pago['nombre_cliente'] ?? 'N/A') ?></td>
        </tr>
        <tr>
            <td class="kv-key">SERVICIO:</td>
            <td class="kv-val">F-<?= htmlspecialchars($pago['cotizacion_id'] ?? 'N/A') ?></td>
        </tr>
        <tr>
            <td class="kv-key">CONCEPTO:</td>
            <td class="kv-val"><?= htmlspecialchars($concepto_pago) ?></td>
        </tr>
    </table>

    <!-- MONTO -->
    <div class="amount-box">
        <div class="amount-label">Monto Total a Pagar</div>
        <div class="amount-val">$<?= $monto_formateado ?> <span style="font-size: 8.5px;">MXN</span></div>
        <?php if ($fecha_limite_pago): ?>
        <div class="amount-limit">
            Pagar antes de: <strong><?= htmlspecialchars($fecha_limite_pago) ?></strong>
        </div>
        <?php endif; ?>
    </div>

    <!-- MÉTODOS DE PAGO -->
    <?php if ($mostrar_spei || $mostrar_paynet || $mostrar_link): ?>
        <div class="section-title">Formas de Pago Disponibles</div>
    <?php endif; ?>

    <!-- 1. SPEI -->
    <?php if ($mostrar_spei): ?>
    <div class="method-box">
        <div class="method-subtitle">Transferencia Bancaria (SPEI)</div>

        <?php if ($convenio_cie): ?>
            <div class="item-label">Opción A - Banca Bancomer</div>
            <ol class="instructions-list">
                <li>Menú "Pagar" &gt; "De servicios".</li>
                <li>Capture el número de convenio en "Referencia".</li>
                <li>Capture la referencia de 20 dígitos en "Concepto".</li>
            </ol>
            <div class="item-label">Convenio CIE Bancomer</div>
            <div class="code-field"><?= htmlspecialchars($convenio_cie) ?></div>
            
            <?php if ($referencia_spei): ?>
            <div class="item-label">Referencia de 20 dígitos</div>
            <div class="code-field"><?= htmlspecialchars($referencia_spei) ?></div>
            <?php endif; ?>

            <?php if ($clabe): ?><hr class="divider-dashed"><?php endif; ?>
        <?php endif; ?>

        <?php if ($clabe): ?>
            <div class="item-label"><?= $convenio_cie ? 'Opción B - Otros Bancos' : 'Instrucciones SPEI' ?></div>
            <ol class="instructions-list">
                <li>Capture la CLABE y el concepto exacto.</li>
            </ol>

            <?php if ($banco_receptor): ?>
            <div class="item-label">Banco Receptor</div>
            <div class="code-field"><?= htmlspecialchars($banco_receptor) ?></div>
            <?php endif; ?>

            <div class="item-label">Beneficiario</div>
            <div class="code-field">Transportes y Mudanzas Pantera</div>

            <div class="item-label">CLABE Interbancaria</div>
            <div class="code-field"><?= htmlspecialchars($clabe) ?></div>

            <?php if ($referencia_spei): ?>
            <div class="item-label">Concepto de Pago (Referencia)</div>
            <div class="code-field"><?= htmlspecialchars($referencia_spei) ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($bancos_spei)): ?>
            <div class="item-label" style="margin-top: 2mm; text-align: center;"></div>
            <div class="tiendas-logos">
                <?php foreach ($bancos_spei as $banco): ?>
                    <?php if ($banco['logo_base64']): ?>
                        <?php 
                            $estilo_logo = match($banco['nombre']) {
                                'BBVA'        => 'height: 4.5mm; max-width: 11mm;',
                                'Citibanamex' => 'height: 7mm; max-width: 22mm;',
                                'HSBC'        => 'height: 6.5mm; max-width: 18mm;',
                                'Santander'   => 'height: 6.5mm; max-width: 19mm;',
                                default       => 'height: 6mm; max-width: 18mm;'
                            };
                        ?>
                        <img src="data:<?= $banco['mime'] ?>;base64,<?= $banco['logo_base64'] ?>"
                             alt="<?= htmlspecialchars($banco['nombre']) ?>" 
                             class="tienda-logo" 
                             style="<?= $estilo_logo ?>">
                    <?php else: ?>
                        <span class="tag-item"><?= htmlspecialchars($banco['nombre']) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- 2. PAYNET -->
    <?php if ($mostrar_paynet): ?>
    <div class="method-box">
        <table style="width: 100%; border-collapse: collapse; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.6mm; margin-bottom: 1mm;">
            <tr>
                <td style="vertical-align: middle; padding: 0;">
                    <span class="method-subtitle" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">Pago en Efectivo (Paynet)</span>
                </td>
                <?php if (!empty($logo_openpay_base64)): ?>
                <td style="text-align: right; vertical-align: middle; padding: 0;">
                    <img src="data:image/jpeg;base64,<?= $logo_openpay_base64 ?>" 
                         alt="Openpay" 
                         style="height: 6.5mm; width: auto;">
                </td>
                <?php endif; ?>
            </tr>
        </table>

        <ol class="instructions-list">
            <li>Muestre el código de barras o referencia al cajero.</li>
            <li>Pague el monto exacto en efectivo.</li>
        </ol>

        <?php if ($barcode): ?>
            <div class="barcode-wrap">
                <img src="data:image/png;base64,<?= $barcode ?>" alt="Código de Barras">
                <div class="barcode-num"><?= htmlspecialchars($paynet) ?></div>
            </div>
        <?php else: ?>
            <div class="item-label">Referencia Paynet</div>
            <div class="code-field"><?= htmlspecialchars($paynet) ?></div>
        <?php endif; ?>

        <?php if (!empty($tiendas_paynet)): ?>
            <div class="item-label" style="margin-top: 1.5mm; text-align: center;">Tiendas Participantes</div>
            <div class="tiendas-logos">
                <?php foreach ($tiendas_paynet as $tienda): ?>
                    <?php if ($tienda['logo_base64']): ?>
                        <img src="data:<?= $tienda['mime'] ?>;base64,<?= $tienda['logo_base64'] ?>"
                             alt="<?= htmlspecialchars($tienda['nombre']) ?>" 
                             class="tienda-logo"
                             style="height: 5mm; max-width: 14mm;">
                    <?php else: ?>
                        <span class="tag-item"><?= htmlspecialchars($tienda['nombre']) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- 3. LINK / QR DE PAGO -->
    <?php if ($mostrar_link): ?>
    <div class="method-box text-center">
        <div class="method-subtitle">Pago en Línea con Tarjeta</div>
        <div class="instructions-list" style="text-align: left;">
            Escanee el código QR para pagar en línea:
        </div>

        <?php if ($qr_link): ?>
            <div class="qr-wrap">
                <img src="data:image/png;base64,<?= $qr_link ?>" alt="QR Pago">
            </div>
        <?php endif; ?>

        <div class="item-label" style="text-align: left;">Enlace directo</div>
        <div class="code-field" style="font-size: 7px; text-align: left;"><?= htmlspecialchars($link_pago) ?></div>
    </div>
    <?php endif; ?>

    <hr class="divider-dashed">

    <!-- DATOS FISCALES -->
    <div class="section-title">Datos Fiscales</div>
    <div class="fiscal-box">
        <div><strong>Razón Social:</strong> <?= htmlspecialchars($razon_social) ?></div>
        <div><strong>RFC:</strong> <?= htmlspecialchars($rfc) ?></div>
        <div><strong>Régimen:</strong> <?= htmlspecialchars($regimen) ?></div>
    </div>

    <!-- PIE DE PÁGINA -->
    <div class="ticket-footer">
        <?php 
        $ruta_openpay_footer = __DIR__ . '/assets/Logotipos_bancos/LogotipoOpenpay.jpg';
        $logo_openpay_footer = logoOptimizado($ruta_openpay_footer, $cache_dir);
        ?>
        <?php if ($logo_openpay_footer): ?>
            <div style="margin-bottom: 1mm;">
                <span style="font-size: 7px; color: #64748b; font-weight: bold; vertical-align: middle;">PROCESADO POR</span>
                <img src="data:image/jpeg;base64,<?= $logo_openpay_footer ?>" 
                     alt="Openpay" 
                     style="height: 3.5mm; width: auto; vertical-align: middle; margin-left: 1mm;">
            </div>
        <?php else: ?>
            <strong>PROCESADO POR OPENPAY</strong><br>
        <?php endif; ?>
        Confirmación automática · Sin comprobante impreso<br>
        Soporte: <?= htmlspecialchars($contacto_correo) ?><br>
        Tel: <?= htmlspecialchars($contacto_tel) ?>
        <div style="font-family: 'Courier New', monospace; font-size: 6.5px; margin-top: 1mm; color: #94a3b8;">
            <?= htmlspecialchars($folio) ?>
        </div>
    </div>

</div>

</body>
</html>

<?php

$html = ob_get_clean();
$html = preg_replace('/<script.*?<\/script>/s', '', $html);

// ==========================================
// CÁLCULO DINÁMICO DE ALTURA DEL TICKET (80mm)
// ==========================================
$alto_mm = 125; 

if ($mostrar_spei) {
    $alto_mm += 60; 
    if (!empty($convenio_cie) && !empty($clabe)) {
        $alto_mm += 40;
    }
}

if ($mostrar_paynet) {
    $alto_mm += 55; 
}

if ($mostrar_link) {
    $alto_mm += 50; 
}

// Convertir mm a pt (1 mm = 2.83465 pt)
$alto_pt = $alto_mm * 2.83465;

// ==========================================
// RENDER Y GENERACIÓN DEL PDF
// ==========================================
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');

// Ancho fijo de 80mm (226.77pt) y alto dinámico calculated
$dompdf->setPaper([0, 0, 226.77, $alto_pt], 'portrait');
$dompdf->render();

$dompdf->stream('orden_pago_' . $folio . '.pdf', ['Attachment' => false]);