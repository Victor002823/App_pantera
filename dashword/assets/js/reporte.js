// ==========================================
// UTILIDAD DE ALERTAS (NATIVA CON SVG - INFALIBLE)
// ==========================================
const CustomAlert = {
    show: function({ title, text, icon = 'info', showCancelButton = false, confirmButtonText = 'OK', cancelButtonText = 'Cancelar', confirmButtonColor = 'blue' }) {
        return new Promise((resolve) => {
            // Eliminar alerta previa si existe por seguridad
            const existingAlert = document.getElementById('native-custom-alert');
            if (existingAlert) existingAlert.remove();

            // Configurar colores y crear el icono SVG
            let iconBg = '#e0e7ff';
            let iconSvg = `<svg style="width: 2rem; height: 2rem; color: #4f46e5;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`; // Info por defecto

            if (icon === 'success') { 
                iconBg = '#d1fae5'; 
                iconSvg = `<svg style="width: 2rem; height: 2rem; color: #059669;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>`; 
            }
            if (icon === 'warning') { 
                iconBg = '#ffedd5'; 
                iconSvg = `<svg style="width: 2rem; height: 2rem; color: #ea580c;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>`; 
            }
            if (icon === 'error') { 
                iconBg = '#fee2e2'; 
                iconSvg = `<svg style="width: 2rem; height: 2rem; color: #dc2626;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>`; 
            }

            let btnBg = '#2563eb', btnHover = '#1d4ed8'; // Azul por defecto
            if (confirmButtonColor === 'red') { btnBg = '#ef4444'; btnHover = '#dc2626'; }

            // Crear el elemento Dialog nativo
            const dialog = document.createElement('dialog');
            dialog.id = 'native-custom-alert';
            
            // Estilos del contenedor principal (reseteo total)
            dialog.style.cssText = `
                padding: 0;
                border: none;
                border-radius: 1rem;
                background: transparent;
                max-width: 24rem;
                width: 90%;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                overflow: visible;
            `;

            // HTML Interno (Se inserta el SVG directamente)
            dialog.innerHTML = `
                <div style="background: white; border-radius: 1rem; padding: 1.5rem; display: flex; flex-direction: column; align-items: center; box-sizing: border-box; font-family: ui-sans-serif, system-ui, sans-serif;">
                    
                    <div style="display: flex; align-items: center; justify-content: center; width: 3.5rem; height: 3.5rem; border-radius: 50%; background-color: ${iconBg}; margin-bottom: 1rem;">
                        ${iconSvg}
                    </div>
                    
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 700; color: #0f172a; text-align: center; line-height: 1.2;">${title}</h3>
                    
                    <div id="alert-scroll-wrap" style="position: relative; width: 100%; margin-bottom: 1.5rem;">
                        <p id="alert-text-content" style="margin: 0; font-size: 0.875rem; color: #64748b; text-align: left; line-height: 1.6; white-space: pre-wrap; max-height: 40vh; overflow-y: auto; width: 100%; box-sizing: border-box;">${text}</p>
                        <div id="alert-scroll-fade" style="display:none; position: absolute; left: 0; right: 0; bottom: 0; height: 2.5rem; background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(255,255,255,0.95) 70%); pointer-events: none; align-items: flex-end; justify-content: center; padding-bottom: 2px; z-index: 5;">
                            <svg style="width: 1.1rem; height: 1.1rem; color: #94a3b8; animation: alertBounce 1.2s infinite;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem; width: 100%; justify-content: center; flex-direction: row;">
                        ${showCancelButton ? `<button id="alert-btn-cancel" style="flex: 1; padding: 0.625rem 1rem; border-radius: 0.75rem; border: none; background: #f1f5f9; color: #475569; font-weight: 600; font-size: 0.875rem; cursor: pointer; transition: background 0.2s; margin: 0;">${cancelButtonText}</button>` : ''}
                        
                        <button id="alert-btn-confirm" style="flex: 1; padding: 0.625rem 1rem; border-radius: 0.75rem; border: none; background: ${btnBg}; color: white; font-weight: 600; font-size: 0.875rem; cursor: pointer; transition: opacity 0.2s; margin: 0;">${confirmButtonText}</button>
                    </div>
                </div>
                <style>
                    /* El backdrop es el fondo oscuro difuminado nativo del navegador */
                    #native-custom-alert::backdrop {
                        background: rgba(15, 23, 42, 0.5) !important;
                        backdrop-filter: blur(4px) !important;
                    }
                    #alert-btn-cancel:hover { background: #e2e8f0 !important; color: #0f172a !important; }
                    #alert-btn-confirm:hover { filter: brightness(0.9) !important; }
                    @keyframes alertBounce {
                        0%, 100% { transform: translateY(0); opacity: 0.6; }
                        50% { transform: translateY(4px); opacity: 1; }
                    }
                </style>
            `;

            document.body.appendChild(dialog);

            const contenidoEl = dialog.querySelector('#alert-text-content');
            const fadeEl = dialog.querySelector('#alert-scroll-fade');

            const actualizarIndicadorScroll = () => {
                if (!contenidoEl || !fadeEl) return;
                const haySobrante = contenidoEl.scrollHeight > contenidoEl.clientHeight + 2;
                const llegoAlFinal = contenidoEl.scrollTop + contenidoEl.clientHeight >= contenidoEl.scrollHeight - 2;
                fadeEl.style.display = (haySobrante && !llegoAlFinal) ? 'flex' : 'none';
            };

            if (contenidoEl) {
                contenidoEl.addEventListener('scroll', actualizarIndicadorScroll);
            }

            // Función de cierre
            const closeAlert = (isConfirmed) => {
                dialog.close(); // Cierra el modal nativo
                dialog.remove(); // Limpia el DOM
                resolve({ isConfirmed });
            };

            // Asignar eventos
            dialog.querySelector('#alert-btn-confirm').addEventListener('click', () => closeAlert(true));
            if (showCancelButton) {
                dialog.querySelector('#alert-btn-cancel').addEventListener('click', () => closeAlert(false));
            }

            // Mostrar el modal usando la API nativa (forza Top Layer)
            dialog.showModal();

            // Detecta si el contenido se pasa del alto visible y, de ser así,
            // muestra la flecha con degradado indicando que hay más para leer.
            // Se usan varias pasadas (rAF + setTimeout) porque en el WebView de
            // Android un solo requestAnimationFrame no siempre alcanza a medir
            // el layout ya calculado del modal (a diferencia de un navegador de escritorio).
            requestAnimationFrame(() => {
                actualizarIndicadorScroll();
                requestAnimationFrame(actualizarIndicadorScroll);
            });
            setTimeout(actualizarIndicadorScroll, 150);
            setTimeout(actualizarIndicadorScroll, 400);
        });
    }
};

