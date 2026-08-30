// ==========================================
// FACTURACIONES UI
// ==========================================
//
// Funciones portadas del archivo legacy (DataTables) a este entorno
// de tarjetas tipo acordeón:
//   - Eliminar factura (solo admin)          -> /view/home/eliminar_factura.php
//   - Editar y guardar factura                -> /view/home/actualizar_factura.php
//     (campos editables: anticipo, iva, fecha_servicio, hora_servicio)
//   - Generar PDF de facturas seleccionadas    -> jsPDF + autoTable
//
// Requiere que la variable global `rolUsuario`, `nombreUsuarioLogueado` existan en la página,
// y que jQuery, flatpickr y jsPDF + jsPDF-autoTable ya estén cargados en el HTML.
//
// Las notificaciones y confirmaciones usan el mismo sistema propio de
// Cotizaciones (mostrarAlerta / confirmarAccion), así que ese archivo debe
// estar cargado en la misma página para que avisar() y confirmarAccionFacturas()
// se vean con el estilo de tarjetas; si no está disponible, hay un
// respaldo con alert()/confirm() nativos.

let datosFacturaciones = [];
let facturacionesAgrupadas = [];

// Carrito local para generación de PDF (independiente del carrito de venta)
let carritoPDFFacturas = [];


// ==========================================
// HELPERS
// ==========================================

// Evita XSS al insertar datos del servidor en innerHTML
function escapeHTML(str){

    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

}

function formatoMoneda(valor){

    const num = Number(valor);

    return (Number.isFinite(num) ? num : 0).toLocaleString(
        'es-MX',
        { style:'currency', currency:'MXN' }
    );

}

// Igual que en el archivo legacy: solo el rol "admin" puede
// eliminar/editar facturas.
function esAdmin(){

    return typeof rolUsuario !== 'undefined'
        && rolUsuario
        && rolUsuario.trim().toLowerCase() === 'admin';

}

// Confirmación estilizada: usa el modal propio de Cotizaciones
// (confirmarAccion) si está cargado en la página; si no, cae a confirm() nativo.
function confirmarAccionFacturas({ titulo, texto, confirmText = 'Sí, continuar' }){

    if(typeof confirmarAccion === 'function'){

        // confirmarAccion(mensaje, tituloBoton, tipo) -> viene de cotizaciones.js
        const mensaje = texto ? `${titulo}. ${texto}` : titulo;
        return confirmarAccion(mensaje, confirmText, 'peligro');

    }

    return Promise.resolve(window.confirm(`${titulo}\n${texto}`));

}

// Aviso/toast: usa la cola de toasts propia de Cotizaciones (mostrarAlerta)
// si está cargada en la página; si no, cae a un alert() nativo.
function avisar(titulo, texto, icono='success'){

    if(typeof mostrarAlerta === 'function'){

        // mostrarAlerta(mensaje, tipo) -> tipo es 'exito' | 'eliminado' | 'error' | 'info'
        const mapaTipos = { success:'exito', error:'error', warning:'info', info:'info' };
        mostrarAlerta(texto, mapaTipos[icono] || 'exito');

    }else{

        alert(`${titulo}: ${texto}`);

    }

}


// ==========================================
// CARGAR DATOS
// ==========================================

async function inicializarFacturaciones(){

    try {

        const response = await fetch(
            '/view/home/getfacturaciones.php?_=' + new Date().getTime()
        );

        if(!response.ok){
            throw new Error(`HTTP ${response.status}`);
        }

        const json = await response.json();

        console.log("Respuesta BD:", json);


        if(json.success){

            datosFacturaciones = json.data || [];

            agruparPorServicio();

            renderizarFacturaciones(facturacionesAgrupadas);

        }else{

            renderizarFacturaciones([]);

        }


    }catch(error){

        console.error(
            "Error cargando facturaciones:",
            error
        );

        mostrarErrorCarga();

    }

}


function mostrarErrorCarga(){

    const container = document.getElementById('listaFacturaciones');

    if(!container) return;

    container.innerHTML = `
        <div class="bg-white rounded-2xl border p-8 text-center">
            <p class="text-red-500 font-semibold">
                Ocurrió un error al cargar las facturaciones. Intenta de nuevo.
            </p>
        </div>
    `;

}



// ==========================================
// AGRUPAR POR SERVICIO
// ==========================================

function agruparPorServicio(){

    const grupos = {};


    datosFacturaciones.forEach(item=>{

        const rawKey = (item.servicio_id ?? item.id);
        const key = Number(rawKey);


        if(!grupos[key]){

            grupos[key] = {

                servicio_id:key,

                cliente:item.cliente || 'Cliente',

                asesor:item.asesor || 'Asesor',

                fecha:item.fecha,

                fecha_servicio:item.fecha_servicio,

                hora_servicio:item.hora_servicio,

                anticipo:Number(item.anticipo) || 0,

                iva:Number(item.iva) || 0,

                productos:[],

                total:0,

                ultimo_id:Number(item.id) || 0

            };

        }


        grupos[key].productos.push(item);


        // La fecha/hora programada puede venir vacía en algunos productos
        // del mismo folio; nos quedamos con el primer valor no vacío que
        // encontremos en cualquiera de ellos.
        if(!grupos[key].fecha_servicio && item.fecha_servicio){
            grupos[key].fecha_servicio = item.fecha_servicio;
        }

        if(!grupos[key].hora_servicio && item.hora_servicio){
            grupos[key].hora_servicio = item.hora_servicio;
        }


        const totalItem = Number(item.total);

        grupos[key].total += Number.isFinite(totalItem) ? totalItem : 0;



        const idActual = Number(item.id);

        if(Number.isFinite(idActual) && idActual > grupos[key].ultimo_id){

            grupos[key].ultimo_id = idActual;

        }


    });



    facturacionesAgrupadas = Object.values(grupos);


    // Los productos de cada folio deben listarse del id más chico al más grande
    facturacionesAgrupadas.forEach(grupo=>{

        grupo.productos.sort((a,b)=> Number(a.id) - Number(b.id));

    });


    facturacionesAgrupadas.sort((a,b)=>{

        return b.ultimo_id - a.ultimo_id;

    });


}



// ==========================================
// RENDER
// ==========================================

