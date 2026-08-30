// ==========================================
// PRECARGA GLOBAL DEL LOGO (Evita retrasos de renderizado)
// ==========================================
const logoEmpresa = new Image();
logoEmpresa.src = "/asset/logo1023.png";

let datosCotizaciones = [];
let idEdicionActual = null;
let idFolioActual = null; 
window.currentDocData = null; 

// Almacenamiento del archivo pre-compilado listo para compartir
window.readyPdfFile = null;
window.readyPdfBlob = null;

// ==========================================
// 1. CARGAR DATOS
// ==========================================
async function inicializar() {
    try {
        const response = await fetch('/view/home/getCotizaciones.php');
        const json = await response.json();
        
        if (json.success) {
            datosCotizaciones = json.data;
            renderizar(datosCotizaciones);
        } else {
            console.error(json.error);
            document.getElementById('listaCotizaciones').innerHTML = '<p class="text-red-500 text-center font-bold bg-red-50 p-4 rounded-xl">Error al cargar datos.</p>';
        }
    } catch (error) {
        console.error("Error de conexión:", error);
    }
}

// ==========================================
// 2. DIBUJAR TARJETAS
// ==========================================
function renderizar(data) {
    const container = document.getElementById('listaCotizaciones');
    if(data.length === 0) {
        container.innerHTML = `
            <div class="text-center py-16 bg-white rounded-2xl border border-slate-200/70">
                <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">inbox</span>
                <p class="text-slate-500 font-semibold text-sm">No se encontraron resultados</p>
            </div>`;
        return;
    }

    container.innerHTML = data.map(s => {
        const maniobraNum = parseFloat(s.maniobra || 0);
        const totalGlobal = parseFloat(s.total || 0) + maniobraNum;
        const totalFmt = totalGlobal.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
        const maniobraFmt = maniobraNum.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
        const esAdmin = obtenerRolUsuario() === 'admin';
        return `
        <div class="bg-white rounded-2xl border border-slate-200/70 border-l-4 border-l-blue-800 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden" id="fila-${s.id}">
            <!-- Encabezado de Tarjeta -->
            <div class="p-5 cursor-pointer flex justify-between items-center gap-3 hover:bg-slate-50/80 transition-colors select-none" onclick="toggle(${s.id})">
                <div class="min-w-0 pr-2">
                    <span class="inline-block text-[10.5px] font-bold text-blue-800 bg-blue-50 px-2 py-0.5 rounded-md tracking-wide">FOLIO #${s.id}</span>
                    <h3 class="text-base font-semibold text-slate-800 mt-1.5 truncate">${s.nombre_cliente || 'Sin nombre'}</h3>
                    <span class="inline-block text-[10px] text-slate-500 font-semibold uppercase tracking-wide bg-slate-100 px-2 py-0.5 rounded-full mt-1">${s.tipo_servicio || 'Servicio'}</span>
                </div>
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <div class="text-right">
                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide">Total</p>
                        <p class="text-lg font-extrabold text-slate-800 tabular-nums leading-tight">${totalFmt}</p>
                    </div>
                    <span id="chevron-${s.id}" class="material-symbols-outlined text-slate-300 transition-transform duration-200">expand_more</span>
                </div>
            </div>
            
            <!-- Cuerpo Desplegable -->
            <div id="card-${s.id}" class="hidden p-5 pt-0 border-t border-slate-100 text-sm">
                <div class="grid grid-cols-2 gap-3 mt-4 mb-4">
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                        <p class="text-slate-400 text-[10px] uppercase font-semibold tracking-wide mb-0.5">Inmueble</p>
                        <p class="font-semibold text-slate-700 truncate text-[13px]">${s.inmueble || '—'}</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                        <p class="text-slate-400 text-[10px] uppercase font-semibold tracking-wide mb-0.5">Camioneta</p>
                        <p class="font-semibold text-slate-700 truncate text-[13px]">${s.tipo_camioneta || '—'}</p>
                    </div>
                </div>
                
                <div class="space-y-3.5 mb-4">
                    <div class="relative pl-8">
                        <span class="absolute left-0 top-0.5 w-5 h-5 bg-blue-50 text-blue-800 rounded-full flex items-center justify-center text-[10px] font-bold ring-4 ring-white">O</span>
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-slate-400 text-[10px] uppercase font-semibold tracking-wide">Origen</p>
                            <a href="geo:0,0?q=${encodeURIComponent(s.direccion_origen || '')}" onclick="event.stopPropagation()" class="text-blue-800 bg-blue-50 p-1.5 rounded-lg hover:bg-blue-100 transition-colors active:scale-90 select-none">
                                <span class="material-symbols-outlined text-sm block rotate-[45deg]">navigation</span>
                            </a>
                        </div>
                        <p class="text-slate-600 font-medium leading-snug text-[13px]">${s.direccion_origen || '—'}</p>
                    </div>
                    <div class="relative pl-8">
                        <span class="absolute left-0 top-0.5 w-5 h-5 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center text-[10px] font-bold ring-4 ring-white">D</span>
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-slate-400 text-[10px] uppercase font-semibold tracking-wide">Destino</p>
                            <a href="geo:0,0?q=${encodeURIComponent(s.direccion_destino || '')}" onclick="event.stopPropagation()" class="text-emerald-600 bg-emerald-50 p-1.5 rounded-lg hover:bg-emerald-100 transition-colors active:scale-90 select-none">
                                <span class="material-symbols-outlined text-sm block rotate-[45deg]">navigation</span>
                            </a>
                        </div>
                        <p class="text-slate-600 font-medium leading-snug text-[13px]">${s.direccion_destino || '—'}</p>
                    </div>
                    
                    <div class="bg-amber-50/60 p-4 rounded-xl border border-amber-100 relative">
                        <button onclick="event.stopPropagation(); abrirModalInventario(${s.id})" class="absolute top-3 right-3 text-amber-600 bg-amber-100/80 p-1.5 rounded-lg transition-all hover:bg-amber-200 active:scale-90 select-none">
                            <span class="material-symbols-outlined text-sm block">edit_square</span>
                        </button>
                        <p class="text-amber-700 text-[10px] font-semibold uppercase tracking-wide mb-1">Inventario / Notas</p>
                        <p class="text-slate-700 font-medium text-[13px] pr-6">${s.inventario || '<span class="text-slate-400 italic">Sin registro...</span>'}</p>
                    </div>
                </div>

                <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl mb-5 border border-slate-100">
                    <div class="text-center flex-1"><p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Cargadores</p><p class="font-bold text-slate-700 mt-0.5">${s.cargadores ?? '—'}</p></div>
                    <div class="w-px h-8 bg-slate-200"></div>
                    <div class="text-center flex-1"><p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Maniobra</p><p class="font-bold text-emerald-600 mt-0.5">${maniobraFmt}</p></div>
                    <div class="w-px h-8 bg-slate-200"></div>
                    <div class="text-center flex-1"><p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Fecha</p><p class="font-bold text-slate-700 text-[11px] mt-1">${s.fecha_creacion ? s.fecha_creacion.split(' ')[0] : '—'}</p></div>
                </div>
                
                <div class="flex gap-2">
                    ${esAdmin 
                        ? `<button onclick="event.stopPropagation(); borrarRegistro(${s.id})" class="flex-none w-12 h-12 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl transition-all active:scale-95 select-none flex items-center justify-center cursor-pointer" title="Eliminar registro">
                            <span class="material-symbols-outlined text-lg block">delete</span>
                        </button>`
                        : `<button class="flex-none w-12 h-12 bg-slate-100 text-slate-300 rounded-xl flex items-center justify-center cursor-not-allowed" disabled title="Solo admin puede eliminar">
                            <span class="material-symbols-outlined text-lg block">delete</span>
                        </button>`
                    }
                    <button onclick="event.stopPropagation(); abrirFacturacion(${s.id})" data-accion="facturar" data-folio-id="${s.id}" class="flex-1 h-12 bg-blue-800 hover:bg-blue-900 text-white rounded-xl font-semibold shadow-sm transition-all active:scale-95 select-none flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-lg">point_of_sale</span> Facturar
                    </button>
                </div>
            </div>
        </div>
    `;
    }).join('');
}

