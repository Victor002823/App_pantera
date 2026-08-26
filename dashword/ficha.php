<?php
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

$pdo = (new db())->conexion();


// =======================
// OBTENER PAGO
// =======================
$id = $_GET['id'] ?? 0;


$stmt = $pdo->prepare("
    SELECT *
    FROM pagos
    WHERE cotizacion_id = ?
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

$monto_formateado = number_format(
    $pago['monto'] ?? 0,
    2,
    '.',
    ','
);


$fecha_creacion = $pago['created_at'] 
    ?? date('Y-m-d H:i:s');



// =======================
// MÉTODOS DE PAGO
// =======================

// CLABE SPEI
$clabe = !empty($pago['clabe'])
    ? $pago['clabe']
    : null;


// REFERENCIA PAYNET
$paynet = !empty($pago['paynet_reference'])
    ? $pago['paynet_reference']
    : null;



// REFERENCIA INTERNA SPEI
$referencia_spei = 
"LINCE ORD " . 
str_pad(
    $pago['id'],
    8,
    '0',
    STR_PAD_LEFT
);



// REFERENCIA DOCUMENTO
$folio = 
"ORD-" .
date('Y-m-d') .
"-" .
str_pad(
    $pago['id'],
    4,
    '0',
    STR_PAD_LEFT
);



// Validación para saber qué mostrar

$mostrar_spei = !empty($clabe);

$mostrar_paynet = !empty($paynet);


?>
<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Orden de Pago - El Lince</title>


<style>

:root {
    --primary-navy:#00113B;
    --slate-gray:#64748B;
    --light-border:#E2E8F0;
    --bg-soft:#F8FAFC;
    --accent-gold:#D97706;
    --alert-red:#B91C1C;
}


*{
    box-sizing:border-box;
    margin:0;
    padding:0;
}


body{

    font-family:
    Arial,
    sans-serif;

    background:#f1f5f9;

    padding:20px 0;

    color:var(--primary-navy);

}


.toolbar{

    width:100%;
    max-width:210mm;
    margin:0 auto 20px;
    display:flex;
    gap:12px;
    position:sticky;
    top:0;
    background:white;
    padding:15px 20px;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,17,59,0.1);
    z-index:1000;

}


.toolbar button{

    flex:1;
    padding:14px 16px;

    background:var(--primary-navy);

    color:white;

    border:0;

    border-radius:10px;

    font-weight:bold;
    
    font-size:13px;
    
    cursor:pointer;
    
    transition:all 0.3s ease;
    
    display:flex;
    
    align-items:center;
    
    justify-content:center;
    
    gap:8px;

}


.toolbar button:active{

    transform:scale(0.98);
    
    opacity:0.9;

}


.toolbar button:disabled{

    opacity:0.6;
    
    cursor:not-allowed;

}


.toolbar button.loading{

    position:relative;

}


.toolbar button.loading span{

    visibility:hidden;

}


.toolbar button.loading::after{

    content:"";
    
    position:absolute;
    
    width:14px;
    
    height:14px;
    
    border:2px solid rgba(255,255,255,0.3);
    
    border-top-color:white;
    
    border-radius:50%;
    
    animation:spin 0.8s linear infinite;

}


@keyframes spin{

    to{transform:rotate(360deg);}

}


.page-wrapper{

    width:100%;
    overflow-x:auto;
    transform-origin:top center;
    display:flex;
    justify-content:center;

}


.a4-container{

    width:210mm;

    background:white;

    padding:14mm 16mm;

    margin:auto;

    display:flex;

    flex-direction:column;

}



.brand-header-layout{

display:flex;
justify-content:space-between;
align-items:center;

border-bottom:3px solid var(--primary-navy);

padding-bottom:15px;

margin-bottom:20px;

}



.identity-flex{

display:flex;
align-items:center;
gap:20px;

}


.brand-logo-img{

height:65px;

}



.brand-text h1{

font-size:22px;
font-weight:900;

}


.brand-text p{

font-size:11px;
color:var(--slate-gray);

}



.document-meta-block{

text-align:right;

}


.document-meta-block h2{

font-size:13px;
color:var(--slate-gray);

}



.process-step-title{

font-size:12px;
font-weight:900;
text-transform:uppercase;

display:flex;
align-items:center;

margin:20px 0 10px;

}


