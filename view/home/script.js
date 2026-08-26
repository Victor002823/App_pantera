

// Forzar la muerte del Service Worker con delay para romper el bucle
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(function(registrations) {
        if (registrations.length > 0) {
            for (let registration of registrations) {
                registration.unregister().then(function( boolean) {
                    if (boolean) {
                        console.log("SW eliminado.");
                        setTimeout(() => {
                            window.location.reload();
                        }, 500); // Medio segundo para limpiar caché limpia
                    }
                });
            }
        }
    });
}
// --- Envío del formulario con protección contra doble-click / doble-listener ---
(function () {
    const btnGuardar = document.querySelector('#next button');
    if (!btnGuardar) return;

    if (btnGuardar.dataset.listenerGuardarAttached === "true") return;
    btnGuardar.dataset.listenerGuardarAttached = "true";

    btnGuardar.addEventListener('click', function () {
        // 🔍 DEBUG TEMPORAL: muestra de dónde viene cada click/llamada
        console.trace("CLICK guardar_servicio disparado");

        if (btnGuardar.disabled) {
            console.warn("⚠️ Click ignorado: botón ya deshabilitado (segundo click detectado)");
            return;
        }

        const form = document.getElementById('cotizacionForm');
        const formData = new FormData(form);

        // 🔍 DEBUG TEMPORAL: imprime todos los datos que se están enviando
        console.log("📦 Payload a enviar:", Object.fromEntries(formData.entries()));

        btnGuardar.disabled = true;
        const textoOriginal = btnGuardar.textContent;
        btnGuardar.textContent = 'Guardando...';

        fetch('/view/home/guardar_servicio.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log("✅ Respuesta del servidor:", data);
            if (data.success) {
                mostrarAlerta('Servicio guardado correctamente. ID: ' + data.id, 'exito');
                form.reset();
            } else {
                mostrarAlerta('Error al guardar: ' + data.error, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            mostrarAlerta('Ocurrió un error al guardar el servicio.', 'error');
        })
        .finally(() => {
            btnGuardar.disabled = false;
            btnGuardar.textContent = textoOriginal;
        });
    });

    console.log("✅ Listener de guardar registrado. Botón:", btnGuardar);
})();

document.addEventListener("DOMContentLoaded", () => {

  const pages = document.querySelectorAll('.page');
  const links = document.querySelectorAll('.nav a');
  const indicator = document.querySelector('.indicator');
  const container = document.querySelector('.container-fluid');

  if (!links.length || !indicator) return;

  function moveIndicator(el) {
    const rect = el.getBoundingClientRect();
    const navRect = el.parentElement.getBoundingClientRect();

    indicator.style.width = rect.width + "px";
    indicator.style.left = (rect.left - navRect.left) + "px";
  }

  links.forEach(link => {
    link.addEventListener('click', () => {

      pages.forEach(p => p.classList.remove('active'));
      links.forEach(l => l.classList.remove('active'));

      const target = link.dataset.page;

      if (container) {
        container.style.display =
          (target === "form" || target === "factura")
            ? "none"
            : "block";
      }

      const page = document.getElementById(target);
      if (page) page.classList.add('active');

      link.classList.add('active');
      moveIndicator(link);
    });
  });

  const active = document.querySelector('.nav a.active');
  if (active) moveIndicator(active);

  window.addEventListener('load', () => {
    const active = document.querySelector('.nav a.active');
    if (active) moveIndicator(active);
  });

  window.addEventListener('resize', () => {
    const active = document.querySelector('.nav a.active');
    if (active) moveIndicator(active);
  });

});


document.getElementById('btnSiguiente').addEventListener('click', () => {
    // Validar nombre
    const nombre = document.getElementById('nombre').value.trim();
    if (!nombre) {
        alert('Por favor ingresa el nombre del cliente.');
        return;
    }

    // Validar destino (radio obligatorio)
    const destino = document.querySelector('input[name="destino"]:checked');
    if (!destino) {
        alert('Por favor selecciona un destino.');
        return;
    }

    // Oculta la sección actual
    document.querySelector('.page.active').classList.remove('active');
    
    // Muestra la sección control
    document.getElementById('control').classList.add('active');

    // Refresca la lista de cotizaciones al entrar a "control"
    // (usa la función que ya tienes en cotizaciones-fixed.js)
    if (typeof inicializar === 'function') {
        inicializar();
    }
});

// Lógica al hacer click en un botón facturar
$('#tablaFacturaciones').on('click', '.generarPDF', function() {
    // 1️⃣ Quita active de todos los botones facturar
    $('#tablaFacturaciones .generarPDF').removeClass('active');

    // 2️⃣ Agrega active al botón que se clickeó
    $(this).addClass('active');

    // 3️⃣ Oculta la sección actualmente activa
    document.querySelector('.page.active').classList.remove('active');

    // 4️⃣ Muestra la sección #generar
    document.getElementById('generar').classList.add('active');
});