// ==========================================
// 3. FUNCIONES DE UI Y BÚSQUEDA
// ==========================================
function toggle(id) { document.getElementById(`card-${id}`).classList.toggle('hidden'); }
function cerrarModal(idModal) { document.getElementById(idModal).classList.add('hidden'); }

function filtrar() {
    const val = document.getElementById('BuscadorCotizaciones').value.toLowerCase();
    const filtrados = datosCotizaciones.filter(s => 
        (s.nombre_cliente || '').toLowerCase().includes(val) || 
        String(s.id).includes(val) ||
        (s.direccion_origen || '').toLowerCase().includes(val)
    );
    renderizar(filtrados);
}

// Cola de notificaciones: si llega un segundo aviso mientras el actual se
// está mostrando, se encola en vez de sobrescribirlo de golpe.
window._toastQueue = [];
window._toastMostrando = false;

function mostrarAlerta(mensaje, tipo = 'exito') {
    window._toastQueue.push({ mensaje, tipo });
    procesarColaToast();
}

function procesarColaToast() {
    if (window._toastMostrando) return;
    const siguiente = window._toastQueue.shift();
    if (!siguiente) return;
    window._toastMostrando = true;
    renderToast(siguiente.mensaje, siguiente.tipo);
}

// Colores e íconos según el tipo de aviso:
// 'exito' = verde (confirmación), 'eliminado'/'error' = rojo, 'info' = azul
function obtenerEstiloToast(tipo) {
    switch (tipo) {
        case 'eliminado':
            return { color: 'bg-red-600', icono: 'delete' };
        case 'error':
            return { color: 'bg-red-600', icono: 'error' };
        case 'info':
            return { color: 'bg-blue-800', icono: 'info' };
        case 'exito':
        default:
            return { color: 'bg-green-600', icono: 'check_circle' };
    }
}

function renderToast(mensaje, tipo) {
    const toast = document.getElementById('toastNotificacionLince');
    if (!toast) {
        console.warn('No se encontró el elemento #toastNotificacionLince para mostrar:', mensaje);
        window._toastMostrando = false;
        procesarColaToast();
        return;
    }

    // Blindaje: si algún contenedor padre tiene transform/overflow, el
    // position:fixed deja de calcularse contra la pantalla completa.
    // Reenganchamos el toast directo a <body> con z-index máximo.
    if (toast.parentElement !== document.body) {
        document.body.appendChild(toast);
    }
    toast.style.position = 'fixed';
    toast.style.zIndex = '999999';

    const textoEl = document.getElementById('toastTexto');
    const iconoEl = document.getElementById('toastIcono');
    const circuloEl = document.getElementById('toastIconWrap');
    const { color, icono } = obtenerEstiloToast(tipo);

    if (textoEl) textoEl.textContent = mensaje;
    if (iconoEl) iconoEl.textContent = icono;
    if (circuloEl) {
        circuloEl.classList.remove('bg-blue-800', 'bg-red-600', 'bg-green-600');
        circuloEl.classList.add(color);
    }

    // Tocar el toast lo cierra de inmediato
    toast.onclick = () => cerrarToastActual();

    // Mostrar con animación de entrada
    toast.classList.remove('opacity-0', '-translate-y-3', 'pointer-events-none');
    toast.classList.add('opacity-100', 'translate-y-0');

    clearTimeout(toast._alertaTimeoutId);
    // Los errores se quedan visibles hasta que el usuario los toque;
    // los avisos de éxito se autoborran solos.
    if (tipo !== 'error') {
        toast._alertaTimeoutId = setTimeout(() => cerrarToastActual(), 3000);
    }
}

function cerrarToastActual() {
    const toast = document.getElementById('toastNotificacionLince');
    if (!toast) {
        window._toastMostrando = false;
        procesarColaToast();
        return;
    }
    clearTimeout(toast._alertaTimeoutId);
    toast.classList.remove('opacity-100', 'translate-y-0');
    toast.classList.add('opacity-0', '-translate-y-3', 'pointer-events-none');
    // Se espera a que termine la animación de salida antes de mostrar el siguiente de la cola
    setTimeout(() => {
        window._toastMostrando = false;
        procesarColaToast();
    }, 300);
}

// ==========================================
// UI/UX: CONFIRMACIÓN ESTILIZADA (reemplaza el confirm() nativo del navegador)
// ==========================================
function confirmarAccion(mensaje, tituloBoton = 'Confirmar', tipo = 'peligro') {
    const esPeligro = tipo === 'peligro';
    const colorIcono = esPeligro ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-800';
    const icono = esPeligro ? 'warning' : 'task_alt';
    const colorBoton = esPeligro ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-800 hover:bg-blue-900';

    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[10000] bg-black/50 flex items-center justify-center p-6';
        overlay.innerHTML = `
            <div class="bg-white rounded-2xl w-full max-w-sm p-6 shadow-2xl">
                <div class="w-12 h-12 rounded-full ${colorIcono} flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined">${icono}</span>
                </div>
                <p class="text-slate-700 font-semibold text-[15px] leading-snug mb-6">${mensaje}</p>
                <div class="flex gap-2">
                    <button data-accion="cancelar" class="flex-1 h-12 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition-all active:scale-95 select-none">Cancelar</button>
                    <button data-accion="confirmar" class="flex-1 h-12 ${colorBoton} text-white rounded-xl font-semibold transition-all active:scale-95 select-none">${tituloBoton}</button>
                </div>
            </div>`;
        document.body.appendChild(overlay);

        overlay.addEventListener('click', (e) => {
            const accion = e.target.closest('[data-accion]')?.dataset.accion;
            if (!accion && e.target !== overlay) return; // clic dentro de la tarjeta, fuera de un botón: no hacer nada
            document.body.removeChild(overlay);
            resolve(accion === 'confirmar');
        });
    });
}