function renderizarFacturaciones(data){

const container =
document.getElementById('listaFacturaciones');


if(!container)return;


if(data.length===0){

container.innerHTML=`

<div class="bg-white rounded-2xl border p-8 text-center">

<p class="text-slate-500 font-semibold">
No existen facturaciones
</p>

</div>

`;

return;

}


const admin = esAdmin();


container.innerHTML=data.map(f=>{


const total = formatoMoneda(f.total);

const cliente = escapeHTML(f.cliente);
const asesor = escapeHTML(f.asesor);
const fechaServicio = escapeHTML(f.fecha_servicio || '');
const horaServicio = escapeHTML(f.hora_servicio || '');



return `

<div class="
bg-white
rounded-2xl
border
border-slate-200/70
border-l-4
border-l-blue-800
shadow-sm
hover:shadow-md
transition
overflow-hidden
">


<div 
onclick="toggleFactura(${f.servicio_id})"
class="
p-5
flex
justify-between
items-start
cursor-pointer
select-none
hover:bg-slate-50
active:bg-slate-100
transition-colors
duration-150
">


<div>

<span class="
text-xs
font-bold
text-blue-800
bg-blue-50
px-2
py-1
rounded-lg
">

FOLIO #${f.servicio_id}

</span>


<h3 class="
font-bold
mt-2
text-slate-800
">

${cliente}

</h3>


<p class="
text-xs
text-slate-500
">

Asesor: ${asesor}

</p>


</div>



<div class="text-right">


<p class="text-xs text-slate-400">
TOTAL
</p>


<p class="font-black text-lg">

${total}

</p>


<span 
id="icon-${f.servicio_id}"
class="material-symbols-outlined text-slate-400 transition-transform duration-200">

expand_more

</span>


</div>


</div>



<div 
id="body-${f.servicio_id}"
class="
hidden
border-t
p-5
">


<div class="
grid
grid-cols-2
gap-3
mb-4
">


<div class="group relative bg-slate-50 hover:bg-slate-100 border border-slate-200 p-3 rounded-xl transition-colors duration-150">

<p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 flex items-center gap-1">
    <span class="material-symbols-outlined text-[14px] leading-none">calendar_month</span>
    Fecha servicio
</p>

<b
class="fecha-servicio-editable block mt-1 text-sm font-bold text-slate-800 rounded-lg px-2 py-1 -mx-2 outline-none transition-all duration-150 ${admin ? 'cursor-pointer hover:bg-blue-50 hover:text-blue-800 focus:ring-2 focus:ring-blue-500/40 focus:bg-white' : ''}"
data-servicio-id="${f.servicio_id}"
>${fechaServicio || '—'}</b>

${admin ? `<span class="material-symbols-outlined absolute top-3 right-3 text-[15px] text-slate-300 group-hover:text-blue-600 transition-colors pointer-events-none">edit</span>` : ``}

</div>



<div class="group relative bg-slate-50 hover:bg-slate-100 border border-slate-200 p-3 rounded-xl transition-colors duration-150">

<p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 flex items-center gap-1">
    <span class="material-symbols-outlined text-[14px] leading-none">schedule</span>
    Hora
</p>

<b
class="hora-servicio-editable block mt-1 text-sm font-bold text-slate-800 rounded-lg px-2 py-1 -mx-2 outline-none transition-all duration-150 ${admin ? 'cursor-pointer hover:bg-blue-50 hover:text-blue-800 focus:ring-2 focus:ring-blue-500/40 focus:bg-white' : ''}"
data-servicio-id="${f.servicio_id}"
>${horaServicio || '—'}</b>

${admin ? `<span class="material-symbols-outlined absolute top-3 right-3 text-[15px] text-slate-300 group-hover:text-blue-600 transition-colors pointer-events-none">edit</span>` : ``}

</div>


<div class="group relative bg-slate-50 ${admin ? 'hover:bg-slate-100' : ''} border border-slate-200 p-3 rounded-xl transition-colors duration-150">

<p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 flex items-center gap-1">
    <span class="material-symbols-outlined text-[14px] leading-none">payments</span>
    Anticipo
</p>

<b
class="anticipo-editable block mt-1 text-sm font-bold text-slate-800 rounded-lg px-2 py-1 -mx-2 outline-none transition-all duration-150 ${admin ? 'cursor-text hover:bg-blue-50 focus:ring-2 focus:ring-blue-500/40 focus:bg-white' : 'text-slate-500'}"
data-servicio-id="${f.servicio_id}"
${admin ? 'contenteditable="true"' : 'contenteditable="false"'}
>${Number(f.anticipo ?? 0)}</b>

${admin ? `<span class="material-symbols-outlined absolute top-3 right-3 text-[15px] text-slate-300 group-hover:text-blue-600 transition-colors pointer-events-none">edit</span>` : ``}

</div>


<div class="group relative bg-slate-50 ${admin ? 'hover:bg-slate-100' : ''} border border-slate-200 p-3 rounded-xl transition-colors duration-150">

<p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 flex items-center gap-1">
    <span class="material-symbols-outlined text-[14px] leading-none">percent</span>
    IVA
</p>

<b
class="iva-editable block mt-1 text-sm font-bold text-slate-800 rounded-lg px-2 py-1 -mx-2 outline-none transition-all duration-150 ${admin ? 'cursor-text hover:bg-blue-50 focus:ring-2 focus:ring-blue-500/40 focus:bg-white' : 'text-slate-500'}"
data-servicio-id="${f.servicio_id}"
${admin ? 'contenteditable="true"' : 'contenteditable="false"'}
>${Number(f.iva ?? 0)}</b>

${admin ? `<span class="material-symbols-outlined absolute top-3 right-3 text-[15px] text-slate-300 group-hover:text-blue-600 transition-colors pointer-events-none">edit</span>` : ``}

</div>


</div>

${admin ? `<p class="text-[11px] text-slate-400 flex items-center gap-1 -mt-2 mb-4"><span class="material-symbols-outlined text-[13px]">info</span>Toca un campo para editarlo</p>` : ``}



<h4 class="font-bold mb-3">
Productos
</h4>


${f.productos.map(p=>{

    const pid = Number(p.id);

    const producto = escapeHTML(p.producto);
    const cantidad = escapeHTML(p.cantidad);
    const subtotal = formatoMoneda(p.subtotal ?? 0);
    const totalProducto = formatoMoneda(p.total);

    return `

<div class="
producto-card
bg-slate-50
hover:bg-slate-100
border
border-slate-200
rounded-xl
p-3
mb-2
transition-colors
duration-150
"
data-id="${pid}"
data-cliente="${escapeHTML(f.cliente)}"
data-asesor="${escapeHTML(f.asesor)}"
data-fecha="${escapeHTML(f.fecha || '')}"
data-producto="${producto}"
data-cantidad="${cantidad}"
data-subtotal="${Number(p.subtotal ?? 0)}"
data-total="${Number(p.total ?? 0)}"
>


<div class="flex justify-between items-start gap-3">

<b class="text-sm text-slate-800">
${producto}
</b>


<span class="text-sm font-bold text-slate-900 whitespace-nowrap">
${totalProducto}
</span>


</div>


<p class="text-xs text-slate-500 mt-1">
Cantidad: <span class="font-semibold text-slate-600">${cantidad}</span>
&nbsp;•&nbsp;
Subtotal: <span class="font-semibold text-slate-600">${subtotal}</span>
</p>


</div>

`;

}).join('')}



<div class="
mt-4
bg-blue-50
p-4
rounded-xl
flex
justify-between
">


<b>
Total
</b>


<b>
${total}
</b>


</div>


${admin ? `
<button
    class="btnGuardarServicio w-full mt-3 min-h-[48px] rounded-2xl bg-blue-800 text-white font-bold text-sm
        flex items-center justify-center gap-2
        transition-all duration-150
        hover:bg-blue-900 active:scale-[0.98]
        disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
    data-servicio-id="${f.servicio_id}"
>
    <span class="material-symbols-outlined text-lg">save</span>
    Guardar cambios de este servicio
</button>
` : ``}

<button
    class="btnAgregarPDFServicio w-full mt-2 min-h-[48px] rounded-2xl bg-slate-900 text-white font-bold text-sm
        flex items-center justify-center gap-2
        transition-all duration-150
        hover:bg-slate-800 active:scale-[0.98]
        disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
    data-servicio-id="${f.servicio_id}"
>
    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
    Agregar todo a PDF
</button>

${admin ? `
<button
    class="btnEliminarServicio w-full mt-2 min-h-[48px] rounded-2xl bg-white text-red-600 font-bold text-sm
        border-2 border-red-200
        flex items-center justify-center gap-2
        transition-all duration-150
        hover:bg-red-50 hover:border-red-300 active:scale-[0.98]
        disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
    data-servicio-id="${f.servicio_id}"
>
    <span class="material-symbols-outlined text-lg">delete</span>
    Eliminar este servicio completo
</button>
` : ``}



</div>


</div>


`;

}).join('');

}



// ==========================================
// TOGGLE
// ==========================================

function toggleFactura(id){

const body =
document.getElementById(`body-${id}`);

const icon =
document.getElementById(`icon-${id}`);

if(!body || !icon) return;


body.classList.toggle('hidden');


icon.textContent =
body.classList.contains('hidden')
?
"expand_more"
:
"expand_less";

}



// ==========================================
// BUSCADOR
// ==========================================