.process-step-title:after{

content:"";

height:1px;

background:#ddd;

flex:1;

margin-left:15px;

}



.financial-grid-dashboard{

display:grid;

grid-template-columns:
repeat(3,1fr);

gap:15px;

margin-bottom:25px;

}



.dashboard-metric-card{

border:1px solid var(--light-border);

padding:15px;

border-radius:8px;

background:var(--bg-soft);

text-align:center;

}



.metric-label{

font-size:10px;

font-weight:bold;

color:var(--slate-gray);

text-transform:uppercase;

}



.metric-value{

font-size:15px;

font-weight:bold;

margin-top:5px;

}


.amount-large{

font-size:24px;

color:var(--accent-gold);

}



.payment-method-panel{

border:1px solid var(--light-border);

border-radius:8px;

overflow:hidden;

margin-bottom:20px;

}



.panel-top-header{

background:var(--primary-navy);

color:white;

padding:12px;

font-weight:bold;

font-size:12px;

}



.info-data-line{

display:flex;

padding:12px 20px;

border-bottom:1px solid #eee;

}



.info-label{

width:30%;

font-size:11px;

font-weight:bold;

color:var(--slate-gray);

}



.info-value{

width:70%;

font-weight:bold;

}



.clabe-box{

font-family:monospace;

font-size:18px;

background:#f8fafc;

padding:8px;

border:1px solid #ddd;

}



.paynet-layout-flex{

display:flex;

align-items:center;

justify-content:space-between;

padding:20px;

}



.barcode-card-container{

border:1px solid #ddd;

padding:15px;

text-align:center;

}



#barcode{

width:210px;

height:50px;

}



.corporate-fiscal-table{

width:100%;

border-collapse:collapse;

margin-top:20px;

}



.corporate-fiscal-table td{

border:1px solid #ddd;

padding:10px;

}


.cell-label{

font-weight:bold;

background:#f8fafc;

}



.footer{

margin-top:auto;

text-align:center;

font-size:11px;

color:var(--slate-gray);

border-top:1px solid #ddd;

padding-top:15px;

}



@media print{

.toolbar{

display:none;

}


body{

background:white;

padding:0;

}


.a4-container{

width:100%;

}

}


@media(max-width:640px){

.toolbar{

gap:10px;
padding:12px 15px;
margin:0 auto 15px;

}


.toolbar button{

padding:12px 14px;
font-size:12px;

}


.a4-container{

padding:10mm 12mm;

}

}

</style>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
// Calcular scale dinámico para que quepa sin scroll
function calcularScaleDinamico() {
    const pageWrapper = document.querySelector(".page-wrapper");
    const a4Container = document.querySelector(".a4-container");
    
    if (!pageWrapper || !a4Container) return;
    
    // Solo aplicar en móvil
    if (window.innerWidth > 640) {
        pageWrapper.style.transform = "none";
        return;
    }
    
    // Alto disponible (viewport - toolbar - paddings)
    const toolbarHeight = document.querySelector(".toolbar").offsetHeight;
    const altoDisponible = window.innerHeight - toolbarHeight - 40;
    
    // Alto natural del A4
    const alturaA4Natural = 297 * (96 / 25.4); // mm a px (96 dpi)
    
    // Calcular scale para que quepa exactamente
    const scaleNecesario = altoDisponible / alturaA4Natural;
    
    // Limitar a máximo 0.8 (para que sea legible)
    const scaleFinal = Math.min(scaleNecesario, 0.8);
    
    pageWrapper.style.transform = `scale(${scaleFinal})`;
    pageWrapper.dataset.scale = scaleFinal;
}

// Ejecutar al cargar
document.addEventListener("DOMContentLoaded", calcularScaleDinamico);

// Recalcular si cambia el tamaño de ventana
window.addEventListener("resize", calcularScaleDinamico);

// Recalcular cuando se rota i dispositivo
window.addEventListener("orientationchange", () => {
    setTimeout(calcularScaleDinamico, 100);
});
</script>
</head>


<body>


<div class="toolbar">

<button onclick="enviarCliente()">
<span>📲 Enviar cliente</span>
</button>


<button id="compartir-btn" onclick="compartirPDF()">
<span>📄 PDF</span>
</button>


<button onclick="copiarPago()">
<span>📋 Copiar datos</span>
</button>


<button id="imprimir-btn" onclick="window.print()">
<span>🖨 Imprimir</span>
</button>