// Helper para obtener el rol del usuario (busca en múltiples fuentes)
function obtenerRolUsuario() {
    // Opción 1: variable global inyectada desde PHP
    if (typeof rolUsuario !== 'undefined' && rolUsuario && rolUsuario.trim()) {
        return rolUsuario.trim().toLowerCase();
    }
    // Opción 2: localStorage (por si se guardó antes)
    const rolLocal = localStorage.getItem('rolUsuario');
    if (rolLocal) {
        return rolLocal.trim().toLowerCase();
    }
    // Opción 3: window.rolUsuario (backup)
    if (window.rolUsuario && window.rolUsuario.trim()) {
        return window.rolUsuario.trim().toLowerCase();
    }
    return '';
} 
// ==========================================
async function borrarRegistro(id) {
    // Validar que el usuario es admin antes de permitir eliminar
    if (obtenerRolUsuario() !== 'admin') {
        mostrarAlerta('No tienes permiso para eliminar registros', 'error');
        return;
    }

    const confirmado = await confirmarAccion('¿Seguro que deseas eliminar este registro? Esta acción no se puede deshacer.', 'Eliminar');
    if (!confirmado) return;
    
    try {
        const res = await fetch("/view/home/eliminar_servicio.php", {
            method: "POST",
            body: new URLSearchParams({ id })
        });
        const resp = await res.json();
        
        if(resp.success) {
            datosCotizaciones = datosCotizaciones.filter(d => d.id !== id);
            renderizar(datosCotizaciones);
            mostrarAlerta('Registro eliminado correctamente', 'eliminado');
        } else {
            mostrarAlerta("Error al eliminar: " + resp.error, 'error');
        }
    } catch (err) {
        console.error(err);
        mostrarAlerta('Error de conexión al eliminar', 'error');
    }
}

// ==========================================
// 5. INVENTARIO / ACTUALIZAR
// ==========================================
function abrirModalInventario(id) {
    idEdicionActual = id;
    const registro = datosCotizaciones.find(s => s.id === id);
    if (!registro) return;
    document.getElementById('modal-cliente-inventario').innerText = `Cliente: ${registro.nombre_cliente || '-'}`;
    document.getElementById('edit-area').value = registro.inventario || '';
    document.getElementById('modal-inventario').classList.remove('hidden');
}

async function guardarInventario() {
    const nuevoInventario = document.getElementById('edit-area').value;
    const registro = datosCotizaciones.find(s => s.id === idEdicionActual);
    if (!registro) return;

    // Se sanea el objeto para evitar enviar "null"/"undefined" como texto literal
    const payload = {};
    Object.keys(registro).forEach(k => { payload[k] = registro[k] ?? ''; });
    payload.inventario = nuevoInventario;

    try {
        const res = await fetch("/view/home/actualizar_servicio.php", {
            method: "POST",
            body: new URLSearchParams(payload)
        });
        const resp = await res.json();
        
        if (resp.success) {
            registro.inventario = nuevoInventario;
            renderizar(datosCotizaciones);
            cerrarModal('modal-inventario');
            mostrarAlerta('Inventario actualizado');
        } else {
            mostrarAlerta('Error al actualizar: ' + resp.error, 'error');
        }
    } catch (error) {
        console.error(error);
        mostrarAlerta('Error de red al actualizar', 'error');
    }
}

// ==========================================
// 6. CARRITO / FACTURACIÓN 
// ==========================================
function abrirFacturacion(id) {
    idFolioActual = id;
    const registro = datosCotizaciones.find(s => s.id === id);
    if (!registro) return;
    
    document.getElementById('folio-display').innerText = id;
    const tbody = document.querySelector('#tablaCarrito tbody');
    tbody.innerHTML = ''; 

    const origen = registro.direccion_origen || '';
    const destino = registro.direccion_destino || '';
    const ruta = `${origen} > ${destino}`;
    
    // Primera fila: NO se puede eliminar (es la fila principal del servicio)
    agregarFilaCarrito(registro.nombre_cliente || 'Cliente', ruta, 1, registro.total, false, false);
    
    // Maniobra: sí se puede eliminar si es admin (puedeEliminar = true por defecto)
    if(parseFloat(registro.maniobra || 0) > 0) {
        const cantidadCargadores = parseInt(registro.cargadores) || 1;
        agregarFilaCarrito(registro.nombre_cliente || 'Cliente', "Cargadores / Maniobra", cantidadCargadores, registro.maniobra);
    }

    document.getElementById('modal-carrito').classList.remove('hidden');
}

function agregarFilaCarrito(cliente, producto, cant, total, puedeEliminar = true, esEditable = true) {
    const tbody = document.querySelector('#tablaCarrito tbody');
    const tr = document.createElement('tr');
    tr.className = "cart-row border-b border-slate-100 hover:bg-slate-50 transition-colors";
    
    const esAdmin = obtenerRolUsuario() === 'admin';
    // Admin puede eliminar y editar cualquier fila, usuarios solo pueden si puedeEliminar/esEditable = true
    const puedeEliminarFila = esAdmin || puedeEliminar;
    const puedeEditarFila = esAdmin || esEditable;
    
    // Si no puede editar, los inputs están deshabilitados
    const disabledAttr = puedeEditarFila ? '' : 'disabled';
    const opacityClass = puedeEditarFila ? '' : 'opacity-50';
    
    tr.innerHTML = `
        <td class="p-2 py-3"><input type="text" class="w-full bg-transparent border-0 focus:ring-0 text-sm font-bold text-slate-700 cart-cliente outline-none ${opacityClass}" value="${cliente}" ${disabledAttr}></td>
        <td class="p-2 py-3"><input type="text" class="w-full bg-transparent border-0 focus:ring-0 text-[12px] text-slate-500 cart-producto outline-none ${opacityClass}" value="${producto}" ${disabledAttr}></td>
        <td class="p-2 py-3"><input type="number" class="w-full p-1.5 bg-slate-100 border border-transparent focus:border-blue-300 rounded text-center cart-cant outline-none font-bold ${opacityClass}" value="${cant}" min="1" oninput="calcularTotalCarrito()" ${disabledAttr}></td>
        <td class="p-2 py-3"><input type="number" class="w-full p-1.5 bg-slate-100 border border-transparent focus:border-blue-300 rounded text-right cart-total outline-none font-black text-blue-800 ${opacityClass}" value="${parseFloat(total || 0).toFixed(2)}" oninput="calcularTotalCarrito()" ${disabledAttr}></td>
        <td class="p-2 py-3 text-right">
            ${puedeEliminarFila 
                ? `<button onclick="eliminarFilaCarrito(this, ${puedeEliminar})" class="text-slate-300 hover:text-red-500 transition-colors active:scale-90 select-none outline-none cursor-pointer" title="Eliminar fila">
                    <span class="material-symbols-outlined text-xl">delete</span>
                </button>`
                : `<button class="text-slate-200 cursor-not-allowed outline-none" disabled title="No puedes eliminar la fila principal">
                    <span class="material-symbols-outlined text-xl">delete</span>
                </button>`
            }
        </td>
    `;
    tbody.appendChild(tr);
    calcularTotalCarrito();
}

function agregarFilaLibre() {
    agregarFilaCarrito("Extra", "Servicio adicional", 1, 0);
}

async function eliminarFilaCarrito(btn, puedeEliminarFilaOriginal) {
    const esAdmin = obtenerRolUsuario() === 'admin';
    
    // Solo admin puede eliminar cualquier fila, usuarios normales solo la segunda en adelante
    if (!esAdmin && !puedeEliminarFilaOriginal) {
        mostrarAlerta('No puedes eliminar la fila principal', 'error');
        return;
    }

    const confirmado = await confirmarAccion('¿Quitar este concepto del carrito?', 'Quitar');
    if (!confirmado) return;
    btn.closest('tr').remove();
    calcularTotalCarrito();
}

