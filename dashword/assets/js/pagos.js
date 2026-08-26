// ===== VARIABLES GLOBALES =====
let confirmCallback = null;

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    
    const modalConfirm = document.getElementById('modalConfirm');
    const confirmBtn = document.getElementById('confirmBtn');
    
    // Cerrar con ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') cerrarConfirm();
    });

    // Cerrar al hacer click fuera
    modalConfirm.addEventListener('click', (e) => {
        if (e.target === modalConfirm) cerrarConfirm();
    });

    // Ejecutar callback al confirmar
    confirmBtn.addEventListener('click', () => {
        if (confirmCallback) {
            confirmCallback();
        }
        cerrarConfirm();
    });
});

// ===== FUNCIONES DE CONFIRMACIÓN =====
function mostrarConfirm(mensaje, callback) {
    document.getElementById('confirmMessage').textContent = mensaje;
    confirmCallback = callback;
    document.getElementById('modalConfirm').classList.remove('hidden');
}

function cerrarConfirm() {
    document.getElementById('modalConfirm').classList.add('hidden');
    confirmCallback = null;
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') cerrarConfirm();
});

document.getElementById('modalConfirm').addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalConfirm')) cerrarConfirm();
});

document.getElementById('confirmBtn').addEventListener('click', () => {
    if (confirmCallback) confirmCallback();
    cerrarConfirm();
});

function buscar(q) {
    let res = document.getElementById('resultados');
    if(q.length < 3) { res.classList.add('hidden'); return; }
    res.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm flex items-center justify-center gap-2"><div class="loading-spinner"></div> Buscando...</div>';
    res.classList.remove('hidden');
    fetch('buscar.php?q=' + q)
        .then(r => r.text())
        .then(html => {
            res.innerHTML = html || '<div class="p-4 text-center text-gray-400 text-sm">No se encontraron resultados</div>';
        });
}

document.addEventListener('click', (e) => {
    if (!document.getElementById('buscador').contains(e.target)) {
        document.getElementById('resultados').classList.add('hidden');
    }
});
// ===== BUSCADOR DE TABLA =====