</div>



<div class="page-wrapper">


<div class="a4-container" id="a4-content">


<header class="brand-header-layout">


<div class="identity-flex">


<img src="/icon-512.png"
class="brand-logo-img">


<div class="brand-text">

<h1>
Fletes y Mudanzas El Lince
</h1>

<p>
Infraestructura en Transporte de Carga
</p>

</div>


</div>



<div class="document-meta-block">

<h2>
Ficha Digital
</h2>


<p>
<?= $folio ?>
</p>


</div>


</header>

          
<div class="process-step-title">
Información del Solicitante
</div>


<div class="financial-grid-dashboard">


<div class="dashboard-metric-card" style="grid-column:span 2;text-align:left;">

<div class="metric-label">
Cliente
</div>

<div class="metric-value">
<?= htmlspecialchars($pago['nombre_cliente'] ?? 'N/A') ?>
</div>

</div>



<div class="dashboard-metric-card">

<div class="metric-label">
Servicio
</div>

<div class="metric-value">
F-<?= htmlspecialchars($pago['cotizacion_id']) ?>
</div>

</div>


</div>





<div class="process-step-title">
Resumen del Pago
</div>



<div class="financial-grid-dashboard">


<div class="dashboard-metric-card">

<div class="metric-label">
Monto Total
</div>

<div class="metric-value amount-large">

$<?= $monto_formateado ?>

</div>

</div>



<div class="dashboard-metric-card">

<div class="metric-label">
Estatus
</div>


<div class="metric-value">

<?= strtoupper($pago['status']) ?>

</div>


</div>



<div class="dashboard-metric-card">

<div class="metric-label">
Fecha
</div>


<div class="metric-value">

<?= date('d/m/Y',strtotime($fecha_creacion)) ?>

</div>


</div>



</div>





<?php if($mostrar_spei): ?>


<div class="process-step-title">
Transferencia SPEI
</div>


<div class="payment-method-panel">


<div class="panel-top-header">
DATOS BANCARIOS
</div>



<div class="info-data-line">

<div class="info-label">
Banco
</div>

<div class="info-value">
STP
</div>

</div>




<div class="info-data-line">

<div class="info-label">
Beneficiario
</div>

<div class="info-value">
Fletes y Mudanzas El Lince
</div>

</div>




<div class="info-data-line">

<div class="info-label">
CLABE
</div>


<div class="info-value">

<div class="clabe-box">

<?= htmlspecialchars($clabe) ?>

</div>

</div>

</div>




<div class="info-data-line">

<div class="info-label">
Concepto
</div>


<div class="info-value">

<?= htmlspecialchars($referencia_spei) ?>

</div>


</div>



</div>


<?php endif; ?>







<?php if($mostrar_paynet): ?>



<div class="process-step-title">

Pago en Comercios Paynet

</div>



<div class="payment-method-panel">


<div class="panel-top-header">

REFERENCIA PAYNET

</div>



<div class="paynet-layout-flex">


<div>

<p>
Muestre esta referencia al cajero.
</p>


<p>
Indique pago mediante:
<strong>Paynet</strong>
</p>


<p>
Disponible en comercios afiliados.
</p>


</div>




<div class="barcode-card-container">


<svg id="barcode"></svg>



<div style="
font-family:monospace;
font-weight:bold;
margin-top:8px;
">

<?= htmlspecialchars($paynet) ?>


</div>


</div>



</div>


</div>



<?php endif; ?>




<div class="process-step-title">

Información Fiscal

</div>



<table class="corporate-fiscal-table">


<tr>

<td class="cell-label">
Razón Social
</td>


<td>
<?= htmlspecialchars(
$pago['razon_social']
?? 
'LAURA ALEJANDRA CEDILLO FLORES'
) ?>
</td>


</tr>



<tr>

<td class="cell-label">
RFC
</td>


<td>

<?= htmlspecialchars(
$pago['rfc']
??
'CEFL950210513'
) ?>

</td>


</tr>




<tr>

<td class="cell-label">
Régimen
</td>


<td>

<?= htmlspecialchars(
$pago['regimen']
??
'RESICO'
) ?>


</td>


</tr>


</table>




<footer class="footer">

Pago procesado mediante Openpay.
El sistema registrará automáticamente la confirmación.

</footer>



</div>

</div>

   <script>