function calcularTotalCarrito() {
    let total = 0;
    document.querySelectorAll('.cart-row').forEach(row => {
        const inputTotal = row.querySelector('.cart-total');
        if(inputTotal) total += parseFloat(inputTotal.value || 0);
    });
    document.getElementById('totalCarrito').innerText = total.toFixed(2);
}

async function guardarFacturacion() {
    const carrito = [];
    let totalAFacturar = 0;
    document.querySelectorAll('.cart-row').forEach(row => {
        const inputProd = row.querySelector('.cart-producto');
        const inputCliente = row.querySelector('.cart-cliente');
        const inputCant = row.querySelector('.cart-cant');
        const inputTotal = row.querySelector('.cart-total');

        if (!inputProd || !inputCliente) return;

        const prod = inputProd.value;
        let origen = "", destino = "";
        
        if (prod.includes(">")) {
            const partes = prod.split(">");
            origen = partes[0].trim();
            destino = partes[1].trim();
        }

        const totalFila = parseFloat(inputTotal ? inputTotal.value : 0);
        totalAFacturar += totalFila;

        carrito.push({
            cliente: inputCliente.value,
            producto: prod,
            cantidad: parseInt(inputCant ? inputCant.value : 1),
            total: totalFila,
            direccion_origen: origen,
            destino: destino
        });
    });

    if (carrito.length === 0) {
        mostrarAlerta("El carrito está vacío.", 'error');
        return;
    }

    const totalFmt = totalAFacturar.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
    const confirmado = await confirmarAccion(`¿Guardar esta facturación por un total de ${totalFmt}?`, 'Guardar', 'guardar');
    if (!confirmado) return;

    try {
        const res = await fetch("/view/home/guardar_facturacion.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            // Se agrega servicio_id para vincular la facturación con la cotización de origen
            body: JSON.stringify({ carrito, servicio_id: idFolioActual })
        });
        const resp = await res.json();
        
        if (resp.success) {
            mostrarAlerta("Facturación guardada exitosamente");
            if (resp.liga_rastreo) {
                mostrarLigaRastreo(resp.liga_rastreo, resp.telefono_cliente);
            }
            cerrarModal('modal-carrito');
        } else {
            mostrarAlerta("Error al guardar: " + resp.error, 'error');
        }
    } catch (err) {
        console.error(err);
        mostrarAlerta("Error de conexión al guardar carrito.", 'error');
    }
}

// ==========================================
// 7. INTERFAZ Y RENDER DEL DOCUMENTO REAL
// ==========================================
function cerrarPreview() {
    document.getElementById('modalDocumentoReal').classList.add('hidden');
    window.currentDocData = null;
    window.readyPdfFile = null;
    window.readyPdfBlob = null;
}

// ==========================================
// CORREGIDO: el folio del PDF debe ser siempre el folio real del cliente
// (idFolioActual), no un consecutivo traído de un endpoint externo.
// Antes esta función llamaba a getFolio.php y ese valor podía no
// corresponder a la cotización que se estaba facturando.
// ==========================================
async function obtenerFolioPDF() {
    return `${idFolioActual}`;
}

