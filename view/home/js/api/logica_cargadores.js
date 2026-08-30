let cargadoresCost = 0;
// Reemplaza/actualiza la función updateTotal por una versión definitiva
function updateTotal() {

  let costoCamionetas = 0;
  let detalleCamionetas = [];

  document.querySelectorAll('input[name="camioneta"]:checked')
    .forEach(input => {

      const card = input.closest('.camioneta-card');

      if (!card) return;

      const titulo = card.querySelector('h4')?.textContent || 'Camioneta';

      const textoCosto = card.querySelector('.precio')?.textContent || '';

      const costo = parseFloat(
        textoCosto
          .replace(/[^\d.,]/g, '')
          .replace(/,/g, '')
      ) || 0;

      costoCamionetas += costo;

      detalleCamionetas.push(`
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
          <span>${titulo}</span>
          <strong>$${costo.toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          })}</strong>
        </div>
      `);
    });

  const total = costoCamionetas + cargadoresCost;

  const resumen = document.getElementById("resumenCostos");

  if (resumen) {

    resumenCostos.innerHTML = `

      <div style="background-color:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.08); border-radius:20px; padding:20px;">

        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
          <div style="width:40px; height:40px; background-color:rgba(255,255,255,0.08); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
            🚚
          </div>
          <div>
            <div style="font-weight:700; color:#f1f5f9; font-size:15px;">Camionetas seleccionadas</div>
          </div>
        </div>

        <div style="background-color:rgba(0,0,0,0.2); border-radius:14px; padding:4px 14px; margin-bottom:16px; color:#e2e8f0;">
          ${
            detalleCamionetas.length
              ? detalleCamionetas.join('')
              : `
                <div style="color:rgba(255,255,255,0.4); padding:12px 0; font-size:13px; text-align:center;">
                  Ninguna camioneta seleccionada
                </div>
              `
          }
        </div>

        <div style="border-top:1px solid rgba(255,255,255,0.08); padding-top:14px; margin-bottom:14px;">

          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <span style="color:rgba(255,255,255,0.6); font-size:13px;">Total camionetas</span>
            <strong style="color:#f1f5f9; font-size:14px;">
              $${costoCamionetas.toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
              })}
            </strong>
          </div>

          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span style="color:rgba(255,255,255,0.6); font-size:13px;">Cargadores</span>
            <strong style="color:#f1f5f9; font-size:14px;">
              $${cargadoresCost.toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
              })}
            </strong>
          </div>

        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid rgba(255,255,255,0.08); padding-top:14px;">
          <div style="font-weight:700; color:#f1f5f9; font-size:14.5px;">Total a pagar</div>
          <div style="font-weight:800; color:#34d399; font-size:22px; letter-spacing:-0.02em;">
            $${total.toLocaleString('es-MX', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
            })}
          </div>
        </div>

      </div>

    `;
  }

  const totalInput = document.getElementById("totalInput");
  if (totalInput) {
    totalInput.value = total.toFixed(2);
  }

  const totales = document.getElementById("totales");
  if (totales) {
    totales.value = costoCamionetas.toFixed(2);
  }
  // Guardar camionetas seleccionadas en input oculto
const camionetaInput = document.getElementById("camionetaSeleccionadaInput");

if (camionetaInput) {

  const camionetasSeleccionadas = [];

  document.querySelectorAll('input[name="camioneta"]:checked')
    .forEach(input => {
      camionetasSeleccionadas.push(input.value);
    });

  camionetaInput.value = camionetasSeleccionadas.join(' > ');
}

}


// Mostrar el botón "Siguiente" solo al llegar al final de la página
window.addEventListener('scroll', function() {
  const buttonContainer = document.getElementById('nextButtonContainer');
  if (!buttonContainer) return;
  if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 20) {
    buttonContainer.classList.add('show');
  } else {
    buttonContainer.classList.remove('show');
  }
});