function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    const baseClasses = "px-6 py-4 rounded-xl shadow-2xl border text-white font-medium flex items-center gap-3 transform transition-all duration-300 translate-y-0 opacity-100";
    const typeClasses = type === 'success' ? "bg-emerald-600 border-emerald-500" : "bg-red-600 border-red-500";
    toast.className = `${baseClasses} ${typeClasses}`;
    toast.innerHTML = `<i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} text-xl"></i><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
function generarPago(id, nombre, total, metodo = 'spei') {
    // Mapear métodos a labels
    const metodoLabels = {
        'spei': 'SPEI',
        'paynet': 'Paynet',
        'link': 'Link de Pago'
    };
    
    const label = metodoLabels[metodo] || metodo;
    
    mostrarConfirm(`¿Generar pago ${label} para ${nombre}?`, () => {
        
        showToast('Procesando solicitud...', 'success');

        fetch('procesar_pago.php', { 
            method: 'POST', 
            body: new URLSearchParams({
                id: id,
                nombre: nombre,
                total: total,
                metodo: metodo
            })
        })
        .then(r => r.text())
        .then(text => {
            try {
                const data = JSON.parse(text);

                if(data.success){
                    const mensajes = {
                        'spei': 'CLABE SPEI generada correctamente',
                        'paynet': 'Referencia Paynet generada correctamente',
                        'link': 'Link de pago generado correctamente'
                    };
                    
                    showToast(mensajes[metodo] || 'Pago generado correctamente', 'success');
                    setTimeout(() => location.reload(), 1500);
                }else{
                    showToast('Error: '+data.error,'error');
                }
            } catch(e){
                console.error("Error parsing JSON:", text);
                showToast("Error del servidor", "error");
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showToast("Error de conexión: " + err.message, "error");
        });
    });
}
function verDetalle(clabe, paynet, payment_url, id, nombre, monto, status = '') {
    const modal = document.getElementById('modalDetalle');
    const contenido = document.getElementById('contenidoModal');
    
    let datosPago = '';

    if (clabe) {
        datosPago += `<div><p class="text-gray-500 font-semibold">CLABE Interbancaria SPEI:</p><div class="flex items-center justify-between bg-blue-50 p-3 rounded border border-blue-100 mt-1"><p class="font-mono text-lg font-bold text-blue-700 break-all">${clabe}</p><button onclick="navigator.clipboard.writeText('${clabe}'); showToast('CLABE copiada','success')" class="text-blue-600 hover:text-blue-800 ml-2"><i class="bi bi-clipboard"></i></button></div></div>`;
    }

    if (paynet) {
        datosPago += `<div><p class="text-gray-500 font-semibold">Referencia Paynet:</p><div class="bg-gray-100 p-3 rounded border mt-1"><p class="font-mono text-lg font-bold text-gray-800 break-all">${paynet}</p></div></div>`;
    }

    if (payment_url) {
        datosPago += `
        <div>
            <p class="text-gray-500 font-semibold">Enlace de Pago (Link):</p>
            <div class="flex items-center justify-between bg-purple-50 p-3 rounded border border-purple-100 mt-1 gap-2">
                <input type="text" value="${payment_url}" readonly class="w-full font-mono text-xs text-purple-700 bg-transparent outline-none select-all truncate">
                <button onclick="navigator.clipboard.writeText('${payment_url}'); showToast('Link copiado','success')" class="text-purple-600 hover:text-purple-800 shrink-0 px-2"><i class="bi bi-clipboard"></i></button>
            </div>
            <div class="mt-2">
                <a href="${payment_url}" target="_blank" class="block w-full text-center py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm">
                    <i class="bi bi-box-arrow-up-right"></i> Abrir link de pago
                </a>
            </div>
        </div>`;
    }

    if (!clabe && !paynet && !payment_url) {
        datosPago = `<div class="bg-yellow-50 border border-yellow-200 p-3 rounded">No hay datos de pago generados.</div>`;
    }

    let statusBadge = '';
    if (status) {
        let statusClass = 'bg-slate-100 text-slate-700 border-slate-300';
        let statusText = status;
        
        switch(status) {
            case 'completed':
            case 'charge_success':
                statusClass = 'bg-green-50 text-green-700 border-green-200';
                statusText = 'Pagado';
                break;
            case 'pending':
            case 'charge_pending':
                statusClass = 'bg-amber-50 text-amber-700 border-amber-200';
                statusText = 'Pendiente';
                break;
            case 'failed':
                statusClass = 'bg-red-50 text-red-700 border-red-200';
                statusText = 'Fallido';
                break;
            case 'cancelled':
                statusClass = 'bg-gray-100 text-gray-700 border-gray-300';
                statusText = 'Cancelado';
                break;
        }
        
        statusBadge = `<div><span class="px-3 py-1 ${statusClass} border rounded-full text-[10px] font-bold uppercase tracking-widest">${statusText}</span></div>`;
    }

    contenido.innerHTML = `
    <div class="text-sm space-y-4">
        <div>
            <p class="text-gray-500 font-semibold">Cliente:</p>
            <p class="text-gray-900 font-bold">${nombre}</p>
        </div>
        ${statusBadge}
        ${datosPago}
        <div>
            <p class="text-gray-500 font-semibold">Monto a pagar:</p>
            <p class="text-2xl font-bold text-emerald-600">$${monto}</p>
        </div>
        <button onclick="compartirFicha(${id})" class="flex items-center justify-center gap-2 w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-xl font-bold transition-all">
            <i class="bi bi-share-fill"></i>
            Compartir Orden de Pago
        </button>
    </div>
    `;

    modal.classList.remove('hidden');
}


// ===== COMPARTIR PDF CON ACCIONES NATIVAS DE ANDROID =====

function blobToBase64(blob) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result.split(',')[1]);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
    });
}

async function compartirFicha(id) {
    const url = `generar_pdf.php?id=${id}&t=${Date.now()}`;

    // 1) Preferir el puente nativo de Android: usa Android.sharePdf, que ya
    // existe en MainActivity (guarda el PDF vía FileProvider y dispara el
    // Intent.ACTION_SEND nativo, el mismo mecanismo que usan las exportaciones
    // de tabla). Así se abre el selector de apps del sistema en vez de una
    // pestaña/ventana nueva del WebView.
    if (window.Android && typeof window.Android.sharePdf === 'function') {
        try {
            showToast('Preparando archivo...', 'success');
            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const blob = await response.blob();
            const base64 = await blobToBase64(blob);
            window.Android.sharePdf(base64, `Orden_de_pago_${id}.pdf`);
            return;
        } catch (e) {
            console.error('Error compartiendo vía Android bridge:', e);
            showToast('Error al preparar el archivo: ' + e.message, 'error');
            return;
        }
    }

    // 2) Fallback si se abre fuera del WebView (navegador normal con Web Share API)
    if (navigator.share) {
        try {
            const response = await fetch(url);
            const blob = await response.blob();
            const file = new File([blob], `Orden de pago_${id}.pdf`, { type: "application/pdf" });
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                await navigator.share({ title: "Orden de Pago", text: "Orden de pago El Lince", files: [file] });
                return;
            }
        } catch (e) { console.error(e); }
    }

    // 3) Último recurso: abrir el PDF en pestaña nueva
    window.open(url, "_blank");
}

function eliminarPago(id){
    mostrarConfirm('¿Ocultar este registro de pago?', () => {
        const fila = document.querySelector(`tr[data-pago-id="${id}"]`);
        if (!fila) { showToast("Error: No se encontró el registro", "error"); return; }
        fetch('eliminar_pago.php',{method:'POST', body:new URLSearchParams({id:id})})
        .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.text(); })
        .then(text => {
            if (!text.trim().startsWith('{')) { console.error('Respuesta no JSON:', text); showToast("Error del servidor", "error"); return; }
            const data = JSON.parse(text);
            if(data.success){
                showToast("Pago archivado correctamente", "success");
                fila.style.transition = 'opacity 0.3s ease-out, max-height 0.3s ease-out';
                fila.style.opacity = '0';
                fila.style.maxHeight = '0';
                fila.style.overflow = 'hidden';
                setTimeout(()=>{ fila.remove(); }, 300);
            }else{ showToast(data.error,"error"); }
        })
        .catch(err => { console.error('Error:', err); showToast("Error: " + err.message, "error"); });
    });
}

function restaurarPago(id){
    mostrarConfirm('¿Restaurar este pago?', () => {
        const fila = document.querySelector(`tr[data-pago-id="${id}"]`);
        if (!fila) { showToast("Error: No se encontró el registro", "error"); return; }
        fetch('restaurar_pago.php',{method:'POST', body:new URLSearchParams({id:id})})
        .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.text(); })
        .then(text => {
            if (!text.trim().startsWith('{')) { console.error('Respuesta no JSON:', text); showToast("Error del servidor", "error"); return; }
            const data = JSON.parse(text);
            if(data.success){
                showToast("Pago restaurado correctamente", "success");
                fila.style.transition = 'opacity 0.3s ease-out, max-height 0.3s ease-out';
                fila.style.opacity = '0';
                fila.style.maxHeight = '0';
                fila.style.overflow = 'hidden';
                setTimeout(()=>{ fila.remove(); }, 300);
            }else{ showToast(data.error,"error"); }
        })
        .catch(err => { console.error('Error:', err); showToast("Error: " + err.message, "error"); });
    });
}