function filtrarFacturaciones(){

const valor =
document
.getElementById('BuscadorFacturaciones')
.value
.toLowerCase();



const filtradas =
facturacionesAgrupadas.filter(f=>{

return (

f.cliente.toLowerCase().includes(valor)

||

String(f.servicio_id).includes(valor)

||

f.asesor.toLowerCase().includes(valor)

);

});


renderizarFacturaciones(filtradas);

}



// ==========================================
// ELIMINAR SERVICIO COMPLETO (solo admin)
// ==========================================

document.addEventListener('click', async (e)=>{

    const btn = e.target.closest('.btnEliminarServicio');

    if(!btn) return;

    if(!esAdmin()){
        avisar('Sin permiso', 'No tienes permiso para eliminar', 'error');
        return;
    }

    const servicioId = btn.dataset.servicioId;

    const body = document.getElementById(`body-${servicioId}`);

    if(!body){
        avisar('Error', 'No se encontró el servicio en pantalla.', 'error');
        return;
    }

    const tarjetas = body.querySelectorAll('.producto-card');

    if(tarjetas.length === 0){
        avisar('Aviso', 'Este servicio no tiene productos para eliminar.', 'info');
        return;
    }

    const ids = Array.from(tarjetas).map(card => card.dataset.id);

    const confirmado = await confirmarAccionFacturas({
        titulo: '¿Estás seguro?',
        texto: `Se eliminarán ${ids.length} producto(s) de este servicio de forma permanente.`,
        confirmText: 'Sí, eliminar todo'
    });

    if(!confirmado) return;

    btn.disabled = true;

    try{

        const resultados = await Promise.all(
            ids.map(async id => {

                try{

                    const res = await fetch('/view/home/eliminar_factura.php', {
                        method: 'POST',
                        body: new URLSearchParams({ id })
                    });

                    if(!res.ok) throw new Error(`HTTP ${res.status}`);

                    const resp = await res.json();

                    return { id, ...resp };

                }catch(error){

                    return { id, success:false, error: error.message };

                }

            })
        );

        const fallidos = resultados.filter(r => !r.success);

        if(fallidos.length === 0){

            avisar('¡Eliminado!', 'Servicio eliminado correctamente.');

        }else{

            console.error('Fallos al eliminar servicio:', fallidos);

            avisar(
                'Eliminación parcial',
                `${resultados.length - fallidos.length} de ${resultados.length} producto(s) se eliminaron. ` +
                `Falló(ron): ${fallidos.map(f => `#${f.id} (${f.error})`).join(', ')}`,
                'warning'
            );

        }

        await inicializarFacturaciones();

    }catch(error){

        console.error(error);

        avisar('Error', 'Error al conectar con el servidor.', 'error');

    }finally{

        btn.disabled = false;

    }

});



// ==========================================
// GUARDAR FACTURA (editar) - solo admin
// ==========================================

document.addEventListener('click', async (e)=>{

    const btn = e.target.closest('.btnGuardarServicio');

    if(!btn) return;

    if(!esAdmin()){
        avisar('Sin permiso', 'No tienes permiso para guardar', 'error');
        return;
    }

    const servicioId = btn.dataset.servicioId;

    const body = document.getElementById(`body-${servicioId}`);

    if(!body){
        avisar('Error', 'No se encontró el servicio en pantalla.', 'error');
        return;
    }

    const tarjetas = body.querySelectorAll('.producto-card');

    if(tarjetas.length === 0){
        avisar('Aviso', 'Este servicio no tiene productos para guardar.', 'info');
        return;
    }

    const fechaServicio = document
        .querySelector(`.fecha-servicio-editable[data-servicio-id="${servicioId}"]`)
        ?.textContent.trim() || '';

    const horaServicio = document
        .querySelector(`.hora-servicio-editable[data-servicio-id="${servicioId}"]`)
        ?.textContent.trim() || '';

    const anticipo = parseFloat(
        document.querySelector(`.anticipo-editable[data-servicio-id="${servicioId}"]`)?.textContent.trim()
    ) || 0;

    const iva = parseFloat(
        document.querySelector(`.iva-editable[data-servicio-id="${servicioId}"]`)?.textContent.trim()
    ) || 0;

    const payloads = Array.from(tarjetas).map(card => {

        return {
            id: card.dataset.id,
            asesor: card.dataset.asesor,
            fecha: card.dataset.fecha,
            cliente: card.dataset.cliente,
            fecha_servicio: fechaServicio,
            hora_servicio: horaServicio,
            producto: card.dataset.producto,
            cantidad: card.dataset.cantidad,
            anticipo,
            subtotal: card.dataset.subtotal,
            iva,
            total: card.dataset.total
        };

    });

    const confirmado = await confirmarAccionFacturas({
        titulo: '¿Deseas actualizar este servicio?',
        texto: `Se guardarán ${payloads.length} producto(s) de este servicio.`,
        confirmText: 'Sí, guardar todo'
    });

    if(!confirmado) return;

    btn.disabled = true;

    try{

        const resultados = await Promise.all(
            payloads.map(async data => {

                try{

                    const res = await fetch('/view/home/actualizar_factura.php', {
                        method: 'POST',
                        body: new URLSearchParams(data)
                    });

                    if(!res.ok) throw new Error(`HTTP ${res.status}`);

                    const resp = await res.json();

                    return { id: data.id, ...resp };

                }catch(error){

                    return { id: data.id, success:false, error: error.message };

                }

            })
        );

        const fallidos = resultados.filter(r => !r.success);

        if(fallidos.length === 0){

            avisar('¡Éxito!', 'Servicio actualizado correctamente.');

        }else{

            console.error('Fallos al guardar servicio:', fallidos);

            avisar(
                'Guardado parcial',
                `${resultados.length - fallidos.length} de ${resultados.length} producto(s) se guardaron. ` +
                `Falló(ron): ${fallidos.map(f => `#${f.id} (${f.error})`).join(', ')}`,
                'warning'
            );

        }

        await inicializarFacturaciones();

    }catch(error){

        console.error(error);

        avisar('Error', 'Error de conexión con el servidor.', 'error');

    }finally{

        btn.disabled = false;

    }

});



// ==========================================
// EDITAR FECHA / HORA DE SERVICIO (inline, con flatpickr) - solo admin
// ==========================================

document.addEventListener('click', function(e){

    const celdaFecha = e.target.closest('.fecha-servicio-editable');

    if(celdaFecha && esAdmin() && typeof flatpickr !== 'undefined'){

        if(celdaFecha.querySelector('input')) return;

        const valor = celdaFecha.textContent.trim();

        celdaFecha.innerHTML = `<input type="text" class="w-[130px] text-sm font-bold text-slate-800 border-2 border-blue-500 rounded-lg px-2 py-1 outline-none focus:ring-2 focus:ring-blue-500/40">`;

        const input = celdaFecha.querySelector('input');
        input.value = valor;

        flatpickr(input, {
            locale: "es",
            dateFormat: "Y-m-d",
            defaultDate: valor || null,
            allowInput: false,
            onClose: function(selectedDates, dateStr){
                celdaFecha.textContent = dateStr;
            }
        });

        input.focus();

        return;

    }


    const celdaHora = e.target.closest('.hora-servicio-editable');

    if(celdaHora && esAdmin() && typeof flatpickr !== 'undefined'){

        if(celdaHora.querySelector('input')) return;

        const valor = celdaHora.textContent.trim();

        celdaHora.innerHTML = `<input type="text" class="w-[100px] text-sm font-bold text-slate-800 border-2 border-blue-500 rounded-lg px-2 py-1 outline-none focus:ring-2 focus:ring-blue-500/40">`;

        const input = celdaHora.querySelector('input');
        input.value = valor;

        flatpickr(input, {
            locale: "es",
            enableTime: true,
            noCalendar: true,
            time_24hr: true,
            dateFormat: "H:i",
            defaultDate: valor || null,
            onClose: function(selectedDates, dateStr){
                celdaHora.textContent = dateStr;
            }
        });

        input.focus();

    }

});



// ==========================================
// GENERAR PDF DE FACTURAS SELECCIONADAS
// ==========================================