document.addEventListener("DOMContentLoaded", function() {
    const btnMostrar = document.getElementById('btnMostrar');
    const popup = document.getElementById('popup');
    const popupBackground = document.getElementById('popupBackground');
    const closePopup = document.getElementById('closePopup');
    const formulario = document.getElementById('formulario');
    const resultadoTitulo = document.getElementById('resultadoTitulo');
    const resultado = document.getElementById('resultado');

    // Mostrar popup
    if (btnMostrar) {
        btnMostrar.addEventListener('click', function() {
            popup.style.display = 'block';
            popupBackground.style.display = 'block';
        });
    }

    // 🔹 Cerrar SIN limpiar (Esc o clic fondo)
    function closeOnlyPopup() {
        popup.style.display = 'none';
        popupBackground.style.display = 'none';
    }

    // 🔹 Cerrar Y limpiar (solo botón Cerrar)
    function closeAndReset() {
        popup.style.display = 'none';
        popupBackground.style.display = 'none';
        formulario.reset();
        resultado.textContent = '0';
        resultadoTitulo.style.display = 'none';
    }

    // Botón "Cerrar" → limpia
    closePopup.addEventListener('click', closeAndReset);

    // Fondo o tecla Esc → conserva
    popupBackground.addEventListener('click', closeOnlyPopup);
    document.addEventListener('keydown', e => { if (e.key === "Escape") closeOnlyPopup(); });
});

// 👉 Tu cálculo original
function calcularCosto(event) {
    event.preventDefault();

    const base = parseFloat(document.getElementById('base').value) || 0;
    const kmRecorrer = parseFloat(document.getElementById('kmRecorrer').value);
    const kmxL = parseFloat(document.getElementById('kmxL').value);
    const costoCombustible = parseFloat(document.getElementById('costoCombustible').value);
    const casetas = parseFloat(document.getElementById('casetas').value) || 0;

    if (isNaN(kmRecorrer) || isNaN(kmxL) || isNaN(costoCombustible) || kmRecorrer <= 0 || kmxL <= 0 || costoCombustible <= 0) {
        alert("Por favor, ingresa valores válidos.");
        return;
    }

    // ✅ Fórmula original
    const litrosNecesarios = kmRecorrer / kmxL;
    const costoCombustibleTotal = litrosNecesarios * costoCombustible;

    let costoConBase = costoCombustibleTotal + base;
    costoConBase *= 3; // multiplicador 200% extra
    const costoTotal = costoConBase + casetas;

    // Mostrar en el popup
    document.getElementById('resultado').textContent = costoTotal.toFixed(2);
    document.getElementById('resultadoTitulo').style.display = 'block';
}

async function generarPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();

  let asesor = "";
  let estado = "Cotización";

  // 👉 Fecha automática con hora
  let hoy = new Date();
  let dia = String(hoy.getDate()).padStart(2, "0");
  let mes = String(hoy.getMonth() + 1).padStart(2, "0");
  let anio = hoy.getFullYear();
  let horas = String(hoy.getHours()).padStart(2, "0");
  let minutos = String(hoy.getMinutes()).padStart(2, "0");
  let fecha = `${dia}/${mes}/${anio} ${horas}:${minutos}`;

  // Datos de inputs
  let direccionOrigen = (document.getElementById("input2").value || "").trim();
  let direccionDestino = (document.getElementById("input3").value || "").trim();
  let numCargadores = parseInt(document.getElementById("numCargadores")?.value) || 0;

  // Objetos
  let origen = { concepto: `Origen: ${direccionOrigen}` };
  let destino = { concepto: `Destino: ${direccionDestino}` };
  let cargadores = { concepto: "Cargadores", cantidad: numCargadores };

  // Encabezado
  const encabezadoY = 30;
doc.setFontSize(12);
doc.text("RFC: CEFL950210513", 15, encabezadoY);
doc.text("Teléfono: 5540662626", 15, encabezadoY + 5);
doc.text("Dirección: Jose Ceballos 60", 15, encabezadoY + 10);
doc.text("Correo: transportesymudanzaspantera@gmail.com", 15, encabezadoY + 20);