function ensurePreviewModalExists() {
    if (document.getElementById('modalDocumentoReal')) return;

    const modalHTML = `
    <div id="modalDocumentoReal" class="hidden fixed inset-0 z-[9999] bg-slate-100 flex flex-col w-screen h-screen overflow-hidden">
        <div class="p-4 px-6 flex justify-between items-center gap-3 bg-[#1400AD] text-white flex-shrink-0 select-none">
            <div class="flex items-center gap-2 min-w-0">
                <span class="material-symbols-outlined text-white flex-shrink-0">description</span>
                <h2 class="font-black text-sm tracking-wider uppercase truncate">Documento de Cotización</h2>
            </div>
            
        </div>
        <div class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-300 flex flex-col items-center">
            <div id="wrapperDocumentoHTML" class="w-full max-w-3xl bg-white p-6 sm:p-10 rounded-xl border border-slate-400 shadow-2xl font-sans"></div>
        </div>
        <div class="p-4 px-6 border-t border-slate-200 bg-white flex gap-3 shadow-[0_-4px_12px_rgba(0,0,0,0.05)] flex-shrink-0">
            <button onclick="cerrarPreview()" class="flex-1 min-h-[56px] py-2 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl font-bold transition-all active:scale-95 flex items-center justify-center gap-2 text-sm leading-tight text-center outline-none">
                <span class="material-symbols-outlined text-lg flex-shrink-0">arrow_back</span><span>Regresar</span>
            </button>
            <button id="btnCompartirReal" onclick="ejecutarAccionFinal()" class="flex-[2] min-h-[56px] py-2 px-2 bg-[#1400AD] hover:bg-blue-800 text-white rounded-2xl font-black shadow-xl transition-all active:scale-95 flex items-center justify-center gap-2 text-sm leading-tight text-center outline-none">
                <span class="material-symbols-outlined text-xl flex-shrink-0">share</span><span>Compartir Cotización (PDF)</span>
            </button>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function construirTemplateHTML(folio, fecha, hora, carrito, totalGeneral) {
    const tablaFilasHTML = carrito.map(item => `
        <tr style="font-size: 13px; color: #2d3748;">
            <td style="padding: 10px 8px; vertical-align: top; width:15%; font-weight: bold;">${item.cliente}</td>
            <td style="padding: 10px 8px; vertical-align: top; width:60%; text-align: justify; line-height: 1.4;">${item.producto}</td>
            <td style="padding: 10px 8px; vertical-align: top; text-align: center; width:10%;">${item.cantidad}</td>
            <td style="padding: 10px 8px; vertical-align: top; text-align: right; width:15%; font-weight: 500;">$${parseFloat(item.totalOriginal).toFixed(2)}</td>
        </tr>
    `).join('');

    // Relleno visual: si hay pocos conceptos, se agregan filas vacías para que
    // el cuadro de la tabla ocupe más espacio de la hoja (se ve más "lleno"),
    // sin importar cuántos artículos tenga la cotización.
    const FILAS_MINIMAS = 5;
    const filasFaltantes = Math.max(0, FILAS_MINIMAS - carrito.length);
    const filasRelleno = Array.from({ length: filasFaltantes }).map(() => `
        <tr style="height: 48px;"><td colspan="4">&nbsp;</td></tr>
    `).join('');

    return `
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:3px solid #1400AD; padding-bottom:10px; margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <img src="/asset/logo1023.png" style="width:110px; height:110px; object-fit:contain; margin-bottom:10px;">
                <div style="margin-left:10px;">
                    <strong style="font-size:20px; color: #1a202c; font-family: sans-serif;">Transportes y Mudanzas Pantera</strong><br>
                    <small style="color:#718096; font-size:13px; font-weight: 600;">Formato de Cotizacion</small>
                </div>
            </div>
            <div style="text-align:right; font-size:12px; font-family: sans-serif; color: #2d3748; line-height:1.4;">
                <div style="margin-bottom: 5px;">
                    <strong style="color:#718096;">Folio:</strong> <strong style="color: #FF0000; font-size: 15px;">${folio}</strong>
                </div>
                <div><strong>Fecha:</strong> ${fecha}<br><strong>Hora:</strong> ${hora}${obtenerRolUsuario() === "admin" ? "<br>RFC: CEFL950210513<br>Jose Ceballos 60<br>Tel: 5540662626" : ""}</div>
            </div>
        </div>
        <table style="width:100%; border-collapse: separate; border-spacing: 0; border: 2px solid #1400AD; border-radius: 12px; overflow: hidden; font-family: sans-serif; margin-bottom: 5px;">
            <thead style="background:#1400AD; color:white; font-size:13px;">
                <tr>
                    <th style="padding:10px 8px; text-align:left;">Cliente</th>
                    <th style="padding:10px 8px; text-align:left;">Concepto</th>
                    <th style="padding:10px 8px; text-align:center;">Cantidad</th>
                    <th style="padding:10px 8px; text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody style="background: white;">${tablaFilasHTML}${filasRelleno}</tbody>
        </table>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px; margin-bottom: 25px; font-size:15px; font-family: sans-serif; color: #1a202c;">
            <div></div>
            <div><strong>Total: $<span>${totalGeneral.toLocaleString('es-MX', {minimumFractionDigits: 2})}</span></strong></div>
        </div>
        <div style="margin-top:20px;">
            <div style="width:100%; border:2px solid #1400AD; border-radius:12px; padding:15px; font-size:11px; white-space: pre-wrap; font-family: sans-serif; color:#2d3748; line-height:1.5; text-align:justify; background-color: white;"><strong>Observaciones del Servicio de Mudanza</strong>
Alcance: El servicio es exclusivo para el transporte de bienes; no se transporta a personas. El operador tiene la facultad de cancelar el servicio si las vialidades no son aptas para la unidad.
Puntualidad: La hora de llegada está sujeta a factores externos (tráfico, accidentes, etc.).
Tiempos de Espera: Se otorgan 30 minutos de tolerancia para carga y descarga. Excedido este tiempo, se aplicará un cargo del 10% del costo total por cada 30 minutos adicionales. Si la espera supera las 3 horas, la penalización será del 100% del costo.
Inventario: El Cliente es responsable de proporcionar un listado detallado y preciso de los bienes a trasladar.
Pagos y Anticipos: Se requiere un anticipo del 50% para reservar el servicio. El saldo restante debe liquidarse antes de iniciar la descarga en el destino.
Cancelaciones: Deben notificarse con al menos 8 horas de anticipación. De lo contrario, el anticipo no será reembolsable. Si la Empresa cancela por causas propias, se devolverá el total del anticipo.
Responsabilidad: La Empresa tomará las precauciones necesarias, pero se recomienda al Cliente contratar un seguro adicional para cubrir cualquier eventualidad.
Consulta nuestros términos y condiciones del servicio.</div>
        </div>`;
}

// 🔥 VISTA PREVIA INSTANTÁNEA BLINDADA
async function generarPDF() {
    try {
        ensurePreviewModalExists();
        
        // 1. Abrir el modal inmediatamente en estado de carga (Asegura respuesta visual al 100%)
        document.getElementById('wrapperDocumentoHTML').innerHTML = `
            <div class="text-center py-12 text-slate-500 font-bold">
                <p class="animate-pulse tracking-wide text-blue-800 text-base">Estructurando documento de cotización...</p>
            </div>`;
        
        // Deshabilitar visualmente el botón de compartir hasta que termine jsPDF en background
        const btnCompartir = document.getElementById('btnCompartirReal');
        if(btnCompartir) {
            btnCompartir.disabled = true;
            btnCompartir.classList.add('opacity-50', 'cursor-not-allowed');
            btnCompartir.innerHTML = `<span class="material-symbols-outlined text-xl animate-spin">sync</span>Compilando PDF...`;
        }

        document.getElementById('modalDocumentoReal').classList.remove('hidden');

        // 2. Mapeo seguro de variables del carrito
        const carritoData = [];
        let totalGeneral = 0;
        
        const rows = document.querySelectorAll('.cart-row');
        if (rows.length === 0) {
            document.getElementById('wrapperDocumentoHTML').innerHTML = `
                <div class="text-center py-12 text-red-500 font-bold">
                    <p>El carrito actual se encuentra vacío.</p>
                </div>`;
            return;
        }

        rows.forEach((row) => {
            const inputCliente = row.querySelector('.cart-cliente');
            const inputProducto = row.querySelector('.cart-producto');
            const inputCant = row.querySelector('.cart-cant');
            const inputTotal = row.querySelector('.cart-total');

            // Protección crítica: Si la fila está rota o incompleta, se ignora en vez de crashear
            if (inputCliente && inputProducto && inputCant && inputTotal) {
                const totalFila = parseFloat(inputTotal.value || 0);
                totalGeneral += totalFila;
                carritoData.push({
                    cliente: inputCliente.value,
                    producto: inputProducto.value,
                    cantidad: inputCant.value,
                    totalOriginal: totalFila.toFixed(2)
                });
            }
        });

        const ahora = new Date();
        const fechaMX = ahora.toLocaleDateString('es-MX', { timeZone: 'America/Mexico_City', day: '2-digit', month: '2-digit', year: 'numeric' });
        const horaMX = ahora.toLocaleTimeString('es-MX', { timeZone: 'America/Mexico_City', hour: '2-digit', minute: '2-digit', hour12: true }).toUpperCase();
        
        // 3. El folio SIEMPRE corresponde al folio real del cliente/cotización
        const folio = await obtenerFolioPDF();
        
        // 4. Inyectar la plantilla final en el modal abierto
        window.currentDocData = { folio, fechaMX, horaMX, carritoData, totalGeneral };
        document.getElementById('wrapperDocumentoHTML').innerHTML = construirTemplateHTML(folio, fechaMX, horaMX, carritoData, totalGeneral);

        // 5. Compilar el archivo PDF en el servidor con DomPDF (texto nítido y seleccionable,
        //    en vez de la "foto" que producía html2canvas)
        compilarPdfEnServidor(folio, carritoData, totalGeneral);

    } catch (error) {
        console.error("Error crítico en generarPDF:", error);
        document.getElementById('wrapperDocumentoHTML').innerHTML = `
            <div class="text-center py-12 text-red-500 font-bold bg-red-50 rounded-2xl p-4">
                <p>Error en la compilación del modal: ${error.message}</p>
            </div>`;
    }
}

// ==========================================
// 8. COMPILADOR EN SEGUNDO PLANO (captura visual con html2canvas)
// ==========================================
// En vez de redibujar el documento a mano con jsPDF+autoTable, se toma una
// "foto" del propio HTML que ya se muestra en pantalla (wrapperDocumentoHTML)
// y esa imagen se pega dentro del PDF. El diseño queda idéntico al preview,
// y solo depende de dos librerías: jsPDF y html2canvas.
async function esperarImagenesCargadas(contenedor) {
    const imgs = Array.from(contenedor.querySelectorAll('img'));
    await Promise.all(imgs.map(img => {
        if (img.complete) return Promise.resolve();
        return new Promise(resolve => {
            img.onload = resolve;
            img.onerror = resolve; // seguir aunque una imagen falle, no bloquear el PDF
        });
    }));
}

function compilarPdfEnSegundoPlano(folio) {
    window.readyPdfFile = null;
    window.readyPdfBlob = null;

    // Reactiva el botón de compartir en caso de cualquier fallo, para que el usuario
    // pueda reintentar en vez de quedarse atascado en "Compilando PDF..." para siempre.
    const habilitarReintento = () => {
        const btnCompartir = document.getElementById('btnCompartirReal');
        if (btnCompartir) {
            btnCompartir.disabled = false;
            btnCompartir.classList.remove('opacity-50', 'cursor-not-allowed');
            btnCompartir.innerHTML = `<span class="material-symbols-outlined text-xl">error</span>Reintentar PDF`;
            btnCompartir.onclick = () => {
                btnCompartir.onclick = () => ejecutarAccionFinal();
                compilarPdfEnSegundoPlano(folio);
            };
        }
    };

    (async () => {
        try {
            if (typeof html2canvas !== 'function') throw new Error("html2canvas no está cargado en la página");
            const { jsPDF } = window.jspdf || {};
            if (!jsPDF) throw new Error("jsPDF no está cargado en la página");

            const wrapper = document.getElementById('wrapperDocumentoHTML');
            if (!wrapper) throw new Error("No se encontró el documento a convertir");

            // Se crea una copia oculta con ANCHO FIJO tipo "hoja de documento".
            // Esto evita que el PDF salga apretado/encimado según el ancho de pantalla
            // del celular desde el que se genera; el diseño visible en el modal no se toca.
            const ANCHO_DOCUMENTO = 760;
            const clon = document.createElement('div');
            clon.className = wrapper.className;
            clon.innerHTML = wrapper.innerHTML;
            clon.style.position = 'absolute';
            clon.style.top = '0';
            clon.style.left = '-99999px';
            clon.style.width = ANCHO_DOCUMENTO + 'px';
            clon.style.maxWidth = 'none';
            clon.style.backgroundColor = '#ffffff';
            document.body.appendChild(clon);

            // Espera a que el logo y cualquier otra imagen del template terminen de cargar
            await esperarImagenesCargadas(clon);

            let canvas;
            try {
                canvas = await html2canvas(clon, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    width: ANCHO_DOCUMENTO,
                    windowWidth: ANCHO_DOCUMENTO + 200
                });
            } finally {
                document.body.removeChild(clon);
            }

            const imgData = canvas.toDataURL('image/jpeg', 0.95);

            // Hoja A4 FIJA; el contenido capturado se estira para llenar
            // exactamente el ancho y el alto útiles de la hoja.
            const margin = 20;
            const doc = new jsPDF('p', 'pt', 'a4');
            const usableWidth = doc.internal.pageSize.getWidth() - margin * 2;
            const usableHeight = doc.internal.pageSize.getHeight() - margin * 2;

            doc.addImage(imgData, 'JPEG', margin, margin, usableWidth, usableHeight);

            const blob = doc.output("blob");
            window.readyPdfBlob = blob;
            window.readyPdfFile = new File([blob], `Cotizacion-${folio}.pdf`, { type: 'application/pdf' });

            const btnCompartir = document.getElementById('btnCompartirReal');
            if (btnCompartir) {
                btnCompartir.disabled = false;
                btnCompartir.classList.remove('opacity-50', 'cursor-not-allowed');
                btnCompartir.innerHTML = `<span class="material-symbols-outlined text-xl">share</span>Compartir Cotización (PDF)`;
                btnCompartir.onclick = () => ejecutarAccionFinal();
            }
        } catch (err) {
            console.error("Error al compilar PDF con html2canvas:", err);
            habilitarReintento();
        }
    })();
}

// ==========================================
// 9. DISPARADOR DE COMPARTICIÓN REFORZADO
// ==========================================
function compilarPdfEnServidor(folio, carritoData, totalGeneral) {
    window.readyPdfFile = null;
    window.readyPdfBlob = null;

    // Reactiva el botón de compartir en caso de cualquier fallo, para que el usuario
    // pueda reintentar en vez de quedarse atascado en "Compilando PDF..." para siempre.
    const habilitarReintento = () => {
        const btnCompartir = document.getElementById('btnCompartirReal');
        if (btnCompartir) {
            btnCompartir.disabled = false;
            btnCompartir.classList.remove('opacity-50', 'cursor-not-allowed');
            btnCompartir.innerHTML = `<span class="material-symbols-outlined text-xl">error</span>Reintentar PDF`;
            btnCompartir.onclick = () => {
                btnCompartir.onclick = () => ejecutarAccionFinal();
                compilarPdfEnServidor(folio, carritoData, totalGeneral);
            };
        }
    };

    (async () => {
        try {
            // El backend espera "total" (no "totalOriginal") por cada concepto
            const carritoParaEnviar = carritoData.map(item => ({
                cliente: item.cliente,
                producto: item.producto,
                cantidad: item.cantidad,
                total: item.totalOriginal
            }));

            const res = await fetch('/view/home/generar_pdf.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ folio, carrito: carritoParaEnviar, totalGeneral })
            });
            const resp = await res.json();

            if (!resp.success) throw new Error(resp.error || 'El servidor no pudo generar el PDF');

            // Decodificar el base64 devuelto por el servidor a un Blob real
            const byteChars = atob(resp.pdf_base64);
            const byteNumbers = new Array(byteChars.length);
            for (let i = 0; i < byteChars.length; i++) {
                byteNumbers[i] = byteChars.charCodeAt(i);
            }
            const byteArray = new Uint8Array(byteNumbers);
            const blob = new Blob([byteArray], { type: 'application/pdf' });

            window.readyPdfBlob = blob;
            window.readyPdfFile = new File([blob], resp.filename || `Cotizacion-${folio}.pdf`, { type: 'application/pdf' });

            const btnCompartir = document.getElementById('btnCompartirReal');
            if (btnCompartir) {
                btnCompartir.disabled = false;
                btnCompartir.classList.remove('opacity-50', 'cursor-not-allowed');
                btnCompartir.innerHTML = `<span class="material-symbols-outlined text-xl">share</span>Compartir Cotización (PDF)`;
                btnCompartir.onclick = () => ejecutarAccionFinal();
            }
        } catch (err) {
            console.error("Error al compilar PDF en el servidor:", err);
            habilitarReintento();
        }
    })();
}

async function ejecutarAccionFinal() {

    if (!window.readyPdfFile) {
        mostrarAlerta("El PDF aún no está listo.", 'error');
        return;
    }

    // APK Android
    if (window.Android?.sharePdf) {

        const reader = new FileReader();

        reader.onload = () => {
            window.Android.sharePdf(
                reader.result.split(",")[1],
                window.readyPdfFile.name
            );
        };

        reader.readAsDataURL(window.readyPdfBlob);
        return;
    }

    // Navegadores modernos: solo se intenta si el navegador realmente puede
    // compartir ESTE archivo (algunos WebView tienen navigator.share pero no
    // soportan archivos, y tronaban de inmediato cayendo a la descarga).
    const puedeCompartirArchivo = navigator.share &&
        (typeof navigator.canShare !== 'function' || navigator.canShare({ files: [window.readyPdfFile] }));

    if (puedeCompartirArchivo) {

        try {

            await navigator.share({
                files: [window.readyPdfFile],
                title: "Cotización",
                text: "Adjunto la cotización."
            });

            cerrarPreview();
            return;

        } catch (e) {
            console.log(e);
            if (e && e.name === 'AbortError') {
                // El usuario canceló el cuadro de compartir a propósito: no forzar descarga
                return;
            }
            // Cualquier otro error real: se sigue al respaldo de descarga
        }
    }

    // Descarga como respaldo (solo cuando compartir no es posible o falló de verdad)
    const url = URL.createObjectURL(window.readyPdfBlob);

    const a = document.createElement("a");
    a.href = url;
    a.download = window.readyPdfFile.name;

    document.body.appendChild(a);
    a.click();
    a.remove();

    URL.revokeObjectURL(url);

    cerrarPreview();
}

// ==========================================
// 10. ACTUALIZACIÓN AUTOMÁTICA (polling)
// ==========================================
// No hay tiempo real conectado al backend, así que se revisa periódicamente
// si hay cambios y solo se vuelve a dibujar si de verdad cambió algo.
let _intervaloActualizacion = null;

function iniciarActualizacionAutomatica(intervaloMs = 15000) {
    if (_intervaloActualizacion) clearInterval(_intervaloActualizacion);
    _intervaloActualizacion = setInterval(refrescarCotizacionesSiCorresponde, intervaloMs);
}

async function refrescarCotizacionesSiCorresponde() {
    // No interrumpir si el usuario tiene un modal abierto (facturando, editando
    // inventario o viendo el PDF), para no repintarle la lista a medio trabajo.
    const modalesAbiertos = ['modal-carrito', 'modal-inventario', 'modalDocumentoReal']
        .some(id => {
            const el = document.getElementById(id);
            return el && !el.classList.contains('hidden');
        });
    if (modalesAbiertos) return;

    try {
        const response = await fetch('/view/home/getCotizaciones.php');
        const json = await response.json();
        if (!json.success) return;

        const datosNuevos = json.data.map(item => ({ ...item, id: Number(item.id) }));
        const huboCambios = JSON.stringify(datosNuevos) !== JSON.stringify(datosCotizaciones);
        if (huboCambios) {
            datosCotizaciones = datosNuevos;
            renderizar(datosCotizaciones);
            mostrarAlerta('Se encontraron nuevas cotizaciones', 'info');
        }
    } catch (err) {
        console.error('Error al refrescar cotizaciones automáticamente:', err);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    inicializar();
    iniciarActualizacionAutomatica();
});

(function () {

    // ==========================================
    // MODALES DE TELÉFONO (glassmorphism, generados dinámicamente)
    // ==========================================
    function ensurarModalesTelefono() {
        if (document.getElementById('modalTelefono')) return;

        const html = `
        <div id="modalTelefono" class="fixed inset-0 z-[10050] flex items-center justify-center p-4 hidden">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"></div>
            <div class="bg-white/70 backdrop-blur-xl border border-white/50 shadow-2xl w-full max-w-sm relative z-10 p-8 rounded-3xl">
                <div class="w-16 h-16 bg-white/50 rounded-2xl flex items-center justify-center mx-auto mb-6 border border-white/20">
                    <svg class="w-8 h-8 text-blue-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                </div>

                <div class="text-center mb-6">
                    <h2 class="text-2xl font-extrabold text-slate-800">¡Información Requerida!</h2>
                    <p id="modalTelefonoTexto" class="text-slate-600 mt-2"></p>
                </div>

                <form id="formTelefono" class="space-y-6">
                    <input
                        type="tel"
                        id="inputTelefono"
                        placeholder="Ej. 55 1234 5678"
                        pattern="[0-9]{10,12}"
                        inputmode="numeric"
                        required
                        class="w-full bg-white/50 border border-slate-200/50 rounded-xl px-4 py-3 text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-800 outline-none transition"
                    >
                    <p id="inputTelefonoError" class="text-red-600 text-sm font-semibold hidden -mt-3">Ingresa un número válido de 10 dígitos.</p>

                    <div class="flex gap-3">
                        <button type="button" id="btnCancelarTelefono" class="flex-1 bg-black/5 hover:bg-black/10 text-slate-600 font-bold py-4 rounded-2xl transition">
                            Cancelar
                        </button>
                        <button type="submit" id="btnGuardarTelefono" class="flex-1 bg-blue-800 hover:bg-blue-900 text-white font-bold py-4 rounded-2xl transition shadow-lg shadow-blue-800/20">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="modalFeedbackTelefono" class="fixed inset-0 z-[10060] flex items-center justify-center p-4 hidden">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"></div>
            <div class="bg-white/70 backdrop-blur-xl border border-white/50 shadow-2xl w-full max-w-sm relative z-10 p-8 rounded-3xl text-center">
                <div id="feedbackTelefonoIconWrap" class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6"></div>
                <h2 id="feedbackTelefonoTitulo" class="text-2xl font-bold text-slate-800 mb-2"></h2>
                <p id="feedbackTelefonoMensaje" class="text-slate-600 mb-8"></p>
                <button id="btnCerrarFeedbackTelefono" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-4 rounded-2xl transition shadow-lg">
                    Entendido
                </button>
            </div>
        </div>`;

        document.body.insertAdjacentHTML('beforeend', html);
    }

    // Muestra el modal para capturar el teléfono. Devuelve una Promise que
    // resuelve con el número ingresado, o null si el usuario cancela.
    function pedirTelefono(cliente) {
        ensurarModalesTelefono();

        return new Promise((resolve) => {
            const modal = document.getElementById('modalTelefono');
            const texto = document.getElementById('modalTelefonoTexto');
            const form = document.getElementById('formTelefono');
            const input = document.getElementById('inputTelefono');
            const errorMsg = document.getElementById('inputTelefonoError');
            const btnCancelar = document.getElementById('btnCancelarTelefono');

            texto.textContent = `El cliente "${cliente}" no tiene un número telefónico registrado. Por favor, ingrésalo para continuar:`;
            input.value = '';
            errorMsg.classList.add('hidden');
            input.classList.remove('ring-2', 'ring-red-500');
            modal.classList.remove('hidden');
            setTimeout(() => input.focus(), 50);

            const limpiar = () => {
                form.removeEventListener('submit', onSubmit);
                btnCancelar.removeEventListener('click', onCancelar);
                modal.classList.add('hidden');
            };

            const onSubmit = (e) => {
                e.preventDefault();
                const valor = input.value.trim();
                if (!valor || !/^\d{10,12}$/.test(valor)) {
                    errorMsg.classList.remove('hidden');
                    input.classList.add('ring-2', 'ring-red-500');
                    return;
                }
                limpiar();
                resolve(valor);
            };

            const onCancelar = () => {
                limpiar();
                resolve(null);
            };

            form.addEventListener('submit', onSubmit);
            btnCancelar.addEventListener('click', onCancelar);
        });
    }

    // Muestra el modal de feedback (éxito o error). Devuelve una Promise que
    // resuelve cuando el usuario toca "Entendido".
    function mostrarFeedbackTelefono(esExito, titulo, mensaje) {
        ensurarModalesTelefono();

        return new Promise((resolve) => {
            const modal = document.getElementById('modalFeedbackTelefono');
            const iconWrap = document.getElementById('feedbackTelefonoIconWrap');
            const tituloEl = document.getElementById('feedbackTelefonoTitulo');
            const mensajeEl = document.getElementById('feedbackTelefonoMensaje');
            const btnCerrar = document.getElementById('btnCerrarFeedbackTelefono');

            if (esExito) {
                iconWrap.className = "w-20 h-20 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-6";
                iconWrap.innerHTML = `<svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>`;
            } else {
                iconWrap.className = "w-20 h-20 bg-rose-500/20 rounded-full flex items-center justify-center mx-auto mb-6";
                iconWrap.innerHTML = `<svg class="w-10 h-10 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>`;
            }

            tituloEl.textContent = titulo;
            mensajeEl.textContent = mensaje;
            modal.classList.remove('hidden');

            const onCerrar = () => {
                btnCerrar.removeEventListener('click', onCerrar);
                modal.classList.add('hidden');
                resolve();
            };
            btnCerrar.addEventListener('click', onCerrar);
        });
    }

    // ==========================================
    // LÓGICA DE INTERCEPCIÓN
    // ==========================================

    // Obtiene el nombre del cliente de la cotización correspondiente al botón
    // "Facturar" que se clickeó. Se usa el data-folio-id del propio botón
    // (en vez de idFolioActual, que todavía no está seteado en este punto,
    // porque abrirFacturacion() aún no se ha ejecutado).
    function obtenerClienteActual(boton) {
        const folioId = boton?.dataset?.folioId ? parseInt(boton.dataset.folioId, 10) : null;

        if (folioId && typeof datosCotizaciones !== 'undefined' && Array.isArray(datosCotizaciones)) {
            const registro = datosCotizaciones.find(s => s.id === folioId);
            if (registro && registro.nombre_cliente && registro.nombre_cliente.trim()) {
                return registro.nombre_cliente.trim();
            }
        }

        // Respaldo: si por algo no se encontró vía folio, intentar leer el
        // input del carrito ya abierto (por si se llama en otro contexto)
        const inputCliente = document.querySelector('#tablaCarrito .cart-cliente');
        if (inputCliente && inputCliente.value.trim()) return inputCliente.value.trim();

        return null;
    }

    async function interceptarAccion(evento) {
        // Selector principal (data-accion) + respaldo por si el botón aún no
        // tiene ese atributo desplegado en el servidor (detecta por onclick).
        const boton = evento.target.closest('[data-accion="facturar"], button[onclick*="abrirFacturacion"]');
        if (!boton) return; // el clic no fue sobre un botón Facturar

        if (boton.dataset.telefonoValidado === "true") {
            return;
        }

        evento.stopImmediatePropagation();
        evento.preventDefault();

        const cliente = obtenerClienteActual(boton);
        if (!cliente) {
            await mostrarFeedbackTelefono(false, 'Error', 'No hay ningún cliente en el carrito.');
            return;
        }

        try {
            const respuesta = await fetch(`/view/home/verificar_telefono.php?cliente=${encodeURIComponent(cliente)}`);
            const resultado = await respuesta.json();

            if (!resultado.success) {
                await mostrarFeedbackTelefono(false, 'Error del Servidor', resultado.error);
                return;
            }

            if (resultado.tiene_telefono) {
                ejecutarAccionOriginal(boton);
                return;
            }

            // Pide el teléfono con el modal glassmorphism
            const telefono = await pedirTelefono(cliente);
            if (!telefono) return; // el usuario canceló

            const guardarRespuesta = await fetch('/view/home/verificar_telefono.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cliente, telefono })
            });
            const guardarResultado = await guardarRespuesta.json();

            if (guardarResultado.success) {
                await mostrarFeedbackTelefono(true, '¡Registrado!', 'El teléfono se guardó en la BD con éxito.');
                ejecutarAccionOriginal(boton);
            } else {
                await mostrarFeedbackTelefono(false, 'Error', 'No se pudo guardar el teléfono: ' + guardarResultado.error);
            }
        } catch (error) {
            console.error(error);
            await mostrarFeedbackTelefono(false, 'Error', 'Hubo un problema de conexión con el servidor.');
        }
    }

    // Simula de nuevo el click una vez aprobada la validación
    function ejecutarAccionOriginal(boton) {
        boton.dataset.telefonoValidado = "true";
        boton.click();

        setTimeout(() => {
            boton.removeAttribute('data-telefono-validado');
        }, 500);
    }

    // Enganchar la intercepción SOLO al botón Facturar, por delegación.
    // Como el botón se regenera en cada renderizar() (uno por cada folio),
    // no se puede usar getElementById con un id fijo: se escucha en el
    // documento y se filtra por [data-accion="facturar"] con closest().
    function inicializarInterceptores() {
        document.addEventListener('click', interceptarAccion, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarInterceptores);
    } else {
        inicializarInterceptores();
    }
})();


