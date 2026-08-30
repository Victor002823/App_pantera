<script src="https://apis.google.com/js/api.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
async function obtenerFolio() {
    try {
        const res = await fetch('/view/home/getFolio.php');
        const data = await res.json();
        return data.folio || 'F-00001';
    } catch (err) {
        console.error(err);
        return 'F-00001';
    }
}

async function generarPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'pt', 'a4');

    const logo = new Image();
    logo.src = "/view/home/logo1023.png";

    logo.onload = async function() {
        const logoWidth = 80;
        const logoHeight = 80;
        doc.addImage(logo, 'PNG', 10, 30, logoWidth, logoHeight);

        const hoy = new Date();
        const fechaActual = `${String(hoy.getDate()).padStart(2,'0')}/${String(hoy.getMonth()+1).padStart(2,'0')}/${hoy.getFullYear()} ${String(hoy.getHours()).padStart(2,'0')}:${String(hoy.getMinutes()).padStart(2,'0')}`;
        const asesor = "<?php echo isset($_SESSION['usuario']['nombre_usuario']) ? $_SESSION['usuario']['nombre_usuario'] : 'Asesor'; ?>";
        const estado = "CDMX";
        let startY = 30 + logoHeight + 10;
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");

        const infoIzquierda = [
            <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>
            "RFC: CEFL950210513",
            "Teléfono: 5540662626",
            "Dirección: Jose Ceballos 60",
            <?php endif; ?>
            "Correo: transportesymudanzaspantera@gmail.com"
        ];

        const folio = await obtenerFolio();
        const infoDerecha = [
            `Asesor: ${asesor}`,
            `Fecha: ${fechaActual}`,
            `Estado: ${estado}`,
            `Folio: ${folio}`
        ];

        const lineHeight = 12;
        infoIzquierda.forEach((line, i) => doc.text(line, 40, startY + 20 + i * lineHeight));
        infoDerecha.forEach((line, i) => doc.text(line, doc.internal.pageSize.getWidth() - 60, startY + 20 + i * lineHeight, { align: 'right' }));

        // Tabla de servicio
        if(carritoPDF.length > 0){
            const primerItem = carritoPDF[0];
            const headersServicio = ["Cliente", "Fecha de Servicio", "Hora de Servicio"];
            const rowServicio = [[primerItem.cliente, primerItem.fechaServicio, primerItem.horaServicio]];

            doc.autoTable({
                head: [headersServicio],
                body: rowServicio,
                startY: startY + 20 + Math.max(infoIzquierda.length, infoDerecha.length) * lineHeight + 10,
                styles: { fontSize: 10, cellPadding: 4 },
                headStyles: { fillColor: [0,0,0], textColor: 255, fontStyle: 'bold', halign:'center' },
                columnStyles: {
                    0: { cellWidth:200, halign:'left' },
                    1: { cellWidth:150, halign:'center' },
                    2: { cellWidth:150, halign:'center' }
                }
            });
        }

        // Tabla de productos
        const headers = ['Id', 'Producto', 'Cantidad', 'Sup.Total', 'Anticipo', 'Total'];
        const rows = [];
        let totalGeneral = 0;

        carritoPDF.forEach(item => {
            const total = parseFloat(item.totalOriginal || 0) - parseFloat(item.anticipo || 0);
            rows.push([item.id, item.producto, item.cantidad, item.totalOriginal, item.anticipo, total.toFixed(2)]);
            totalGeneral += total;
        });
        while(rows.length < 15) rows.push(['','','','','','']);
        rows.push(['','','','Total:', totalGeneral.toFixed(2)]);

        doc.autoTable({
            head: [headers],
            body: rows,
            startY: doc.lastAutoTable.finalY + 15,
            styles: { fontSize: 10, cellPadding: 4 },
            headStyles: { fillColor: [0,0,0], textColor: 255, fontStyle: 'bold', halign:'center' },
            alternateRowStyles: { fillColor: [245,245,245] },
            columnStyles: {
                0: { halign:'center', cellWidth:30 },
                1: { halign:'left', cellWidth:200 },
                2: { halign:'center', cellWidth:50 },
                3: { halign:'right', cellWidth:70 },
                4: { halign:'right', cellWidth:70 },
                5: { halign:'right', cellWidth:80 }
            },
            didParseCell: function(data){
                if(data.row.index === rows.length - 1){
                    data.cell.styles.fillColor = (data.column.index < 4) ? [255,255,255] : [0,0,0];
                    data.cell.styles.textColor = (data.column.index < 4) ? 0 : 255;
                    data.cell.styles.fontStyle = 'bold';
                    data.cell.styles.halign = (data.column.index >= 4) ? 'right' : 'left';
                    data.cell.styles.fontSize = 14;
                }
            }
        });

        // Segunda hoja: Términos y condiciones
        doc.addPage();
        const pageWidth = doc.internal.pageSize.getWidth();
        const logoWidth2 = 90;
        const logoHeight2 = 90;
        const xLogo2 = (pageWidth - logoWidth2) / 1.8;
        const yLogo2 = 60;
        doc.addImage(logo, "PNG", xLogo2, yLogo2, logoWidth2, logoHeight2);

        doc.setFont("helvetica", "bold");
        doc.setFontSize(16);
        doc.text("TÉRMINOS Y CONDICIONES", pageWidth / 2, yLogo2 + logoHeight2 + 20, { align: "center" });

        doc.setFont("helvetica", "normal");
        doc.setFontSize(11);

        const terminos = `Servicios de mudanza: La Empresa se compromete a proporcionar el servicio de mudanza al Cliente, que incluirán el transporte de los bienes del Cliente desde la dirección de origen hasta la dirección de destino (No se transportan personas), si el operador de la unidad considera que las vialidades no son aptas para la unidad, este mismo tiene autorizado dar por terminado el servicio de mudanza.

Fecha y hora de la mudanza: La mudanza se llevará a cabo en la fecha y hora acordada entre ambas partes, siempre considerando que la llegada de la misma puede ser afectada por factores externos como exceso de tráfico, accidente etc.

Tiempo de espera: Se establece un tiempo de espera máximo de 30min para poder iniciar la maniobra de carga, así como de la misma manera al llegar al destino para proceder a la descarga; al excederse el tiempo de espera se cobrará una tarifa del 10% del costo total de la mudanza por cada 30min ,(tiempo de espera no mayor a tres horas), al excederse este periodo se penalizará con el 100% del costo total de la mudanza.

Listado de bienes a ser trasladados: El Cliente proporcionará a La Empresa un inventario detallado de todos los bienes que serán trasladados, incluyendo muebles, electrodomésticos, cajas, etc. El Cliente garantiza que el inventario es preciso y completo.

Pagos y Anticipos: Para confirmar el servicio, el Cliente deberá realizar un anticipo equivalente al 50% del costo total de la mudanza, el cual deberá ser pagado al momento de la contratación.
Este anticipo garantiza la reserva de la unidad y el personal necesario para la mudanza.
En caso de cancelación por parte del Cliente deberá realizarse con al menos 8 horas de anticipación, excedido este tiempo el anticipo no será reembolsable.
Si la Empresa cancela el servicio por causas atribuibles a ella, el anticipo será reembolsado en su totalidad.
El Cliente acuerda pagar a La Empresa la cantidad acordada por los servicios de mudanza, El pago se liquida antes de la descarga de los bienes.

Responsabilidad y seguro: La Empresa se compromete a tomar las precauciones necesarias para garantizar la seguridad de los bienes del Cliente durante la mudanza. Sin embargo, el Cliente reconoce que es recomendable contratar un seguro adicional para cubrir cualquier eventualidad durante la mudanza.

Cancelación: Cualquiera de las partes podrá cancelar mediante notificación por escrito a la otra parte con al menos 8 horas de anticipación.

El Cliente, declara haber leído, entendido y aceptado todos los términos y condiciones establecidos.`; // tu texto completo de términos

        doc.text(terminos, pageWidth / 2, yLogo2 + logoHeight2 + 50, { align: "center", maxWidth: 500 });

        // ===== Compartir PDF =====
        const pdfBlob = doc.output("blob");
        const reader = new FileReader();
        reader.onload = function() {
            const base64Data = reader.result.split(',')[1];
            const fileName = `Notafactura-${folio}.pdf`;

            if(window.Android && window.Android.sharePdf){
                alert("Generando PDF en Android..."); // ✅ alerta Android
                window.Android.sharePdf(base64Data, fileName);
            } else if(navigator.canShare && navigator.canShare({ files: [new File([pdfBlob], fileName, {type:'application/pdf'})] })){
                alert("Generando PDF en navegador compatible con share..."); // ✅ alerta navegador
                navigator.share({ files:[new File([pdfBlob], fileName, {type:'application/pdf'})], title:"Notafactura", text:"Adjunto la cotización en PDF." });
            } else {
                alert("Descargando PDF automáticamente..."); // ✅ alerta fallback
                const url = URL.createObjectURL(pdfBlob);
                const a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
        };
        reader.readAsDataURL(pdfBlob);
    };
}

document.getElementById('btnGeneraPDF').addEventListener('click', generarPDF);
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('cotizacionForm');

  form.addEventListener('submit', function(e) {
    e.preventDefault();

    const elegido = form.querySelector('input[name="destino"]:checked');
    if (!elegido) {
      alert('Selecciona Local o Foráneo.');
      return;
    }

    // 👉 Guardar en variable global
    window.destinoSeleccionado = elegido.value; // "local" o "foraneo"

    // Ajusta rutas según tu estructura real
    const urlLocal = 'panel_control.php';
    const urlForaneo = 'panel_control_foraneo.php';

    // elegir url
    const destino = elegido.value; 
    form.action = destino === 'foraneo' ? urlForaneo : urlLocal;
    form.method = 'POST';

    // submit normal -> redirige a la página PHP
    form.submit();
  });
});
// Función para mostrar el modal toast
// Estado para cada sección
let toastShown = {
    cotizaciones: false,
    facturaciones: false
};



</script>