doc.setFontSize(12);
doc.text(`Asesor: ${asesor}`, 150, encabezadoY);
doc.text(`Fecha: ${fecha}`, 150, encabezadoY + 5);
doc.text(`Estado: ${estado}`, 150, encabezadoY + 10);

  // 🟦 Tabla 1: Origen y Destino
  doc.autoTable({
    startY: 70,
    head: [["Direcciones"]],
    body: [
      [origen.concepto],
      [destino.concepto],
    ],
    styles: {
      fontSize: 10,
      cellPadding: 3,
      lineWidth: 0.1,
      lineColor: [0, 0, 0],
    },
    headStyles: {
      fillColor: [0, 6, 94],
      textColor: [232, 232, 232],
      fontStyle: "bold",
    },
    bodyStyles: {
      fillColor: [255, 255, 255],
    },
    alternateRowStyles: {
      fillColor: [245, 245, 245],
    },
    columnStyles: {
      0: { halign: "left" },
    },
  });

  let finalY1 = doc.lastAutoTable.finalY + 10;

  // 🟩 Tabla 2: Cargadores con cantidad
  doc.autoTable({
    startY: finalY1,
    head: [["Producto", "Cantidad"]],
    body: [
      [cargadores.concepto, cargadores.cantidad],
    ],
    styles: {
      fontSize: 10,
      cellPadding: 3,
      lineWidth: 0.1,
      lineColor: [0, 0, 0],
    },
    headStyles: {
      fillColor: [0, 6, 94],
      textColor: [232, 232, 232],
      fontStyle: "bold",
    },
    bodyStyles: {
      fillColor: [255, 255, 255],
    },
    alternateRowStyles: {
      fillColor: [245, 245, 245],
    },
    columnStyles: {
      0: { halign: "left" },
      1: { halign: "center" },
    },
  });

  let finalY2 = doc.lastAutoTable.finalY;

  // Total
  // Obtener y formatear el total
let totalNumero = parseFloat(document.getElementById("totalInput").value) || 0;

let totalFormateado = totalNumero.toLocaleString("es-MX", {
  style: "currency",
  currency: "MXN",
  minimumFractionDigits: 2,
});

// 💡 Formato visual personalizado
doc.setFontSize(16); // Tamaño grande
doc.setFont("helvetica", "bold"); // Negrita
doc.setTextColor(0, 6, 94); // Azul elegante

// 📌 Imprimir total con formato
doc.text(`Total: ${totalFormateado}`, 150, finalY2 + 20);

// 🧽 Restaurar formato para lo que sigue (opcional)
doc.setFont("helvetica", "normal");
doc.setFontSize(10);
doc.setTextColor(0, 0, 0);

// =======================================================
// FLUXO UNIFICADO PARA COMPARTIR (WEB + APK DE ANDROID)
// =======================================================
const fileName = `Cotizacion-${folio || 'nueva'}.pdf`;

// 1. Convertir el PDF a formato Blob y preparar el lector para Base64
const pdfBlob = doc.output("blob");
const reader = new FileReader();

reader.onload = async function() {
    // Extraemos la cadena limpia de Base64 que tu MainActivity.java necesita
    const base64Data = reader.result.split(',')[1];

    // DETECCIÓN: ¿Estamos dentro de tu APK de Android?
    if (window.Android && window.Android.sharePdf) {
        // Ejecuta el menú nativo de Android (WhatsApp, Gmail, etc.) sin usar el File de JS
        window.Android.sharePdf(base64Data, fileName);
    } 
    // DETECCIÓN: Navegadores web tradicionales en PC/Móvil compatibles
    else if (navigator.canShare && navigator.canShare({ files: [new File([pdfBlob], fileName, { type: 'application/pdf' })] })) {
        try {
            const file = new File([pdfBlob], fileName, { type: "application/pdf" });
            await navigator.share({
                files: [file],
                title: "Cotización",
                text: "Adjunto la cotización en PDF."
            });
        } catch (error) {
            console.error("Error al compartir en navegador:", error);
            // Si el usuario cancela o falla el share, se descarga automáticamente como respaldo
            descargarPdfAlternativo(pdfBlob, fileName);
        }
    } 
    // RESPALDO: Navegadores antiguos (Descarga directa)
    else {
        descargarPdfAlternativo(pdfBlob, fileName);
    }
};

// Disparar la lectura del archivo
reader.readAsDataURL(pdfBlob);

// Función de respaldo para navegadores que no soportan Share API
function descargarPdfAlternativo(blob, name) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = name;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
};