function asegurarPanelPDFFacturas(){

    if(document.getElementById('panelPDFFacturas')) return;

    // Estilos forzados en línea: no dependen de que Tailwind compile clases
    // usadas solo aquí (este archivo puede no estar incluido en el "content"
    // que escanea Tailwind), y no pueden ser sobreescritos por CSS global
    // del proyecto (selectores por etiqueta, resets, etc.).
    const EST = {
        backdrop: 'display:none; position:fixed; inset:0; z-index:1050; background:rgba(15,23,42,.55); backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:16px; box-sizing:border-box;',
        card: 'background:#ffffff; width:100%; max-width:400px; border-radius:24px; box-shadow:0 25px 50px -12px rgba(0,0,0,.35); display:flex; flex-direction:column; max-height:85vh; overflow:hidden; font-family:inherit; box-sizing:border-box;',
        header: 'display:flex; justify-content:space-between; align-items:center; padding:20px 20px 12px 20px; border-bottom:1px solid #f1f5f9; flex-shrink:0; box-sizing:border-box;',
        title: 'display:flex; align-items:center; gap:8px; font-weight:900; color:#1e293b; font-size:14px; margin:0;',
        iconBlue: 'color:#1d4ed8; font-size:20px; line-height:1;',
        closeBtn: 'width:32px; height:32px; min-width:32px; border-radius:9999px; background:#f1f5f9; color:#64748b; border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:14px; padding:0; flex-shrink:0;',
        listWrap: 'flex:1 1 auto; overflow-y:auto; padding:12px 20px; display:flex; flex-direction:column; gap:8px; box-sizing:border-box;',
        footer: 'padding:12px 20px 20px 20px; border-top:1px solid #f1f5f9; flex-shrink:0; box-sizing:border-box;',
        totalRow: 'display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;',
        totalLabel: 'font-size:14px; color:#64748b; font-weight:600; margin:0;',
        totalValue: 'font-size:18px; font-weight:900; color:#0f172a; margin:0;',
        genBtn: 'width:100%; min-height:48px; border-radius:16px; background:#1e40af; color:#ffffff; font-weight:700; font-size:14px; border:none; display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; box-sizing:border-box;'
    };

    const panel = document.createElement('div');

    panel.id = 'panelPDFFacturas';

    panel.setAttribute('style', EST.backdrop);

    panel.innerHTML = `
        <div style="${EST.card}">

            <div style="${EST.header}">
                <b style="${EST.title}">
                    <span class="material-symbols-outlined" style="${EST.iconBlue}">picture_as_pdf</span>
                    Facturas para PDF
                </b>
                <button id="btnCerrarPanelPDFFacturas" style="${EST.closeBtn}">✕</button>
            </div>

            <div id="listaPDFFacturas" style="${EST.listWrap}"></div>

            <div style="${EST.footer}">
                <div style="${EST.totalRow}">
                    <b style="${EST.totalLabel}">Total</b>
                    <b id="totalPanelPDFFacturas" style="${EST.totalValue}">$0.00</b>
                </div>
                <button id="btnGenerarPDFFacturas" style="${EST.genBtn}">
                    <span class="material-symbols-outlined" style="font-size:18px; line-height:1;">picture_as_pdf</span>
                    Generar PDF
                </button>
            </div>

        </div>
    `;

    document.body.appendChild(panel);

    const mostrarPanel = () => { panel.style.display = 'flex'; };
    const ocultarPanel = () => { panel.style.display = 'none'; };

    panel._mostrar = mostrarPanel;
    panel._ocultar = ocultarPanel;

    // Cerrar al tocar el fondo oscurecido (fuera de la tarjeta)
    panel.addEventListener('click', (e)=>{
        if(e.target === panel) ocultarPanel();
    });

    document.getElementById('btnCerrarPanelPDFFacturas')
        .addEventListener('click', ocultarPanel);

    document.getElementById('btnGenerarPDFFacturas')
        .addEventListener('click', generarPDFFacturas);

}

function sumarAnticiposUnicos(items){

    const vistos = new Set();

    let total = 0;

    items.forEach(item => {

        if(vistos.has(item.servicio_id)) return;

        vistos.add(item.servicio_id);

        total += Number(item.anticipo) || 0;

    });

    return total;

}

function renderPanelPDFFacturas(){

    const lista = document.getElementById('listaPDFFacturas');

    if(!lista) return;

    if(carritoPDFFacturas.length === 0){

        lista.innerHTML = `<p style="color:#94a3b8; font-size:14px; text-align:center; padding:24px 0; margin:0;">Sin facturas seleccionadas.</p>`;

    }else{

        const EST_ITEM = 'display:flex; justify-content:space-between; align-items:flex-start; gap:12px; background:#f8fafc; border-radius:16px; padding:12px; font-size:14px; box-sizing:border-box;';
        const EST_NOMBRE = 'color:#1e293b; font-weight:700; line-height:1.3; display:block; margin:0;';
        const EST_SUB = 'color:#94a3b8; font-size:12px; display:block; margin-top:2px;';
        const EST_PRECIO = 'font-weight:700; color:#0f172a; white-space:nowrap;';
        const EST_QUITAR = 'width:28px; height:28px; min-width:28px; border-radius:9999px; background:#ffffff; color:#94a3b8; border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:12px; padding:0; flex-shrink:0;';

        lista.innerHTML = carritoPDFFacturas.map(item => `
            <div style="${EST_ITEM}" data-id="${item.id}">
                <div style="min-width:0;">
                    <b style="${EST_NOMBRE}">${escapeHTML(item.producto)}</b>
                    <span style="${EST_SUB}">${escapeHTML(item.cliente)} · Folio #${escapeHTML(item.servicio_id)}</span>
                </div>
                <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                    <span style="${EST_PRECIO}">${formatoMoneda(item.total)}</span>
                    <button class="btnQuitarPDFFactura" style="${EST_QUITAR}" data-id="${item.id}">✕</button>
                </div>
            </div>
        `).join('');

    }

    const sumaTotales = carritoPDFFacturas.reduce((acc, item) => acc + Number(item.total), 0);
    const sumaAnticipos = sumarAnticiposUnicos(carritoPDFFacturas);
    const totalGeneral = sumaTotales - sumaAnticipos;

    const totalEl = document.getElementById('totalPanelPDFFacturas');

    if(totalEl){
        totalEl.innerHTML = `
            ${formatoMoneda(totalGeneral)}
            <div class="text-xs text-slate-400 font-normal">
                (Subtotal ${formatoMoneda(sumaTotales)} − Anticipos ${formatoMoneda(sumaAnticipos)})
            </div>
        `;
    }

}

document.addEventListener('click', function(e){

    const btnAgregar = e.target.closest('.btnAgregarPDFServicio');

    if(btnAgregar){

        const servicioId = btnAgregar.dataset.servicioId;

        const body = document.getElementById(`body-${servicioId}`);

        if(!body) return;

        const tarjetas = body.querySelectorAll('.producto-card');

        if(tarjetas.length === 0){
            avisar('Aviso', 'Este servicio no tiene productos para agregar.', 'info');
            return;
        }

        const anticipoServicio = parseFloat(
            document.querySelector(`.anticipo-editable[data-servicio-id="${servicioId}"]`)?.textContent.trim()
        ) || 0;

        let agregados = 0;

        tarjetas.forEach(card => {

            const id = Number(card.dataset.id);

            if(carritoPDFFacturas.some(item => item.id === id)) return;

            carritoPDFFacturas.push({
                id,
                servicio_id: servicioId,
                cliente: card.dataset.cliente,
                asesor: card.dataset.asesor,
                producto: card.dataset.producto,
                cantidad: card.dataset.cantidad,
                anticipo: anticipoServicio,
                total: Number(card.dataset.total) || 0
            });

            agregados++;

        });

        // El carrito de PDF debe quedar ordenado del id más chico al más grande
        carritoPDFFacturas.sort((a,b)=> Number(a.id) - Number(b.id));

        asegurarPanelPDFFacturas();

        document.getElementById('panelPDFFacturas').style.display = 'flex';

        renderPanelPDFFacturas();

        if(agregados === 0){
            avisar('Aviso', 'Todos los productos de este servicio ya estaban en el carrito de PDF.', 'info');
        }

        return;

    }

    const btnQuitar = e.target.closest('.btnQuitarPDFFactura');

    if(btnQuitar){

        const id = Number(btnQuitar.dataset.id);

        carritoPDFFacturas = carritoPDFFacturas.filter(item => item.id !== id);

        renderPanelPDFFacturas();

    }

});


