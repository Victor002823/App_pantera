<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

// NOTA: revisa que esta ruta apunte exactamente a tu autoload.php de Composer.
// Ajusta la cantidad de "../" según dónde quede este archivo respecto a la carpeta vendor.
require_once(__DIR__ . "/../../vendor/autoload.php");

use Dompdf\Dompdf;
use Dompdf\Options;

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $folio = isset($input['folio']) ? htmlspecialchars($input['folio']) : '';
    $carrito = isset($input['carrito']) && is_array($input['carrito']) ? $input['carrito'] : [];
    $totalGeneral = isset($input['totalGeneral']) ? floatval($input['totalGeneral']) : 0;

    if (empty($carrito)) {
        echo json_encode(['success' => false, 'error' => 'El carrito está vacío']);
        exit;
    }

    date_default_timezone_set('America/Mexico_City');
    $fechaMX = date('d/m/Y');
    $horaMX = strtoupper(date('h:i A'));

    // ==========================================
    // Construir filas de la tabla de conceptos
    // ==========================================
    $filasHTML = '';
    foreach ($carrito as $item) {
        $cliente = htmlspecialchars($item['cliente'] ?? '');
        $producto = htmlspecialchars($item['producto'] ?? '');
        $cantidad = htmlspecialchars($item['cantidad'] ?? '');
        $total = number_format(floatval($item['total'] ?? 0), 2);

        $filasHTML .= "
        <tr>
            <td style=\"padding:10px 8px; vertical-align:top; width:15%; font-weight:bold; font-size:13px; color:#2d3748;\">{$cliente}</td>
            <td style=\"padding:10px 8px; vertical-align:top; width:60%; text-align:justify; line-height:1.4; font-size:13px; color:#2d3748;\">{$producto}</td>
            <td style=\"padding:10px 8px; vertical-align:top; text-align:center; width:10%; font-size:13px; color:#2d3748;\">{$cantidad}</td>
            <td style=\"padding:10px 8px; vertical-align:top; text-align:right; width:15%; font-weight:500; font-size:13px; color:#2d3748;\">\${$total}</td>
        </tr>";
    }

    // Filas de relleno visual si hay pocos conceptos (mismo criterio que antes: mínimo 5 filas)
    $filasMinimas = 5;
    $filasFaltantes = max(0, $filasMinimas - count($carrito));
    for ($i = 0; $i < $filasFaltantes; $i++) {
        $filasHTML .= '<tr style="height:48px;"><td colspan="4">&nbsp;</td></tr>';
    }

    $totalFmt = number_format($totalGeneral, 2);

    // ==========================================
    // Logo embebido en base64 (DomPDF no siempre resuelve rutas relativas bien)
    // ==========================================
    $logoPath = realpath(__DIR__ . '/../../asset/logo1023.png');
    $logoBase64 = '';
    if ($logoPath && file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    }

    // ==========================================
    // HTML del documento (mismo diseño visual que el preview del JS)
    // ==========================================
    $html = "
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: sans-serif; margin: 20px; }
        </style>
    </head>
    <body>
        <table style='width:100%; border-collapse:collapse; border-bottom:3px solid #1400AD; padding-bottom:10px; margin-bottom:20px;'>
            <tr>
                <td style='width:60%; vertical-align:middle; padding-bottom:10px;'>
                    <table style='border-collapse:collapse;'>
                        <tr>
                            <td style='vertical-align:middle; padding-right:10px;'>
                                " . ($logoBase64 ? "<img src='{$logoBase64}' style='width:70px; height:70px; object-fit:contain;'>" : "") . "
                            </td>
                            <td style='vertical-align:middle;'>
                                <strong style='font-size:20px; color:#1a202c;'>Transportes y Mudanzas Pantera</strong><br>
                                <span style='color:#718096; font-size:13px; font-weight:600;'>Formato de Cotizacion</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style='width:40%; text-align:right; vertical-align:middle; font-size:12px; color:#2d3748; line-height:1.4; padding-bottom:10px;'>
                    <div style='margin-bottom:5px;'>
                        <strong style='color:#718096;'>Folio:</strong> <strong style='color:#FF0000; font-size:15px;'>{$folio}</strong>
                    </div>
                    <div><strong>Fecha:</strong> {$fechaMX}<br><strong>Hora:</strong> {$horaMX}<br>" . (($_SESSION['rol'] ?? '') === 'admin' ? 'RFC: CEFL950210513<br>Jose Ceballos 60<br>Tel: 5540662626' : '') . "</div>
                </td>
            </tr>
        </table>

        <table style='width:100%; box-sizing:border-box; border-collapse:separate; border-spacing:0; border:2px solid #1400AD; border-radius:12px; overflow:hidden; margin-bottom:5px;'>
            <thead style='background:#1400AD; color:white; font-size:13px;'>
                <tr>
                    <th style='padding:10px 8px; text-align:left;'>Cliente</th>
                    <th style='padding:10px 8px; text-align:left;'>Concepto</th>
                    <th style='padding:10px 8px; text-align:center;'>Cantidad</th>
                    <th style='padding:10px 8px; text-align:right;'>Total</th>
                </tr>
            </thead>
            <tbody style='background:white;'>{$filasHTML}</tbody>
        </table>

        <table style='width:100%; border-collapse:collapse; margin-top:10px; margin-bottom:25px; font-size:15px; color:#1a202c;'>
            <tr>
                <td style='text-align:left;'></td>
                <td style='text-align:right;'><strong>Total: \${$totalFmt}</strong></td>
            </tr>
        </table>

        <table style='width:100%; box-sizing:border-box; border-collapse:separate; border-spacing:0; border:2px solid #1400AD; border-radius:12px;'>
            <tr>
                <td style='padding:15px; font-size:11px; color:#2d3748; line-height:1.5; text-align:justify;'>
                    <strong>Observaciones del Servicio de Mudanza</strong><br>
                    Alcance: El servicio es exclusivo para el transporte de bienes; no se transporta a personas. El operador tiene la facultad de cancelar el servicio si las vialidades no son aptas para la unidad.<br>
                    Puntualidad: La hora de llegada está sujeta a factores externos (tráfico, accidentes, etc.).<br>
                    Tiempos de Espera: Se otorgan 30 minutos de tolerancia para carga y descarga. Excedido este tiempo, se aplicará un cargo del 10% del costo total por cada 30 minutos adicionales. Si la espera supera las 3 horas, la penalización será del 100% del costo.<br>
                    Inventario: El Cliente es responsable de proporcionar un listado detallado y preciso de los bienes a trasladar.<br>
                    Pagos y Anticipos: Se requiere un anticipo del 50% para reservar el servicio. El saldo restante debe liquidarse antes de iniciar la descarga en el destino.<br>
                    Cancelaciones: Deben notificarse con al menos 8 horas de anticipación. De lo contrario, el anticipo no será reembolsable. Si la Empresa cancela por causas propias, se devolverá el total del anticipo.<br>
                    Responsabilidad: La Empresa tomará las precauciones necesarias, pero se recomienda al Cliente contratar un seguro adicional para cubrir cualquier eventualidad.<br>
                    Consulta nuestros términos y condiciones del servicio.
                </td>
            </tr>
        </table>
    </body>
    </html>";

    // ==========================================
    // Renderizar con DomPDF
    // ==========================================
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'sans-serif');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfOutput = $dompdf->output();
    $base64Pdf = base64_encode($pdfOutput);

    echo json_encode([
        'success' => true,
        'pdf_base64' => $base64Pdf,
        'filename' => "Cotizacion-{$folio}.pdf"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al generar PDF: ' . $e->getMessage()]);
}
