document.addEventListener("DOMContentLoaded", cargarRastreo);

function cargarRastreo() {
    fetch("api_rastreo_admin.php")
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById("tablaRastreo");
            tbody.innerHTML = "";

            if (data.success && data.data.length > 0) {
                tbody.innerHTML = data.data.map(row => {
                    const estadoColor = row.estado === 'activo'
                        ? 'bg-emerald-50 text-emerald-700'
                        : row.estado === 'completado'
                            ? 'bg-blue-50 text-blue-700'
                            : 'bg-slate-100 text-slate-500';

                    const estaExpirado = row.estado === 'expirado';

                    return `
                        <tr class="hover:bg-slate-50/80" id="fila-liga-${row.id}">
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-500">#${row.servicio_id ?? '—'}</td>
                            <td class="px-5 py-3.5 font-medium text-slate-900">${row.cliente_nombre || 'Sin nombre'}</td>
                            <td class="px-5 py-3.5">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-medium ${estadoColor}" id="badge-estado-${row.id}">${row.estado}</span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">${row.creado_en || '—'}</td>
                            <td class="px-5 py-3.5 text-xs text-slate-500" id="expira-${row.id}">${row.expira_en || '—'}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <button onclick="copiarLink('${row.link_rastreo}', this)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-xs font-medium border-0 cursor-pointer">
                                        <i class="bi bi-clipboard"></i>
                                        <span>Copiar link</span>
                                    </button>
                                    ${estaExpirado ? `
                                        <button onclick="regenerarLiga(${row.id}, this)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 text-orange-700 hover:bg-orange-100 rounded-lg text-xs font-medium border-0 cursor-pointer">
                                            <i class="bi bi-arrow-clockwise"></i>
                                            <span>Regenerar</span>
                                        </button>
                                    ` : ''}
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No hay enlaces registrados.</td></tr>`;
            }
        })
        .catch(() => {
            document.getElementById("tablaRastreo").innerHTML =
                `<tr><td colspan="6" class="px-5 py-12 text-center text-red-500">Error al conectar con el servidor.</td></tr>`;
        });
}

function regenerarLiga(id, btn) {
    btn.disabled = true;
    const original = btn.innerHTML;
    btn.innerHTML = `<i class="bi bi-arrow-repeat animate-spin"></i><span>Regenerando...</span>`;

    fetch("regenerar_liga.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + encodeURIComponent(id)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById(`badge-estado-${id}`);
            const expiraCell = document.getElementById(`expira-${id}`);
            if (badge) {
                badge.textContent = 'activo';
                badge.className = 'px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-700';
            }
            if (expiraCell) expiraCell.textContent = data.expira_en;
            btn.remove();
        } else {
            alert(data.error || 'No se pudo regenerar el link.');
            btn.disabled = false;
            btn.innerHTML = original;
        }
    })
    .catch(() => {
        alert('Error de conexión con el servidor.');
        btn.disabled = false;
        btn.innerHTML = original;
    });
}

function copiarLink(link, btn) {
    navigator.clipboard.writeText(link).then(() => {
        const original = btn.innerHTML;
        btn.innerHTML = `<i class="bi bi-check2"></i><span>Copiado</span>`;
        setTimeout(() => { btn.innerHTML = original; }, 1500);
    });
}