// ==========================================
// FLUJO DE PDF — Vista previa y compilación
// ==========================================

window.currentDocDataFacturas = null;
window.readyPdfFileFacturas = null;
window.readyPdfBlobFacturas = null;

function cerrarPreviewFacturas(){

    const modal = document.getElementById('modalDocumentoFacturas');

    if(modal) modal.classList.add('hidden');

    window.currentDocDataFacturas = null;
    window.readyPdfFileFacturas = null;
    window.readyPdfBlobFacturas = null;

}

function ensurePreviewModalExistsFacturas(){

    if(document.getElementById('modalDocumentoFacturas')) return;

    const modalHTML = `
    <div id="modalDocumentoFacturas" class="hidden fixed inset-0 z-[9999] bg-slate-100 flex flex-col w-screen h-screen overflow-hidden">
        <div class="p-4 px-6 flex justify-between items-center gap-3 bg-[#1400AD] text-white flex-shrink-0 select-none">
            <div class="flex items-center gap-2 min-w-0">
                <span class="material-symbols-outlined text-white flex-shrink-0">description</span>
                <h2 class="font-black text-sm tracking-wider uppercase truncate">Comprobante de Facturación</h2>
            </div>
        
        </div>
        <div class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-300 flex flex-col items-center">
            <div id="previewContentFacturas" class="w-full max-w-3xl bg-white p-6 sm:p-10 rounded-xl border border-slate-400 shadow-2xl font-sans"></div>
        </div>
        <div class="p-4 px-6 border-t border-slate-200 bg-white flex gap-3 shadow-[0_-4px_12px_rgba(0,0,0,0.05)] flex-shrink-0">
            <button onclick="cerrarPreviewFacturas()" class="flex-1 min-h-[56px] py-2 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl font-bold transition-all active:scale-95 flex items-center justify-center gap-2 text-sm leading-tight text-center outline-none">
                <span class="material-symbols-outlined text-lg flex-shrink-0">arrow_back</span><span>Regresar</span>
            </button>
            <button id="btnCompartirFacturas" onclick="ejecutarAccionFinalFacturas()" class="flex-[2] min-h-[56px] py-2 px-2 bg-[#1400AD] hover:bg-blue-800 text-white rounded-2xl font-black shadow-xl transition-all active:scale-95 flex items-center justify-center gap-2 text-sm leading-tight text-center outline-none">
                <span class="material-symbols-outlined text-xl flex-shrink-0">share</span><span>Compartir Facturación (PDF)</span>
            </button>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

}

// ==========================================
// VISTA PREVIA EN HTML (independiente del PDF real)
// ==========================================
// Se pinta de inmediato con los datos ya calculados, sin esperar
// a que jsPDF termine de compilar el PDF (que puede tardar por la
// carga del logo). Así el usuario siempre ve algo en el visor.
function renderPreviewHTMLFacturas(){

    const datos = window.currentDocDataFacturas;

    if(!datos) return;

    const filasProductos = datos.items.map(item => `
        <tr>
            <td class="border px-2 py-1 text-center">${escapeHTML(item.id)}</td>
            <td class="border px-2 py-1">${escapeHTML(item.producto)}</td>
            <td class="border px-2 py-1 text-center">${escapeHTML(item.cantidad)}</td>
            <td class="border px-2 py-1 text-right">${formatoMoneda(item.total)}</td>
        </tr>
    `).join('');

    const wrapper = document.getElementById('previewContentFacturas');

    if(!wrapper) return;

    wrapper.innerHTML = `
        <div class="mb-6 flex justify-between items-start">
            <div>
                <img src="/view/home/logo1023.png" alt="Logo" style="max-width:220px;">
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    RFC: CEFL950210513<br>
                    Teléfono: 5540662626<br>
                    Dirección: Jose Ceballos 60<br>
                    Correo: transportesymudanzaspantera@gmail.com<br>
                </p>
            </div>
            <div class="text-right text-xs text-slate-500 leading-relaxed">
                <p><b>Folio:</b> ${escapeHTML(datos.folio)}</p>
                <p><b>Fecha:</b> ${escapeHTML(datos.fechaCreacionFull)}</p>
                <p><b>Estado:</b> CDMX</p>
            </div>
        </div>

        <div class="bg-slate-50 rounded-xl p-3 mb-4 text-sm">
            <b>Cliente:</b> ${escapeHTML(datos.items[0]?.cliente || '')}<br>
            <b>Fecha de servicio:</b> ${escapeHTML(datos.fechaServicioStr)} &nbsp;•&nbsp;
            <b>Hora:</b> ${escapeHTML(datos.horaServicioStr)}
        </div>

        <table class="w-full border-collapse text-sm mb-4">
            <thead>
                <tr class="bg-slate-900 text-white">
                    <th class="border px-2 py-1">Id</th>
                    <th class="border px-2 py-1">Producto</th>
                    <th class="border px-2 py-1">Cantidad</th>
                    <th class="border px-2 py-1">Total</th>
                </tr>
            </thead>
            <tbody>
                ${filasProductos}
            </tbody>
        </table>

        <div class="text-sm space-y-1 text-right">
            <p>Subtotal: <b>${formatoMoneda(datos.sumaTotales)}</b></p>
            <p>Anticipo: <b>- ${formatoMoneda(datos.sumaAnticipos)}</b></p>
            <p class="text-lg mt-2">Total neto: <b>${formatoMoneda(datos.totalGeneral)}</b></p>
        </div>

        <p class="text-xs text-slate-400 mt-6 text-center">
            El PDF se está compilando para compartir. Puedes usar el botón inferior.
        </p>
    `;

}

async function generarPDFFacturas(){

    if(carritoPDFFacturas.length === 0){
        avisar('Aviso', 'No hay facturas seleccionadas para generar el PDF.', 'info');
        return;
    }

    try{

        ensurePreviewModalExistsFacturas();

        document.getElementById('previewContentFacturas').innerHTML = `
            <div class="text-center py-12 text-slate-500 font-bold">
                <p class="animate-pulse tracking-wide text-blue-800 text-base">Estructurando documento de facturación...</p>
            </div>`;

        const btnCompartir = document.getElementById('btnCompartirFacturas');

        if(btnCompartir){
            btnCompartir.disabled = true;
            btnCompartir.classList.add('opacity-50', 'cursor-not-allowed');
            btnCompartir.innerHTML = `<span class="material-symbols-outlined text-xl animate-spin">sync</span>Compilando PDF...`;
        }

        document.getElementById('modalDocumentoFacturas').classList.remove('hidden');

        const sumaTotales = carritoPDFFacturas.reduce((acc, item) => acc + Number(item.total), 0);
        const sumaAnticipos = sumarAnticiposUnicos(carritoPDFFacturas);
        const totalGeneral = sumaTotales - sumaAnticipos;

        const primerServicioId = carritoPDFFacturas[0].servicio_id;
        const elFechaServicio = document.querySelector(`.fecha-servicio-editable[data-servicio-id="${primerServicioId}"]`);
        const elHoraServicio = document.querySelector(`.hora-servicio-editable[data-servicio-id="${primerServicioId}"]`);

        const fechaServicioStr = elFechaServicio ? elFechaServicio.textContent.trim() : '';
        const horaServicioStr = elHoraServicio ? elHoraServicio.textContent.trim() : '';

        // Fecha de creación del documento (momento actual)
        const ahora = new Date();
        const fechaCreacionStr = ahora.toLocaleDateString('es-MX', { timeZone: 'America/Mexico_City', day: '2-digit', month: '2-digit', year: 'numeric' });
        const horaCreacionStr = ahora.toLocaleTimeString('es-MX', { timeZone: 'America/Mexico_City', hour: '2-digit', minute: '2-digit', hour12: true }).toUpperCase();
        const fechaCreacionFull = `${fechaCreacionStr} ${horaCreacionStr}`;

        const foliosUnicos = [...new Set(carritoPDFFacturas.map(item => item.servicio_id))];
        const folio = foliosUnicos.length === 1 ? foliosUnicos[0] : foliosUnicos.join(', ');

        window.currentDocDataFacturas = { folio, fechaCreacionFull, fechaServicioStr, horaServicioStr, items: carritoPDFFacturas, sumaTotales, sumaAnticipos, totalGeneral };

        // Pinta la vista previa en HTML de inmediato, sin esperar a jsPDF
        renderPreviewHTMLFacturas();

        compilarPdfFacturasEnSegundoPlano(folio);

    }catch(error){

        console.error("Error crítico en generarPDFFacturas:", error);

        const wrapper = document.getElementById('previewContentFacturas');

        if(wrapper){
            wrapper.innerHTML = `
                <div class="text-center py-12 text-red-500 font-bold bg-red-50 rounded-2xl p-4">
                    <p>Error en la compilación del modal: ${error.message}</p>
                </div>`;
        }

    }

}


// ==========================================
// COMPILADOR EN SEGUNDO PLANO (jsPDF + autoTable)
// ==========================================
function compilarPdfFacturasEnSegundoPlano(folio){

    window.readyPdfFileFacturas = null;
    window.readyPdfBlobFacturas = null;

    const habilitarReintento = () => {
        const btnCompartir = document.getElementById('btnCompartirFacturas');
        if (btnCompartir) {
            btnCompartir.disabled = false;
            btnCompartir.classList.remove('opacity-50', 'cursor-not-allowed');
            btnCompartir.innerHTML = `<span class="material-symbols-outlined text-xl">error</span>Reintentar PDF`;
            btnCompartir.onclick = () => {
                btnCompartir.onclick = () => ejecutarAccionFinalFacturas();
                compilarPdfFacturasEnSegundoPlano(folio);
            };
        }
    };

    (async () => {

        try {

            const { jsPDF } = window.jspdf || {};

            if (!jsPDF) throw new Error("jsPDF no está cargado en la página");

            const doc = new jsPDF('p', 'pt', 'a4');

            const logo = new Image();
            logo.src = "/view/home/logo1023.png";

            logo.onload = async function() {
                const logoWidth = 300;
                const logoHeight = 100;
                doc.addImage(logo, 'PNG', 10, 30, logoWidth, logoHeight);

                // Aquí usamos la fecha de creación del documento
                const fechaActual = window.currentDocDataFacturas.fechaCreacionFull;
                
                const obtenerAsesor = () => {
                    if (carritoPDFFacturas.length > 0 && carritoPDFFacturas[0].asesor) {
                        return carritoPDFFacturas[0].asesor;
                    }
                    if (typeof nombreUsuarioLogueado !== 'undefined' && nombreUsuarioLogueado) {
                        return nombreUsuarioLogueado;
                    }
                    return 'Asesor';
                };

                const asesor = obtenerAsesor();
                const estado = "CDMX";
                let startY = 30 + logoHeight + 10;
                doc.setFontSize(10);
                doc.setFont("helvetica", "normal");

                const infoIzquierda = [
                    "RFC: CEFL950210513",
                    "Teléfono: 5540662626",
                    "Dirección: Jose Ceballos 60",
                    "Correo: transportesymudanzaspantera@gmail.com",
                ];

                const infoDerecha = [
                    `Asesor: ${asesor}`,
                    `Fecha: ${fechaActual}`,
                    `Estado: ${estado}`,
                    `Folio: ${folio}`
                ];

                const lineHeight = 12;
                infoIzquierda.forEach((line, i) => doc.text(line, 40, startY + 20 + i * lineHeight));
                infoDerecha.forEach((line, i) => doc.text(line, doc.internal.pageSize.getWidth() - 60, startY + 20 + i * lineHeight, { align: 'right' }));

                // Tabla de servicio (con su fecha y hora de servicio independiente)
                if(carritoPDFFacturas.length > 0){
                    const primerItem = carritoPDFFacturas[0];
                    const headersServicio = ["Cliente", "Fecha de Servicio", "Hora de Servicio"];
                    const rowServicio = [[primerItem.cliente, window.currentDocDataFacturas.fechaServicioStr, window.currentDocDataFacturas.horaServicioStr]];

                    doc.autoTable({
                        head: [headersServicio],
                        body: rowServicio,
                        startY: startY + 20 + infoIzquierda.length * lineHeight + 10,
                        styles: { fontSize: 10, cellPadding: 4 },
                        headStyles: { fillColor: [0,0,0], textColor: 255, fontStyle: 'bold', halign:'center' },
                        columnStyles: {
                            0: { cellWidth:200, halign:'left' },
                            1: { cellWidth:150, halign:'center' },
                            2: { cellWidth:150, halign:'center' }
                        }
                    });
                }

                // Tabla de productos (sin columna de Anticipo; ese dato va en el área de totales)
                const headers = ['Id', 'Producto', 'Cantidad', 'Total'];
                const rows = [];
                let sumaTotalesPDF = 0;
                let sumaAnticiposPDF = 0;
                const anticiposProcesados = new Set();

                carritoPDFFacturas.forEach(item => {
                    const subtotalItem = parseFloat(item.total || 0);
                    const anticipoItem = parseFloat(item.anticipo || 0);

                    rows.push([item.id, item.producto, item.cantidad, formatoMoneda(subtotalItem)]);
                    
                    sumaTotalesPDF += subtotalItem;

                    if(!anticiposProcesados.has(item.servicio_id)){
                        anticiposProcesados.add(item.servicio_id);
                        sumaAnticiposPDF += anticipoItem;
                    }
                });

                const totalGeneralNeto = sumaTotalesPDF - sumaAnticiposPDF;

                while(rows.length < 15) rows.push(['','','','']);

                doc.autoTable({
                    head: [headers],
                    body: rows,
                    startY: doc.lastAutoTable.finalY + 15,
                    styles: { fontSize: 10, cellPadding: 4 },
                    headStyles: { fillColor: [0,0,0], textColor: 255, fontStyle: 'bold', halign:'center' },
                    alternateRowStyles: { fillColor: [245,245,245] },
                    columnStyles: {
                        0: { halign:'center', cellWidth:30 },
                        1: { halign:'left', cellWidth:320 },
                        2: { halign:'center', cellWidth:70 },
                        3: { halign:'right', cellWidth:80 }
                    }
                });

                // Área de totales: Subtotal, Anticipo y Total Neto, separada de la tabla de productos
                doc.autoTable({
                    body: [
                        ['Subtotal', formatoMoneda(sumaTotalesPDF)],
                        ['Anticipo', `- ${formatoMoneda(sumaAnticiposPDF)}`],
                        ['TOTAL NETO', formatoMoneda(totalGeneralNeto)]
                    ],
                    startY: doc.lastAutoTable.finalY + 10,
                    theme: 'plain',
                    styles: { fontSize: 10, cellPadding: { top:5, bottom:5, left:6, right:6 }, valign:'middle' },
                    columnStyles: {
                        0: { halign:'right', cellWidth:410, textColor: [100,100,100], fontStyle:'normal' },
                        1: { halign:'right', cellWidth:90, fontStyle:'bold', textColor: [30,30,30] }
                    },
                    didParseCell: function(data){

                        // Fila "Anticipo": mismo tono gris que su etiqueta para que se lea como resta, no como error
                        if(data.row.index === 1 && data.column.index === 1){
                            data.cell.styles.textColor = [100,100,100];
                        }

                        // Fila "TOTAL NETO": resaltada en negro, con más aire respecto a las filas anteriores
                        if(data.row.index === 2){
                            data.cell.styles.fontSize = 13;
                            data.cell.styles.fillColor = [0,0,0];
                            data.cell.styles.textColor = 255;
                            data.cell.styles.fontStyle = 'bold';
                            data.cell.styles.cellPadding = { top:8, bottom:8, left:6, right:6 };
                        }

                    }
                });

                // Segunda hoja: Términos y condiciones
                doc.addPage();
                const pageWidth = doc.internal.pageSize.getWidth();
                const logoWidth2 = 200;
                const logoHeight2 = 70;
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
Si la Empresa cancela el service por causas atribuibles a ella, el anticipo será reembolsado en su totalidad.
El Cliente acuerda pagar a La Empresa la cantidad acordada por los servicios de mudanza, El pago se liquida antes de la descarga de los bienes.

Responsabilidad y seguro: La Empresa se compromete a tomar las precauciones necesarias para garantizar la seguridad de los bienes del Cliente durante la mudanza. Sin embargo, el Cliente reconoce que es recomendable contratar un seguro adicional para cubrir cualquier eventualidad durante la mudanza.

Cancelación: Cualquiera de las partes podrá cancelar mediante notificación por escrito a la otra parte con al menos 8 horas de anticipación.

El Cliente, declara haber leído, entendido y aceptado todos los términos y condiciones establecidos.`;

                doc.text(terminos, pageWidth / 2, yLogo2 + logoHeight2 + 50, { align: "center", maxWidth: 500 });

                // ===== GENERAR BLOB Y COMPARTIR =====
                const blob = doc.output("blob");
                window.readyPdfBlobFacturas = blob;
                window.readyPdfFileFacturas = new File([blob], `Notafactura-${folio}.pdf`, { type: 'application/pdf' });

                const btnCompartir = document.getElementById('btnCompartirFacturas');

                if (btnCompartir) {
                    btnCompartir.disabled = false;
                    btnCompartir.classList.remove('opacity-50', 'cursor-not-allowed');
                    btnCompartir.innerHTML = `<span class="material-symbols-outlined text-xl">share</span>Compartir Facturación (PDF)`;
                    btnCompartir.onclick = () => ejecutarAccionFinalFacturas();
                }

            };

            logo.onerror = () => {
                throw new Error("No se pudo cargar el logo");
            };

        } catch (err) {

            console.error("Error al compilar PDF de facturación:", err);

            habilitarReintento();

        }

    })();

}