document.addEventListener("DOMContentLoaded", function () {

    const barcode = document.getElementById("barcode");

    const referenciaPaynet = "<?= trim($paynet ?? '') ?>";


    if (barcode && referenciaPaynet !== "") {

        JsBarcode("#barcode", referenciaPaynet, {
            format: "CODE128",
            width: 2,
            height: 60,
            displayValue: false,
            margin: 10
        });

    }

});



async function compartirPDF() {

    const btn = document.getElementById("compartir-btn");

    btn.classList.add("loading");

    const element = document.getElementById("a4-content");
    
    const pageWrapper = document.querySelector(".page-wrapper");

    try {

        // Guardar scale actual (dinámico)
        const originalTransform = pageWrapper.style.transform;
        
        // Remover escala ANTES de capturar
        pageWrapper.style.transform = "none";

        // Obtener dimensiones reales del elemento
        const elementHeight = element.scrollHeight;
        const elementWidth = element.scrollWidth;
        
        // Esperar a que todo esté renderizado
        await new Promise(resolve => setTimeout(resolve, 100));
        
        const canvas = await html2canvas(element, {
            scale: 3,
            useCORS: true,
            allowTaint: true,
            backgroundColor: "#ffffff",
            logging: false,
            windowHeight: elementHeight,
            windowWidth: elementWidth,
            onclone: (clonedDocument) => {
                // Asegurar que no hay overflow oculto en el clon
                const clonedElement = clonedDocument.getElementById("a4-content");
                if (clonedElement) {
                    clonedElement.style.height = "auto";
                    clonedElement.style.overflow = "visible";
                }
            }
        });

        // Restaurar escala visual
        pageWrapper.style.transform = originalTransform;

        const imgData = canvas.toDataURL("image/png");

// ... dentro de la función compartirPDF, después de generar el canvas ...

const { jsPDF } = window.jspdf;

// 1. Configuración del ancho del ticket (80mm)
const ticketWidth = 80; 

// 2. Calcular la altura dinámica basada en el ratio de la imagen (canvas)
const canvasWidth = canvas.width;
const canvasHeight = canvas.height;
const imgRatio = canvasWidth / canvasHeight;
const ticketHeight = ticketWidth / imgRatio;

// 3. Crear el PDF con tamaño personalizado [ancho, alto]
const pdf = new jsPDF("p", "mm", [ticketWidth, ticketHeight]);

// 4. Añadir la imagen ajustada al tamaño exacto del PDF
pdf.addImage(
    canvas.toDataURL("image/png"),
    "PNG",
    0,      // x: alineado a la izquierda
    0,      // y: alineado arriba
    ticketWidth, 
    ticketHeight
);

const blob = pdf.output("blob");

// ... el resto de tu lógica para crear el archivo y compartir ...


        const archivo = new File(
            [blob],
            "Orden_Lince_<?= $pago['id'] ?>.pdf",
            {
                type:"application/pdf"
            }
        );

        // Compartir Android

        if (
            navigator.share &&
            navigator.canShare &&
            navigator.canShare({
                files:[archivo]
            })
        ) {

            await navigator.share({

                files:[archivo],

                title:"Orden de pago El Lince",

                text:"Ficha de pago"

            });

        } else {

            // respaldo

            pdf.save(
                "Orden_Lince_<?= $pago['id'] ?>.pdf"
            );

        }

    } catch(error) {

        console.error(error);

        alert(
            "Error generando PDF: " 
            + error.message
        );

        // Restaurar escala en caso de error
        const pageWrapper = document.querySelector(".page-wrapper");
        const originalTransform = pageWrapper.style.transform;
        pageWrapper.style.transform = originalTransform;

    }

    btn.classList.remove("loading");

}
function obtenerTextoPago(){

return `

<?php if($mostrar_spei): ?>

🏦 *Método:* Transferencia SPEI
    
    
Beneficiario:
Fletes y Mudanzas El Lince

CLABE:
*<?= $clabe ?>*

<?php endif; ?>


`;
}



async function copiarPago(){

const texto = obtenerTextoPago();

await navigator.clipboard.writeText(texto);

alert("Datos de pago copiados");

}



function enviarCliente(){

const texto = encodeURIComponent(obtenerTextoPago());

window.open(
"https://wa.me/?text="+texto,
"_blank"
);

}

</script>


</body>

</html>
