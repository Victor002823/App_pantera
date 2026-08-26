<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>  
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>      


<script>
const rolUsuario = "<?= $_SESSION['rol'] ?? '' ?>";

let celdaNotasCotizaciones = null;
let tableCotizaciones; // global


function exportCellValueCotizaciones(data, row, column, node) {
    const $node = $(node);

    if ($node.find('input').length) {
        return $node.find('input').val();
    }
    if ($node.find('select').length) {
        return $node.find('select option:selected').text();
    }
    if ($node.find('.notaInput').length) {
        return $node.find('.notaInput').val();
    }

    // Evita exportar botones o iconos
    if ($node.find('.deleteRow, .facturar, .editNotes, .guardarRow').length) {
        return '';
    }

    // ✔️ Extrae solo el texto visible (sin etiquetas HTML)
    return $node.text().trim();
}
</script>

  
  <script src="/view/home/js/tables/tablas_facturaciones.js"></script>

<script>            
/**
 * SCRIPT DE INTERCEPCIÓN INDEPENDIENTE (CON CSS INTEGRADO)
 * Renderiza SweetAlert2 dentro del modal y aplica estilos independientes a los botones.
 */
(function() {
    // INYECTAR ESTILOS CSS AL DOM DE FORMA DINÁMICA
    const estilosClases = document.createElement('style');
    estilosClases.innerHTML = `
        /* Contenedor de la alerta personalizado */
        .mazer-swal-popup {
            border-radius: 12px !important;
            padding: 2rem !important;
            font-family: 'Nunito', sans-serif !important;
        }
        /* Input personalizado de la alerta */
        .mazer-swal-input {
            border: 2px solid #e5e9f2 !important;
            border-radius: 6px !important;
            font-size: 16px !important;
            padding: 10px 15px !important;
            transition: border-color 0.3s ease !important;
            box-shadow: none !important;
        }
        .mazer-swal-input:focus {
            border-color: #435ebe !important;
        }
        /* Botón de Confirmar / Guardar */
        .mazer-swal-btn-confirm {
            background-color: #435ebe !important; /* Azul Mazer */
            color: #ffffff !important;
            font-weight: 600 !important;
            padding: 12px 30px !important;
            font-size: 15px !important;
            border-radius: 6px !important;
            border: none !important;
            box-shadow: 0 4px 12px rgba(67, 94, 190, 0.3) !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            justify-content: center !important;
            align-items: center !important;
            text-align: center !important;
        }
        .mazer-swal-btn-confirm:hover {
            background-color: #374fa0 !important;
            transform: translateY(-1px);
        }
        /* Botón de Cancelar */
        .mazer-swal-btn-cancel {
            background-color: transparent !important;
            color: #4e5e7a !important;
            font-weight: 600 !important;
            padding: 12px 24px !important;
            font-size: 15px !important;
            border-radius: 6px !important;
            border: none !important;
            transition: all 0.2s ease !important;
        }
        .mazer-swal-btn-cancel:hover {
            background-color: #dcdfe5 !important;
            color: #2d3748 !important;
        }
    `;
    document.head.appendChild(estilosClases);

    // 1. Extraer el nombre del cliente desde la tabla del carrito
    function obtenerClienteActual() {
        const celdaCliente = document.querySelector('#tablaCarrito tbody tr td:first-child');
        return celdaCliente ? celdaCliente.innerText.trim() : null;
    }

    // 2. Manejador centralizado para interceptar los clicks
    async function interceptarAccion(evento) {
        const boton = evento.currentTarget;

        if (boton.dataset.telefonoValidado === "true") {
            return; 
        }

        evento.stopImmediatePropagation();
        evento.preventDefault();

        const cliente = obtenerClienteActual();
        if (!cliente) {
            Swal.fire('Error', 'No hay ningún cliente en el carrito.', 'error');
            return;
        }

        try {
            let respuesta = await fetch(`/view/home/verificar_telefono.php?    cliente=${encodeURIComponent(cliente)}`);
            let resultado = await respuesta.json();

            if (resultado.success) {
                if (resultado.tiene_telefono) {
                    ejecutarAccionOriginal(boton);
                } else {
                    // LEVANTAMOS LA ALERTA DULCE CON LAS CLASES CSS CONFIGURADAS
                    Swal.fire({
                        title: 'Información Requerida',
                        text: `El cliente "${cliente}" no tiene un número telefónico registrado. Por favor, ingrésalo para continuar:`,
                        input: 'text',
                        inputPlaceholder: 'Número de teléfono (Ej. 5512345678)',
                        icon: 'warning',
                        showCancelButton: true,
                        buttonsStyling: false, // Desactiva los estilos en línea por defecto de SweetAlert2
                        
                        // EVITAR CIERRE POR ACCIDENTE
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        
                        // INYECCIÓN DE CLASES INDEPENDIENTES
                        customClass: {
                            popup: 'mazer-swal-popup',
                            input: 'mazer-swal-input',
                            confirmButton: 'mazer-swal-btn-confirm',
                            cancelButton: 'mazer-swal-btn-cancel'
                        },
                        
                        focusConfirm: false,
                        target: document.getElementById('modalCarrito'),
                        
                        // DETENER PROPAGACIÓN DE CLICS EN EL INPUT HACIA BOOTSTRAP
                        didOpen: () => {
                            const contenedorAlerta = Swal.getPopup();
                            if (contenedorAlerta) {
                                const frenarEvento = (e) => e.stopPropagation();
                                contenedorAlerta.addEventListener('click', frenarEvento);
                                contenedorAlerta.addEventListener('mousedown', frenarEvento);
                                contenedorAlerta.addEventListener('touchstart', frenarEvento, { passive: true });
                            }
                        },
                        
                        inputValidator: (value) => {
                            if (!value || value.trim() === "") {
                                return '¡El número de teléfono es obligatorio!';
                            }
                            if (isNaN(value.trim())) {
                                return 'Ingresa un número telefónico válido sin letras.';
                            }
                        }
                    }).then(async (result) => {
                        if (result.isConfirmed && result.value) {
                            const telefonoIntroducido = result.value.trim();

                            let guardarRespuesta = await fetch('/view/home/verificar_telefono.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ cliente: cliente, telefono: telefonoIntroducido })
                            });
                            let guardarResultado = await guardarRespuesta.json();

                            if (guardarResultado.success) {
                                Swal.fire({
                                    title: '¡Registrado!',
                                    text: 'El teléfono se guardó en la BD con éxito.',
                                    icon: 'success',
                                    buttonsStyling: false,
                                    customClass: {
                                        popup: 'mazer-swal-popup',
                                        confirmButton: 'mazer-swal-btn-confirm'
                                    },
                                    target: document.getElementById('modalCarrito')
                                }).then(() => {
                                    ejecutarAccionOriginal(boton);
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: 'No se pudo guardar el teléfono: ' + guardarResultado.error,
                                    icon: 'error',
                                    target: document.getElementById('modalCarrito')
                                });
                            }
                        }
                    });
                }
            } else {
                Swal.fire({
                    title: 'Error del Servidor',
                    text: resultado.error,
                    icon: 'error',
                    target: document.getElementById('modalCarrito')
                });
            }
        } catch (error) {
            console.error(error);
            Swal.fire({
                title: 'Error',
                text: 'Hubo un problema de conexión con el servidor.',
                icon: 'error',
                target: document.getElementById('modalCarrito')
            });
        }
    }

    // 3. Simula de nuevo el click una vez aprobada la validación
    function ejecutarAccionOriginal(boton) {
        boton.dataset.telefonoValidado = "true"; 
        boton.click();                           
        
        setTimeout(() => {
            boton.removeAttribute('data-telefono-validado');
        }, 500);
    }

    // 4. Enganchar la intercepción prioritariamente en el DOM
    function inicializarInterceptores() {
        const botonesAccion = ['btnGenerarPDF', 'btnAgregarLibre', 'btnGuardarCarrito'];
        
        botonesAccion.forEach(id => {
            const boton = document.getElementById(id);
            if (boton) {
                boton.addEventListener('click', interceptarAccion, true);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarInterceptores);
    } else {
        inicializarInterceptores();
    }
})();



</script>    