// ==========================================
// DISPARADOR DE COMPARTICIÓN
// ==========================================
async function ejecutarAccionFinalFacturas(){

    if (!window.readyPdfFileFacturas) {
        avisar('Aviso', 'El PDF aún no está listo.', 'info');
        return;
    }

    if (window.Android?.sharePdf) {

        const reader = new FileReader();

        reader.onload = () => {
            window.Android.sharePdf(
                reader.result.split(",")[1],
                window.readyPdfFileFacturas.name
            );
        };

        reader.readAsDataURL(window.readyPdfBlobFacturas);
        return;

    }

    const puedeCompartirArchivo = navigator.share &&
        (typeof navigator.canShare !== 'function' || navigator.canShare({ files: [window.readyPdfFileFacturas] }));

    if (puedeCompartirArchivo) {

        try {

            await navigator.share({
                files: [window.readyPdfFileFacturas],
                title: "Notafactura",
                text: "Adjunto el comprobante en PDF."
            });

            cerrarPreviewFacturas();
            return;

        } catch (e) {

            console.log(e);

            if (e && e.name === 'AbortError') {
                return;
            }

        }

    }

    const url = URL.createObjectURL(window.readyPdfBlobFacturas);

    const a = document.createElement("a");
    a.href = url;
    a.download = window.readyPdfFileFacturas.name;

    document.body.appendChild(a);
    a.click();
    a.remove();

    URL.revokeObjectURL(url);

    cerrarPreviewFacturas();

}


