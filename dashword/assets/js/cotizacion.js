document.addEventListener("DOMContentLoaded", () => {
    cargarCotizaciones();

    // Buscador con Debounce (optimización de rendimiento)
    let debounceTimer;
    document.getElementById("BuscadorCotizaciones").addEventListener("keyup", function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const filtro = this.value.toLowerCase();
            document.querySelectorAll("#tablaCotizaciones tbody tr").forEach(fila => {
                fila.style.display = fila.textContent.toLowerCase().includes(filtro) ? "" : "none";
            });
        }, 300);
    });

    document.querySelectorAll('.calcular-total').forEach(input => {
        input.addEventListener('input', actualizarSumaModal);
    });
});

function cargarCotizaciones() {
    fetch("api_cotizaciones.php") 
        .then(res => res.json())
        .then(data => {
            const tbody = document.querySelector("#tablaCotizaciones tbody");
            if (data.success && data.data.length > 0) {
                // Renderizado en bloque para máxima velocidad
                tbody.innerHTML = data.data.map(row => `
                    <tr>
                        <td>${row.id}</td>
                        <td class="text-nowrap">${row.nombre_cliente || ''}</td>
                        <td class="text-nowrap">${row.telefono || ''}</td> 
                        <td class="text-nowrap">${row.tipo_servicio || ''}</td>
                        <td class="text-nowrap">${row.inmueble || ''}</td>
                        <td class="text-nowrap">${row.destino || ''}</td>
                        <td class="text-nowrap">${row.direccion_origen || ''}</td>
                        <td class="text-nowrap">${row.direccion_destino || ''}</td>
                        <td class="text-nowrap">${row.tipo_camioneta || ''}</td>
                        <td class="col-inventario">${row.inventario || ''}</td>
                        <td>${row.cargadores || ''}</td>
                        <td>${row.maniobra || ''}</td>
                        <td>${row.total || ''}</td>
                        <td class="text-nowrap">${row.fecha_creacion || ''}</td>
                        <td class="text-center">
                              <div class="d-inline-flex gap-1">
                                <button class="btn btn-sm btn-primary rounded-1" onclick="editar(${row.id})">
                                  <i class="bi bi-pencil-square"></i> Editar
                                </button>
                                
                                <button class="btn btn-sm btn-danger rounded-1" onclick="eliminar(${row.id})">
                                  <i class="bi bi-trash"></i> Eliminar
                                </button>
                              </div>
                            </td>

                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="15" class="text-center">Sin datos disponibles</td></tr>`;
            }
        });
}

function eliminar(id) {
    Swal.fire({
        title: "¿Seguro?", text: "¡Esta acción es irreversible!", icon: "warning",
        showCancelButton: true, confirmButtonText: "Sí, eliminar",
        buttonsStyling: false,
        customClass: { actions: 'swal-iso-actions', confirmButton: 'swal-iso-btn swal-iso-danger', cancelButton: 'swal-iso-btn swal-iso-cancel' }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch("eliminar_cotizacion.php", { method: "POST", headers: {"Content-Type": "application/x-www-form-urlencoded"}, body: "id=" + id })
            .then(res => res.json()).then(data => {
                if (data.success) { 
                    Swal.fire({title: "¡Eliminado!", icon: "success", buttonsStyling: false, customClass: { actions: 'swal-iso-actions', confirmButton: 'swal-iso-btn swal-iso-success' }});
                    cargarCotizaciones(); 
                }
            });
        }
    });
}

function guardarCambios() {
    const data = { id: document.getElementById("edit_id").value, nombre_cliente: document.getElementById("edit_cliente").value, telefono: document.getElementById("edit_telefono").value, maniobra: document.getElementById("edit_maniobra").value, total: document.getElementById("edit_total").value };
    
    fetch("actualizar_cotizacion.php", { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: new URLSearchParams(data).toString() })
    .then(res => res.json()).then(data => {
        if (data.success) {
            document.querySelector("#modalEditar .btn-close").click();
            Swal.fire({ title: "¡Éxito!", icon: "success", buttonsStyling: false, customClass: { actions: 'swal-iso-actions', confirmButton: 'swal-iso-btn swal-iso-success' } });
            cargarCotizaciones();
        }
    });
}

function editar(id) {
    fetch("obtener_cotizacion.php?id=" + id).then(res => res.json()).then(data => {
        if (data.success) {
            let c = data.data;
            document.getElementById("edit_id").value = c.id;
            document.getElementById("edit_cliente").value = c.nombre_cliente;
            document.getElementById("edit_telefono").value = c.telefono || ''; 
            document.getElementById("edit_total").value = c.total;
            document.getElementById("edit_maniobra").value = c.maniobra || 0;
            actualizarSumaModal();
            new bootstrap.Modal(document.getElementById("modalEditar")).show();
        }
    });
}

function actualizarSumaModal() {
    let suma = (parseFloat(document.getElementById("edit_maniobra").value) || 0) + (parseFloat(document.getElementById("edit_total").value) || 0);
    document.getElementById("edit_suma_final").value = "$ " + suma.toFixed(2);
}