// ==========================================
// UI/UX: MODAL DE LIGA DE RASTREO (tras guardar facturación)
// ==========================================
function mostrarLigaRastreo(url, telefono) {
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 z-[10000] bg-black/50 flex items-center justify-center p-6';
    overlay.innerHTML = `
        <div class="bg-white rounded-2xl w-full max-w-sm p-6 shadow-2xl">
            <div class="w-12 h-12 rounded-full bg-green-50 text-green-600 flex items-center justify-center mb-4">
                <span class="material-symbols-outlined">location_on</span>
            </div>
            <p class="text-slate-700 font-semibold text-[15px] leading-snug mb-2">Liga de rastreo lista</p>
            <p class="text-slate-500 text-xs leading-snug mb-4 break-all">${url}</p>
            <div class="flex flex-col gap-2">
                <button data-accion="whatsapp" class="w-full h-12 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold transition-all active:scale-95 select-none flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">chat</span>
                    Enviar por WhatsApp
                </button>
                <button data-accion="copiar" class="w-full h-12 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition-all active:scale-95 select-none">
                    Copiar liga
                </button>
                <button data-accion="cerrar" class="w-full h-10 text-slate-500 text-sm font-medium">
                    Cerrar
                </button>
            </div>
        </div>`;
    document.body.appendChild(overlay);

    overlay.addEventListener('click', (e) => {
        const accion = e.target.closest('[data-accion]')?.dataset.accion;

        if (accion === 'whatsapp') {
            const mensaje = `Hola, aquí puedes seguir tu mudanza en tiempo real: ${url}`;
            if (telefono) {
                abrirWhatsapp(telefono, mensaje);
            } else {
                mostrarAlerta('No se encontró el teléfono del cliente para enviar', 'error');
            }
            return;
        }

        if (accion === 'copiar') {
            navigator.clipboard.writeText(url).then(() => {
                mostrarAlerta('Liga copiada al portapapeles');
            }).catch(() => {
                mostrarAlerta('No se pudo copiar la liga', 'error');
            });
            return;
        }

        if (accion === 'cerrar' || e.target === overlay) {
            document.body.removeChild(overlay);
        }
    });
}
// ==========================================
// WHATSAPP: abrir chat con mensaje precargado
// ==========================================
function abrirWhatsapp(telefono, mensaje) {
    // Deja solo dígitos (quita espacios, guiones, paréntesis, "+")
    let numero = String(telefono).replace(/\D/g, '');

    // Si viene sin lada de país (10 dígitos, México), se le antepone 52
    if (numero.length === 10) {
        numero = '52' + numero;
    }

    // Dentro de la app Android: usa el puente nativo, abre WhatsApp
    // directo con un Intent explícito, sin pasar por el navegador.
    if (window.Android && typeof window.Android.abrirWhatsapp === 'function') {
        window.Android.abrirWhatsapp(numero, mensaje || '');
        return;
    }

    // Fuera de la app (navegador normal): redirige en la misma pestaña
    // en vez de abrir una ventana/pestaña nueva.
    const url = `https://wa.me/${numero}?text=${encodeURIComponent(mensaje || '')}`;
    window.location.href = url;
}