// ==========================================
// ACTUALIZACIÓN AUTOMÁTICA (polling)
// ==========================================
// Igual patrón que en Cotizaciones: no hay tiempo real conectado al
// backend, así que se revisa periódicamente si hay cambios y solo se
// vuelve a dibujar si de verdad cambió algo.
let _intervaloActualizacionFacturas = null;

function iniciarActualizacionAutomaticaFacturas(intervaloMs = 15000){
    if(_intervaloActualizacionFacturas) clearInterval(_intervaloActualizacionFacturas);
    _intervaloActualizacionFacturas = setInterval(refrescarFacturacionesSiCorresponde, intervaloMs);
}

// No refrescar si el usuario tiene el foco puesto en un campo editable
// (fecha/hora con flatpickr abierto, o anticipo/IVA con contenteditable),
// para no interrumpirlo a medio editar. Esto es adicional al chequeo de
// modales de Cotizaciones, porque aquí la edición ocurre inline en la tarjeta.
function hayFocoEnCampoEditableFacturas(){
    const el = document.activeElement;
    if(!el) return false;
    if(el.isContentEditable) return true;
    return Boolean(el.matches?.('.fecha-servicio-editable input, .hora-servicio-editable input'));
}

async function refrescarFacturacionesSiCorresponde(){

    // No interrumpir si el usuario tiene el panel de PDF o el preview
    // de documento abiertos, ni si está editando un campo en ese momento.
    const panelPDF = document.getElementById('panelPDFFacturas');
    const modalDoc = document.getElementById('modalDocumentoFacturas');

    const panelAbierto = Boolean(panelPDF && panelPDF.style.display === 'flex');
    const modalAbierto = Boolean(modalDoc && !modalDoc.classList.contains('hidden'));

    if(panelAbierto || modalAbierto || hayFocoEnCampoEditableFacturas()) return;

    try {

        const response = await fetch('/view/home/getfacturaciones.php?_=' + new Date().getTime());

        if(!response.ok) return;

        const json = await response.json();

        if(!json.success) return;

        const datosNuevos = json.data || [];

        const huboCambios = JSON.stringify(datosNuevos) !== JSON.stringify(datosFacturaciones);

        if(huboCambios){

            datosFacturaciones = datosNuevos;

            agruparPorServicio();

            renderizarFacturaciones(facturacionesAgrupadas);

            if(typeof mostrarAlerta === 'function'){
                mostrarAlerta('Se encontraron nuevas facturaciones', 'info');
            }

        }

    } catch (err) {

        console.error('Error al refrescar facturaciones automáticamente:', err);

    }

}


// ==========================================
// MODAL DE CORREO ELECTRÓNICO (preparado, aún NO conectado a ningún botón)
// ==========================================
// Mismo patrón visual que el modal de teléfono de Cotizaciones, pero pide
// un correo electrónico en vez de un número. Las funciones ya están listas
// para usarse (pedirEmailFactura, mostrarFeedbackEmailFactura,
// verificarEmailCliente); lo único que falta es decidir a qué acción/botón
// se engancha el interceptor. Por eso NO hay ningún listener de clic que
// dispare este flujo todavía.