// ==========================================
// VER INVENTARIO COMPLETO
// ==========================================
function verInventario(id) {
    const registro = (window._cotizacionesData || []).find(r => r.id == id);
    const texto = (registro && registro.inventario && registro.inventario.trim())
        ? registro.inventario.trim()
        : 'Sin inventario registrado para esta cotización.';

    CustomAlert.show({
        title: `Inventario — Folio #${id}`,
        text: texto,
        icon: 'info'
    });
}

// ==========================================
// ABRIR WHATSAPP
// ==========================================
// Dentro de la app Android usa el puente nativo Android.abrirWhatsapp, que
// abre la app de WhatsApp directo con un Intent explícito. Evita el popup
// del WebView (onCreateWindow) que se dispara con target="_blank" y que no
// sabe manejar la redirección interna de wa.me hacia whatsapp://.
// Fuera de la app (navegador normal), cae al link wa.me de siempre.
function abrirWhatsapp(telefono) {
    if (!telefono) return;
    if (window.Android && typeof window.Android.abrirWhatsapp === 'function') {
        window.Android.abrirWhatsapp(telefono, '');
    } else {
        window.open(`https://wa.me/${telefono.replace(/\D/g, '')}`, '_blank');
    }
}

// ==========================================
// LÓGICA PRINCIPAL DE LA PÁGINA
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    cargarCotizaciones();

    const buscador = document.getElementById("BuscadorCotizaciones");
    const btnLimpiar = document.getElementById("btnLimpiarBusqueda");

    if (buscador) {
        buscador.addEventListener("input", function () {
            let filtro = this.value.toLowerCase().trim();
            let filas = document.querySelectorAll("#tablaCotizaciones tbody tr");
            let visibles = 0;

            if (btnLimpiar) btnLimpiar.classList.toggle("hidden", filtro === "");

            filas.forEach(fila => {
                if (fila.cells.length < 2) return;
                let textoFila = fila.textContent.toLowerCase();
                let mostrar = textoFila.includes(filtro);
                fila.style.display = mostrar ? "" : "none";
                if (mostrar) visibles++;
            });

            const emptyState = document.getElementById("emptyState");
            if (emptyState) emptyState.classList.toggle("hidden", visibles > 0);
        });
    }

    if (btnLimpiar) {
        btnLimpiar.addEventListener("click", () => {
            buscador.value = "";
            buscador.dispatchEvent(new Event('input'));
            buscador.focus();
        });
    }

    document.querySelectorAll('.calcular-total').forEach(input => {
        input.addEventListener('input', actualizarSumaModal);
    });

    const btnSidebar = document.getElementById("toggleSidebarBtn");
    const sidebar = document.getElementById("sidebar") || document.querySelector(".sidebar");

    if (btnSidebar && sidebar) {
        btnSidebar.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            sidebar.classList.toggle("active");
        });

        document.addEventListener("click", (e) => {
            if (window.innerWidth < 1200) { 
                if (!sidebar.contains(e.target) && !btnSidebar.contains(e.target)) {
                    sidebar.classList.remove("active");
                }
            }
        });
    }
});