function ensurarModalesEmailFactura(){

    if(document.getElementById('modalEmailFactura')) return;

    const html = `
    <div id="modalEmailFactura" class="fixed inset-0 z-[10050] flex items-center justify-center p-4 hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"></div>
        <div class="bg-white/70 backdrop-blur-xl border border-white/50 shadow-2xl w-full max-w-sm relative z-10 p-8 rounded-3xl">
            <div class="w-16 h-16 bg-white/50 rounded-2xl flex items-center justify-center mx-auto mb-6 border border-white/20">
                <svg class="w-8 h-8 text-blue-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>

            <div class="text-center mb-6">
                <h2 class="text-2xl font-extrabold text-slate-800">¡Información Requerida!</h2>
                <p id="modalEmailFacturaTexto" class="text-slate-600 mt-2"></p>
            </div>

            <form id="formEmailFactura" class="space-y-6">
                <input
                    type="email"
                    id="inputEmailFactura"
                    placeholder="Ej. cliente@correo.com"
                    required
                    class="w-full bg-white/50 border border-slate-200/50 rounded-xl px-4 py-3 text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-800 outline-none transition"
                >
                <p id="inputEmailFacturaError" class="text-red-600 text-sm font-semibold hidden -mt-3">Ingresa un correo electrónico válido.</p>

                <div class="flex gap-3">
                    <button type="button" id="btnCancelarEmailFactura" class="flex-1 bg-black/5 hover:bg-black/10 text-slate-600 font-bold py-4 rounded-2xl transition">
                        Cancelar
                    </button>
                    <button type="submit" id="btnGuardarEmailFactura" class="flex-1 bg-blue-800 hover:bg-blue-900 text-white font-bold py-4 rounded-2xl transition shadow-lg shadow-blue-800/20">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalFeedbackEmailFactura" class="fixed inset-0 z-[10060] flex items-center justify-center p-4 hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"></div>
        <div class="bg-white/70 backdrop-blur-xl border border-white/50 shadow-2xl w-full max-w-sm relative z-10 p-8 rounded-3xl text-center">
            <div id="feedbackEmailFacturaIconWrap" class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6"></div>
            <h2 id="feedbackEmailFacturaTitulo" class="text-2xl font-bold text-slate-800 mb-2"></h2>
            <p id="feedbackEmailFacturaMensaje" class="text-slate-600 mb-8"></p>
            <button id="btnCerrarFeedbackEmailFactura" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-4 rounded-2xl transition shadow-lg">
                Entendido
            </button>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', html);

}

// Muestra el modal para capturar el correo. Devuelve una Promise que
// resuelve con el correo ingresado, o null si el usuario cancela.
function pedirEmailFactura(cliente){

    ensurarModalesEmailFactura();

    return new Promise((resolve) => {

        const modal = document.getElementById('modalEmailFactura');
        const texto = document.getElementById('modalEmailFacturaTexto');
        const form = document.getElementById('formEmailFactura');
        const input = document.getElementById('inputEmailFactura');
        const errorMsg = document.getElementById('inputEmailFacturaError');
        const btnCancelar = document.getElementById('btnCancelarEmailFactura');

        texto.textContent = `El cliente "${cliente}" no tiene un correo electrónico registrado. Por favor, ingrésalo para continuar:`;
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
            const emailValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor);
            if(!valor || !emailValido){
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
function mostrarFeedbackEmailFactura(esExito, titulo, mensaje){

    ensurarModalesEmailFactura();

    return new Promise((resolve) => {

        const modal = document.getElementById('modalFeedbackEmailFactura');
        const iconWrap = document.getElementById('feedbackEmailFacturaIconWrap');
        const tituloEl = document.getElementById('feedbackEmailFacturaTitulo');
        const mensajeEl = document.getElementById('feedbackEmailFacturaMensaje');
        const btnCerrar = document.getElementById('btnCerrarFeedbackEmailFactura');

        if(esExito){
            iconWrap.className = "w-20 h-20 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-6";
            iconWrap.innerHTML = `<svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>`;
        }else{
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

// Verifica/guarda el correo del cliente contra el backend. Calcado del
// patrón de verificar_telefono.php de Cotizaciones — GET para chequear,
// POST (con { cliente, email }) para guardar uno nuevo.
// Se asume que el backend responde { success, tiene_email } en el GET,
// igual que verificar_telefono.php responde { success, tiene_telefono }.
async function verificarEmailCliente(cliente, emailNuevo = null){

    if(emailNuevo){

        const res = await fetch('/view/home/verificar_email.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cliente, email: emailNuevo })
        });

        return res.json();

    }

    const res = await fetch(`/view/home/verificar_email.php?cliente=${encodeURIComponent(cliente)}`);

    return res.json();

}


// ==========================================
// INTERCEPTOR: verificar correo antes de "Agregar todo a PDF"
// ==========================================
// Mismo patrón que el interceptor de teléfono de Cotizaciones: se escucha
// en captura sobre el documento, se identifica el botón por su clase, y si
// el correo del cliente aún no está validado en este clic se detiene la
// propagación, se resuelve el correo (verificando o pidiéndolo), y solo
// entonces se vuelve a disparar el clic original sobre el mismo botón.
(function(){

    // Obtiene el nombre del cliente del servicio correspondiente al botón
    // "Agregar todo a PDF" que se clickeó, usando su data-servicio-id.
    function obtenerClienteFacturaActual(boton){

        const servicioId = boton?.dataset?.servicioId ? parseInt(boton.dataset.servicioId, 10) : null;

        if(servicioId && Array.isArray(facturacionesAgrupadas)){

            const grupo = facturacionesAgrupadas.find(f => f.servicio_id === servicioId);

            if(grupo && grupo.cliente && grupo.cliente.trim()) return grupo.cliente.trim();

        }

        return null;

    }

    async function interceptarAgregarPDFFactura(evento){

        const boton = evento.target.closest('.btnAgregarPDFServicio');

        if(!boton) return; // el clic no fue sobre ese botón

        if(boton.dataset.correoValidado === "true"){
            return; // ya se validó en este mismo ciclo, dejar pasar
        }

        evento.stopImmediatePropagation();
        evento.preventDefault();

        const cliente = obtenerClienteFacturaActual(boton);

        if(!cliente){
            await mostrarFeedbackEmailFactura(false, 'Error', 'No se encontró el cliente de este servicio.');
            return;
        }

        try{

            const resultado = await verificarEmailCliente(cliente);

            if(!resultado.success){
                await mostrarFeedbackEmailFactura(false, 'Error del Servidor', resultado.error);
                return;
            }

            if(resultado.tiene_email){
                ejecutarClickOriginalPDFFactura(boton);
                return;
            }

            // Pide el correo con el modal glassmorphism
            const correo = await pedirEmailFactura(cliente);

            if(!correo) return; // el usuario canceló

            const guardarResultado = await verificarEmailCliente(cliente, correo);

            if(guardarResultado.success){
                await mostrarFeedbackEmailFactura(true, '¡Registrado!', 'El correo se guardó en la BD con éxito.');
                ejecutarClickOriginalPDFFactura(boton);
            }else{
                await mostrarFeedbackEmailFactura(false, 'Error', 'No se pudo guardar el correo: ' + guardarResultado.error);
            }

        }catch(error){

            console.error(error);
            await mostrarFeedbackEmailFactura(false, 'Error', 'Hubo un problema de conexión con el servidor.');

        }

    }

    // Simula de nuevo el clic una vez aprobada la validación
    function ejecutarClickOriginalPDFFactura(boton){

        boton.dataset.correoValidado = "true";
        boton.click();

        setTimeout(() => {
            boton.removeAttribute('data-correo-validado');
        }, 500);

    }

    // Enganchar la intercepción SOLO al botón "Agregar todo a PDF", por
    // delegación (el botón se regenera en cada renderizarFacturaciones()).
    function inicializarInterceptorEmailFactura(){
        document.addEventListener('click', interceptarAgregarPDFFactura, true);
    }

    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', inicializarInterceptorEmailFactura);
    }else{
        inicializarInterceptorEmailFactura();
    }

})();


// ==========================================
// INICIO
// ==========================================

document.addEventListener(
"DOMContentLoaded",
()=>{


inicializarFacturaciones();

iniciarActualizacionAutomaticaFacturas();


const buscador =
document.getElementById(
'BuscadorFacturaciones'
);


if(buscador){

buscador.addEventListener(
'input',
filtrarFacturaciones
);

}


});