function cargarCotizaciones() {
    fetch("api_cotizaciones.php") 
        .then(res => res.json())
        .then(data => {
            let tbody = document.querySelector("#tablaCotizaciones tbody");
            tbody.innerHTML = "";

            // Se guarda una copia de los datos para poder consultar el inventario
            // completo al hacer clic, sin depender de insertar el texto crudo
            // dentro de un atributo onclick (evita romperse con comillas/saltos de línea).
            window._cotizacionesData = data.data || [];

            if (data.success && data.data.length > 0) {
                let totalMonto = 0;
                let totalCount = data.data.length;
                let hoyCount = 0;
                let fechaHoy = new Date().toISOString().split('T')[0];
                let filasHTML = [];

                data.data.forEach(row => {
                    let total = parseFloat(row.total) || 0;
                    totalMonto += total;

                    if ((row.fecha_creacion || '').includes(fechaHoy)) hoyCount++;

                    let montoFormato = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
                    let maniobraFormato = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(row.maniobra || 0);

                    let origen = row.direccion_origen || 'Origen no especificado';
                    let destino = row.direccion_destino || 'Destino no especificado';
                    let inventario = row.inventario || '—';

                    filasHTML.push(`
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-4 font-mono font-medium text-xs text-slate-500">#${row.id}</td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">${row.nombre_cliente || 'Sin nombre'}</div>
                                <div class="text-xs text-slate-400 mt-0.5">${row.tipo_servicio || 'Servicio General'}</div>
                            </td>
                            <td class="px-5 py-4">
                                ${row.telefono ? `
                                    <button onclick="abrirWhatsapp('${row.telefono.replace(/\D/g,'')}')" title="Enviar WhatsApp" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-xs font-medium transition border-0 cursor-pointer">
                                        <i class="bi bi-whatsapp"></i>
                                        <span>${row.telefono}</span>
                                    </button>
                                ` : '<span class="text-slate-400">—</span>'}
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-xs space-y-1">
                                    <div class="text-slate-700 font-medium truncate max-w-xs" title="${origen}">
                                        <i class="bi bi-geo-alt-fill text-blue-500 mr-1"></i>${origen}
                                    </div>
                                    <div class="text-slate-500 truncate max-w-xs" title="${destino}">
                                        <i class="bi bi-flag-fill text-orange-500 mr-1"></i>${destino}
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="block truncate max-w-[140px] text-slate-600 text-xs" title="${inventario}">${inventario}</span>
                                    <button onclick="verInventario(${row.id})" title="Ver inventario completo" class="shrink-0 w-7 h-7 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all border-0 cursor-pointer">
                                        <i class="bi bi-eye text-sm"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right font-medium text-slate-600">${maniobraFormato}</td>
                            <td class="px-5 py-4 text-right font-bold text-slate-900">${montoFormato}</td>
                            <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button class="text-blue-600 hover:text-blue-800 font-bold text-xs bg-blue-50 px-4 py-2 rounded-lg hover:bg-blue-100 transition-all cursor-pointer border-0" 
                                            onclick="editar(${row.id})" title="Editar Cotización">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Editar</span>
                                    </button>
                                    <button class="text-red-600 hover:text-red-800 font-bold text-xs bg-red-50 px-4 py-2 rounded-lg hover:bg-red-100 transition-all cursor-pointer border-0"
                                            onclick="eliminar(${row.id})" title="Eliminar Cotización">
                                        <i class="bi bi-trash"></i>
                                        <span>Eliminar</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `);
                });

                // Se asigna el HTML completo una sola vez (en vez de += por cada fila),
                // que evita que el navegador re-parsee todo el acumulado en cada iteración
                // (patrón O(n²) que bloqueaba el hilo principal y hacía sentir lento el botón OK)
                tbody.innerHTML = filasHTML.join('');

                document.getElementById("statPendientes").textContent = totalCount;
                document.getElementById("statMontoTotal").textContent = new Intl.NumberFormat('es-MX', {
                    style: 'currency',
                    currency: 'MXN',
                    maximumFractionDigits: 0
                }).format(totalMonto);
                document.getElementById("statHoy").textContent = hoyCount;

                document.getElementById("emptyState").classList.add("hidden");
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="px-5 py-12 text-center text-slate-400">No hay cotizaciones registradas.</td></tr>`;
            }
        })
        .catch(error => {
            console.error(error);
            document.querySelector("#tablaCotizaciones tbody").innerHTML = 
                `<tr><td colspan="8" class="px-5 py-12 text-center text-red-500">Ocurrió un error al conectar con el servidor.</td></tr>`;
        });
}

function eliminar(id) {
    CustomAlert.show({
        title: "¿Eliminar cotización?",
        text: `Esta acción eliminará de forma permanente el registro #${id}.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "red"
    }).then((result) => {
        if (result.isConfirmed) {
            fetch("eliminar_cotizacion.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "id=" + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    CustomAlert.show({
                        title: "Registro eliminado",
                        text: "La cotización ha sido removida correctamente.",
                        icon: "success"
                    });
                    cargarCotizaciones();
                } else {
                    CustomAlert.show({
                        title: "Atención",
                        text: data.error || "No se pudo eliminar el registro.",
                        icon: "error"
                    });
                }
            })
            .catch(() => CustomAlert.show({
                title: "Error",
                text: "Error de conexión con el servidor.",
                icon: "error"
            }));
        }
    });
}

function editar(id) {
    fetch("obtener_cotizacion.php?id=" + encodeURIComponent(id))
    .then(res => res.json())
    .then(data => {

        if (data.success) {
            let c = data.data;

            document.getElementById("edit_id").value = c.id;
            document.getElementById("modalIdDisplay").textContent = "#" + c.id;

            document.getElementById("edit_cliente").value = c.nombre_cliente || '';
            document.getElementById("edit_telefono").value = c.telefono || '';

            // Trae únicamente lo que corresponde de la tabla servicios
            document.getElementById("edit_maniobra").value = c.maniobra || 0;
            document.getElementById("edit_flete").value = c.total || 0;

            actualizarSumaModal();
            openModal();

        } else {
            CustomAlert.show({
                title: "Error",
                text: "No se pudo cargar la cotización.",
                icon: "error"
            });
        }
    })
    .catch(() => {
        CustomAlert.show({
            title: "Error",
            text: "Error de red al intentar cargar la cotización.",
            icon: "error"
        });
    });
}

function actualizarSumaModal() {
    let flete = parseFloat(document.getElementById("edit_flete").value) || 0;
    let maniobra = parseFloat(document.getElementById("edit_maniobra").value) || 0;
    let sumaTotal = flete + maniobra;

    document.getElementById("edit_total").value = sumaTotal.toFixed(2);
    document.getElementById("edit_suma_final").textContent = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(sumaTotal);
}

function guardarCambios() {
    const data = {
    id: document.getElementById("edit_id").value,
    nombre_cliente: document.getElementById("edit_cliente").value,
    telefono: document.getElementById("edit_telefono").value,
    maniobra: document.getElementById("edit_maniobra").value,
    total: document.getElementById("edit_flete").value
};

    if (!data.id) return;

    fetch("actualizar_cotizacion.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(data).toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeModal();
            CustomAlert.show({
                title: "¡Guardado exitoso!",
                text: "La cotización fue actualizada correctamente.",
                icon: "success"
            });
            cargarCotizaciones();
        } else {
            CustomAlert.show({
                title: "Error",
                text: data.error || "No se pudo guardar la información.",
                icon: "error"
            });
        }
    })
    .catch(() => CustomAlert.show({ title: "Error", text: "Error al conectar con el servidor.", icon: "error" }));
}

function openModal() {
    document.getElementById("modalEditar")?.classList.remove("hidden");
}

function closeModal() {
    document.getElementById("modalEditar")?.classList.add("hidden");
}

document.getElementById("modalEditar")?.addEventListener("click", (e) => {
    if (e.target === document.getElementById("modalEditar")) closeModal();
});
