<?php
ob_start();
require_once(__DIR__ . "/../head/header.php");

// Control de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificación de sesión activa (usuario logueado)
if (empty($_SESSION['usuario'])) {
    header("Location: /index.php");
    exit;
}

// Control de inactividad (15 minutos)
const TIEMPO_INACTIVIDAD = 900;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > TIEMPO_INACTIVIDAD)) {
    $_SESSION = [];
    session_destroy();
    header("Location: /index.php?timeout=1");
    exit;
}

// Actualiza última actividad
$_SESSION['last_activity'] = time();

// Datos del usuario para el panel
$nombre   = $_SESSION['usuario']['nombre_usuario'] ?? 'Asesor';
$correo   = $_SESSION['usuario']['correo'] ?? '';
$disabled = ($_SESSION['rol'] ?? '') !== 'admin' ? 'disabled' : '';

ob_end_flush();
?>
<?php include __DIR__ . '/navbar.php'; ?>
<header>
<style>body {margin: 20px 20px 50px 20px;}</style>
		
	

  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <div id="alertaExito" class="alert alert-success" role="alert" style=display:none;>
  A simple success alert—check it out!
</div>

         <div id="popupBackground"></div>    


    </header><br>


<body>
<main>
                
    <section id="control" class="page w-full px-1 pt-5 pb-5" >
                
   <li class="nav-item" id="navExportButtons" style="display: flex;gap: 5px;position: absolute;top: 26px;
    right: 100px;">
    
</li> 
<div style="display:flex; flex-direction:column; gap:10px;">

    <div class="input-group hidden" id="input1-group" style="display:flex; align-items:center; gap:8px; background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:8px 12px;">
        <span style="width:20px; height:20px; background-color:#e2e8f0; color:#475569; border-radius:9999px; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; flex-shrink:0;">•</span>
        <input class="address-input" type="text" value="19.4228676, -99.1451204" id="input1" placeholder="Dirección inicial" style="flex:1; background:transparent; border:0; outline:none; font-size:14px; color:#334155;">
        <button class="clear-btn" type="button" onclick="clearInput('input1')" style="background:none; border:0; color:#94a3b8; padding:4px; cursor:pointer; flex-shrink:0;">
            <i class="fa fa-times" style="font-size:12px;" aria-hidden="true"></i>
        </button>
    </div>

    <div class="input-group" style="display:flex; align-items:center; gap:8px; background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:8px 12px;">
        <span style="width:20px; height:20px; background-color:#eff6ff; color:#1e40af; border-radius:9999px; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; flex-shrink:0;">O</span>
        <input class="address-input" type="text" id="input2" placeholder="Dirección origen" style="flex:1; background:transparent; border:0; outline:none; font-size:14px; color:#334155;">
    </div>

    <div class="input-group" style="display:flex; align-items:center; gap:8px; background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:8px 12px;">
        <span style="width:20px; height:20px; background-color:#ecfdf5; color:#059669; border-radius:9999px; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; flex-shrink:0;">D</span>
        <input class="address-input" type="text" id="input3" placeholder="Dirección destino" style="flex:1; background:transparent; border:0; outline:none; font-size:14px; color:#334155;">
    </div>

    <div id="extra-addresses" style="display:flex; flex-direction:column; gap:10px;"></div>

    <div class="input-group hidden" id="input4-group" style="display:flex; align-items:center; gap:8px; background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:8px 12px;">
        <span style="width:20px; height:20px; background-color:#e2e8f0; color:#475569; border-radius:9999px; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; flex-shrink:0;">•</span>
        <input class="address-input end" type="text" value="19.4228676, -99.1451204" id="input4" placeholder="Dirección final" style="flex:1; background:transparent; border:0; outline:none; font-size:14px; color:#334155;">
        <button class="clear-btn" type="button" onclick="clearInput('input4')" style="background:none; border:0; color:#94a3b8; padding:4px; cursor:pointer; flex-shrink:0;">
            <i class="fa fa-times" style="font-size:12px;" aria-hidden="true"></i>
        </button>
    </div>

    <div style="display:flex; gap:8px; padding-top:4px;">
        <button class="edit-button" onclick="toggleInputs()" title="Editar direcciones fijas" style="flex:none; width:44px; height:44px; background-color:#f1f5f9; color:#475569; border:0; border-radius:12px; display:flex; align-items:center; justify-content:center; cursor:pointer;">
            <i class="fa-solid fa-pen" style="font-size:14px;"></i>
        </button>
        <button class="add-btn" onclick="addAddress()" title="Agregar parada" style="flex:none; width:44px; height:44px; background-color:#eff6ff; color:#1e40af; border:0; border-radius:12px; display:flex; align-items:center; justify-content:center; cursor:pointer;">
            <i class="fa-solid fa-plus" style="font-size:14px;"></i>
        </button>
        <button id="addAddressBtn" onclick="calculateRoute()" style="flex:1; height:44px; background-color:#1e40af; color:#ffffff; border:0; border-radius:12px; font-weight:600; box-shadow:0 1px 3px rgba(0,0,0,0.1); display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer;">
            <i class="fa-solid fa-location-arrow" style="font-size:14px;"></i>
            <span>Calcular Ruta</span>
        </button>
    </div>

</div><br>
		
<div class="address-input-container">
        
        <div class="map-container">
            
            
            <div id="map"></div>
        </div>
        <div class="controls" >
            
            <div id="route-info">
                <i class= ></i><p id="distance"></p><br>
                <i class= ></i><p id="duration"></p>
            </div>
        </div>


        

<!-- Botones principales -->
<div class="main-buttons" id="main-buttons" style="display:none;"></div>


<!-- Camionetas -->
<div id="tarjetas" class="camioneta-grid" style="display:none;">
  <label class="camioneta-card" onclick="selectCamioneta(this)" style="grid-column: span 1;">
    <input type="checkbox" name="camioneta" value="700" style="display:none" checked>
    <h4>Camioneta 700kg</h4>
    <div class="precio" id="block1-results">Costo: $0.00 MXN</div>
  </label>

  <label class="camioneta-card" onclick="selectCamioneta(this)" style="grid-column: span 1;">
    <input type="checkbox" name="camioneta" value="1500" style="display:none">
    <h4>Camioneta 1.5t</h4>
    <div class="precio" id="block2-results">Costo: $0.00 MXN</div>
  </label>

  <label class="camioneta-card" onclick="selectCamioneta(this)" style="grid-column: span 2;">
    <input type="checkbox" name="camioneta" value="3500" style="display:none">
    <h4>Camioneta 3500kg</h4>
    <div class="precio" id="block3-results">Costo: $0.00 MXN</div>
  </label>
</div>
        
        
                <div class="results" id="block2-results" ></div>
            </div>
            

<!-- ==== Bloque de Cargadores ==== -->
<div class="block cargadores" id="cargadores" style="margin-top:0;">
  <h3>Cargadores</h3>
  <div class="switch-container">
    <span>¿Necesitas cargadores?</span>
    <div class="switch">
      <input type="checkbox" id="cargadoresSwitch" onchange="toggleCargadores()">
      <label for="cargadoresSwitch"></label>
    </div>
  </div>

  <div id="cargadoresOptions" style="display: none;">
    <div class="input-container">
  <label>Número de Cargadores</label>
  <div class="contador-container">
    <button class="boton-cambio"
            ontouchstart="startChanging(-1, this)"
            ontouchend="stopChanging(this)"
            ontouchcancel="stopChanging(this)">−</button>

    <input type="number" id="numCargadores" class="valor-numerico" value="0" readonly>

    <button class="boton-cambio"
            ontouchstart="startChanging(1, this)"
            ontouchend="stopChanging(this)"
            ontouchcancel="stopChanging(this)">+</button>
  </div>
</div>

<!-- Pisos a Subir -->
<div class="input-container">
  <label>Pisos a Subir</label>
  <div class="contador-container">
    <button class="boton-cambio"
            ontouchstart="startChanging(-1, this)"
            ontouchend="stopChanging(this)"
            ontouchcancel="stopChanging(this)">−</button>

    <input type="number" id="pisosSubir" class="valor-numerico" value="0" readonly onchange="updateCargadoresCost()">

    <button class="boton-cambio"
            ontouchstart="startChanging(1, this)"
            ontouchend="stopChanging(this)"
            ontouchcancel="stopChanging(this)">+</button>
  </div>
</div>

<!-- Pisos a Bajar -->
<div class="input-container">
  <label>Pisos a Bajar</label>
  <div class="contador-container">
    <button class="boton-cambio"
            ontouchstart="startChanging(-1, this)"
            ontouchend="stopChanging(this)"
            ontouchcancel="stopChanging(this)">−</button>

    <input type="number" id="pisosBajar" class="valor-numerico" value="0" readonly onchange="updateCargadoresCost()">

    <button class="boton-cambio"
            ontouchstart="startChanging(1, this)"
            ontouchend="stopChanging(this)"
            ontouchcancel="stopChanging(this)">+</button>
  </div>
    </div>  <br>  
        

    <!-- Slider de intensidad -->
    <div class="slider-container">
      <label for="intensidad" style="font-weight:bold;">Tipo de maniobra</label>
      <input type="range" id="intensidad" min="0" max="2" step="1" value="0">
      <div class="labels">
        <span>Básica</span>
        <span>Intermedia</span>
        <span>Avanzada</span>
      </div>
      <p id="resultado" style="display:none;">Intensidad seleccionada: Fácil</p>
    </div>
  </div>
          
<input 
  type="text" 
  id="totalInput" style="display:none;"
  class="form-control fw-bold text-success mt-2" 
  value="0.00" 
  readonly>
  <!-- Resultado de costo cargadores -->
  <div id="cargadoresCostResult" class="results"></div>
</div>
<!-- ==== Botón siguiente ==== -->
<div id="nextButtonContainer" style="display:none;">
  <button
    class="btn btn-success"
    type="button"
    data-bs-toggle="offcanvas"
    data-bs-target="#offcanvasBottom">

    Siguiente
  </button>
</div>

<!-- ==== Offcanvas ==== -->
<div class="offcanvas offcanvas-bottom text-light bg-slate-900/75 backdrop-blur-2xl border-t border-white/10 rounded-t-3xl"
     tabindex="-1"
     id="offcanvasBottom" 
     aria-labelledby="offcanvasBottomLabel"
     style="height:auto; max-height:85vh;">


    <div class="offcanvas-header border-b border-white/10 px-6 py-4">
        <h5 class="offcanvas-title font-bold text-slate-100 text-lg m-0" id="offcanvasBottomLabel">
            Resumen de costos
        </h5>

         <button type="button"
                class="btn-close btn-close-white opacity-60"
                data-bs-dismiss="offcanvas">
        </button>
    </div>

    <div class="offcanvas-body px-6 pt-4 pb-6" style="display:block; height:auto; overflow-y:auto;">

     <!-- Aquí updateTotal() insertará el resumen -->
        <div id="resumenCostos"></div>

        <div id="next" class="mt-4">

            <button id="btnEnviar"
                    class="w-full h-13 bg-emerald-600 hover:bg-emerald-700 border-0 rounded-2xl font-bold text-[15px] text-white shadow-lg shadow-emerald-600/30 transition-all active:scale-95"
                    type="button"
                    data-bs-dismiss="offcanvas">

                Enviar

            </button>
        </div>
    </div>
</div>

</div>

</div>

  </section>
  
<section id="cotizaciones" class="page">

  
  

<!-- Carga de Tailwind, Iconos y html2pdf -->
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="max-w-md mx-auto p-4 pb-20 bg-slate-50 min-h-screen">
    <!-- Cabecera y Buscador -->
    <header class="mb-6 text-center">
        <h1 class="text-2xl font-black text-slate-800">Cotizaciones</h1>
        <p class="text-slate-500 font-medium">Gestión de Servicios</p>
    </header>

    <div class="mb-6">
    <div class="relative flex items-center bg-slate-50 border border-slate-200 rounded-xl focus-within:bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 transition-all duration-200">
        <!-- Icono más pequeño -->
        <span class="absolute left-3 text-slate-400 flex items-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </span>
        <!-- Input más delgado -->
        <input type="text" id="BuscadorCotizaciones" onkeyup="filtrar()" placeholder="Buscar por cliente..." 
               class="w-full py-2.5 pl-9 pr-4 bg-transparent text-sm text-slate-700 placeholder-slate-400 outline-none">
    </div>
</div>



    <!-- Contenedor de las Tarjetas -->
    <div id="listaCotizaciones" class="space-y-4">
        <p class="text-center text-slate-400">Cargando cotizaciones...</p>
    </div>
</div>

<!-- Modal: Editar Inventario/Notas -->
<div id="modal-inventario" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-sm p-6 shadow-2xl">
        <h2 class="text-xl font-bold mb-1 text-slate-800">Editar Inventario</h2>
        <p id="modal-cliente-inventario" class="text-sm text-slate-500 mb-4"></p>
        <textarea id="edit-area" class="w-full h-40 p-4 border border-slate-200 rounded-2xl mb-4 text-sm focus:ring-2 focus:ring-blue-800 outline-none"></textarea>
        <div class="flex gap-3">
            <button onclick="cerrarModal('modal-inventario')" class="flex-1 py-3 bg-slate-100 rounded-xl font-bold text-slate-600">Cancelar</button>
            <button onclick="guardarInventario()" class="flex-1 py-3 bg-blue-800 hover:bg-blue-900 text-white rounded-xl font-bold transition-all active:scale-95">Guardar</button>
        </div>
    </div>
</div>

<!-- Modal: Facturar (Carrito) -->
<div id="modal-carrito" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-lg p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-800">Folio: <span id="folio-display" class="text-blue-800"></span></h2>
            <button onclick="cerrarModal('modal-carrito')" class="text-slate-400 hover:text-red-500 font-bold text-xl">&times;</button>
        </div>
        
        <div class="overflow-x-auto mb-4 border border-slate-200 rounded-xl">
            <table class="w-full text-left text-sm" id="tablaCarrito">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="p-3">Cliente</th>
                        <th class="p-3">Producto/Ruta</th>
                        <th class="p-3">Cant.</th>
                        <th class="p-3">Total</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100"></tbody>
            </table>
        </div>

        <div class="flex justify-between items-center mb-6 bg-slate-50 p-4 rounded-xl">
            <span class="font-bold text-slate-500">Total General:</span>
            <span class="font-black text-xl text-blue-800">$<span id="totalCarrito">0.00</span></span>
        </div>

         <!-- Botones de Acción Carrito Forzados contra deformación -->
        <div class="flex w-full gap-3 mb-3">
            <button onclick="agregarFilaLibre()" class="flex-1 h-14 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-[12px] sm:text-sm transition-all active:scale-95 select-none flex items-center justify-center gap-1.5 whitespace-nowrap overflow-hidden px-2">
                <span class="material-symbols-outlined text-base sm:text-lg flex-shrink-0">add</span> 
                <span class="truncate">Añadir Extra</span>
            </button>
            
            <button id="btnDescargarPDF" onclick="generarPDF()" class="flex-1 h-14 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl font-bold text-[12px] sm:text-sm transition-all active:scale-95 select-none flex items-center justify-center gap-1.5 whitespace-nowrap overflow-hidden px-2">
                <span class="material-symbols-outlined text-base sm:text-lg flex-shrink-0">picture_as_pdf</span> 
                <span class="truncate">Generar PDF</span>
            </button>
        </div>
        
        <!-- Botón Guardar Forzado (ÚNICO) -->
        <button onclick="guardarFacturacion()" class="w-full h-14 bg-blue-800 hover:bg-blue-900 text-white rounded-xl font-black text-lg mt-2 shadow-xl shadow-blue-800/20 transition-all active:scale-95 select-none flex items-center justify-center gap-2 whitespace-nowrap">
            <span class="material-symbols-outlined flex-shrink-0">save</span> 
            Guardar Carrito
        </button>
    </div>
</div>

<!-- Área oculta para generar PDF -->
<div id="areaPDFWrapper" class="hidden bg-white p-8 text-black" style="width: 210mm; min-height: 297mm;">
    <h1 class="text-2xl font-bold mb-4">Cotización</h1>
    <table class="w-full text-left border-collapse" id="pdfTabla">
        <thead class="border-b-2 border-black">
            <tr>
                <th class="py-2">Cliente</th>
                <th class="py-2">Descripción</th>
                <th class="py-2 text-center">Cant.</th>
                <th class="py-2 text-right">Total</th>
            </tr>
        </thead>
        <tbody id="pdfTablaBody"></tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right font-bold py-4">Total:</td>
                <td class="text-right font-bold py-4">$<span id="pdfTotal"></span></td>
            </tr>
        </tfoot>
    </table>
    <p id="pdfCondiciones" class="mt-8 text-sm text-gray-600"></p>
</div>

<!-- Toast Notificación -->
<div id="toastNotificacionLince" class="fixed top-4 left-4 right-4 sm:left-1/2 sm:right-auto sm:-translate-x-1/2 sm:w-full sm:max-w-sm z-50 opacity-0 -translate-y-3 pointer-events-none transition-all duration-300 ease-out cursor-pointer">
    <div class="flex items-center gap-3 bg-white border border-slate-200 shadow-2xl rounded-2xl px-4 py-3.5">
        <div id="toastIconWrap" class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 bg-green-600 transition-colors">
            <span id="toastIcono" class="material-symbols-outlined text-white text-lg">check_circle</span>
        </div>
        <p id="toastTexto" class="text-slate-800 font-semibold text-[13px] leading-snug"></p>
    </div>
</div>


</section>

<section id="facturaciones" class="page w-full px-3 pt-5 pb-5">

    <!-- Encabezado -->
    <div class="flex items-center justify-between mb-5">
        <div>
            <h3 class="text-xl font-black text-slate-800 tracking-tight">
                📄 Facturaciones
            </h3>
            <p class="text-sm text-slate-500 font-medium mt-1">
                Gestión de servicios facturados
            </p>
        </div>

        <div class="bg-blue-50 text-blue-800 rounded-xl px-3 py-2">
            <span class="material-symbols-outlined text-xl">
                receipt_long
            </span>
        </div>
    </div>


    <!-- Buscador -->
    <div class="relative mb-5">

        <span class="material-symbols-outlined absolute left-4 top-3.5 text-slate-400">
            search
        </span>

        <input 
            id="BuscadorFacturaciones"
            type="text"
            placeholder="Buscar cliente, folio..."
            class="
                w-full
                h-12
                pl-12
                pr-4
                rounded-2xl
                border
                border-slate-200
                bg-white
                text-sm
                font-medium
                text-slate-700
                outline-none
                focus:ring-2
                focus:ring-blue-800
                shadow-sm
            "
        >

    </div>



    <!-- Lista dinámica -->
    <div 
        id="listaFacturaciones"
        class="space-y-4"
    >

        <!-- Estado inicial -->
        <div class="
            bg-white
            rounded-2xl
            border
            border-slate-200
            p-8
            text-center
        ">

            <span class="
                material-symbols-outlined
                text-4xl
                text-slate-300
                mb-3
            ">
                receipt_long
            </span>

            <p class="
                text-slate-500
                font-semibold
                text-sm
            ">
                Cargando facturaciones...
            </p>

        </div>

    </div>


</section> 
<section id="notas" class="page active ">
  <form id="cotizacionForm">
  <!-- Paso único -->
  <div class="form-step active">
    <div class="form-group">
      <label for="nombre">Nombre del Cliente *</label>
      <input type="text" id="nombre" name="nombre" required>
    </div>

    <div class="form-group">
      <label for="tipo-servicio">Tipo de servicio</label>
      <select id="tipo-servicio" name="tipo-servicio" required onchange="setModeFromSelect()">
        <option value="" disabled selected>Selecciona</option>
        <option value="mudanza">Mudanza</option>
        <option value="flete">Flete</option>
      </select>
    </div>

    <div class="form-group">
      <label for="tipo-inmueble">Tipo de inmueble</label>
      <input type="text" id="tipo-inmueble" name="tipo-inmueble">
    </div>

    <fieldset class="form-group-radio">
  <legend>Destino*</legend>
  <label>
    <input type="radio" name="destino" value="Local" required onchange="activarModo(this.value)">
    Local
  </label>
  <label>
    <input type="radio" name="destino" value="Foráneo" onchange="activarModo(this.value)">
    Foráneo
  </label>
</fieldset>
          

    <div class="form-group">
      <label for="inventario">Inventario *</label>
      <textarea id="inventario" name="inventario" required></textarea>
    </div>

    <div class="form-group" style=display:none;>
      <label for="origen">Dirección de Origen *</label>
      <input type="text" id="origen" name="direccion-origen" required>
    </div>

    <div class="form-group" style=display:none;>
      <label for="direccion-destino">Dirección de Destino *</label>
      <input type="text" id="destino" name="direccion-destino" required>
    </div>
          
          
    <div class="form-group" style= display:none>
      <label for="camionetaSeleccionadaInput">Tipo de Camioneta *</label>
     <input type="text" id="camionetaSeleccionadaInput" name="tipo-camioneta">
    </div>

    <div class="form-group" style="display:none;">
      <label for="cargadoresinput">Número de Cargadores</label>
      <input type="number" id="cargadoresinput" name="cargadores" min="0" value="0">
    </div>

    <div class="form-group" style="display:none;">
      <label for="maniobrainput">Costo de Maniobra</label>
      <input type="number" id="maniobrainput" name="maniobra" step="0.01" value="0">
    </div>

<div class="form-group" style="display:none;">
  <label for="totales">Costo Total *</label>
  <input type="text" id="totales" name="totales"      
    class="form-control fw-bold text-success mt-2" 
    value="0.00" readonly required>
</div>

      

  <!-- BOTÓN SE MANTIENE COMO ESTÁ -->
  <div class="footer-nav">
    <button type="button" id="btnSiguiente">Siguiente</button>
  </div>
 <div id="alertaModo" style="
  display:none;
  position: fixed;
  bottom: 250px;
  right: 30px;
  background: #111;
  color: #fff;
  padding: 12px 20px;
  border-radius: 12px;
  box-shadow: 0 0 15px rgba(0,0,0,0.3);
  font-weight: bold;
  z-index: 9999;
  transition: opacity 0.4s ease;">
</div>       

  <div id="control"></div>
</form>          
</section>
<section id="generar" class="page">
  <div id="generarPDFSection">
  <h4>Carrito para Generar PDF</h4>
  <table class="table table-bordered" id="tablaGenerar">
    <thead class="table-light">
      <tr>
        <th>Id</th>
        <th>Productos</th>
        <th>Cantidad</th>
        <th>Sup.Total</th>
        <th>Anticipo</th>
        <th>Total</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody>
      <!-- Aquí se van agregando las filas -->
    </tbody>
  </table>
  <button class="btn btn-dark" id="btnGeneraPDF">Generar PDF</button>
</div>
          <div class="text-end mt-2">
    <strong>Total General: $<span id="totalGeneralPDF">0.00</span></strong>
</div>
</section>  
          
   
 <!-- Modal Notas -->
<div class="modal fade" id="modalNotas" tabindex="-1" aria-labelledby="modalNotasLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title" id="modalNotasLabel">Editar Notas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <textarea id="textareaNotas" class="form-control" rows="5" placeholder="Escribe tus notas aquí..."></textarea>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="guardarNotas" class="btn btn-primary">Guardar</button>
      </div>
    </div>
  </div>
</div>      





          
<?php include __DIR__ . '/logica_local.php'; ?> 
          
<!-- cotizacion_pdf.php eliminado, no existe -->        
          
<?php include __DIR__ . '/logica02.php'; ?>        

<?php include __DIR__ . '/tablas_logica.php'; ?> 	  
  
	  

<script src="/view/home/js/tables/cotizaciones.js?v=27"></script>	
<script src="/view/home/js/tables/facturaciones.js?v=25"></script>		  

<script src="/view/home/script.js?v=35"https></script>
        




<!-- Modal Bootstrap -->
<div class="modal fade" id="modalCarrito" tabindex="-1" aria-labelledby="modalCarritoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCarritoLabel">Carrito de Facturación</h5>
          <input 
  type="text" 
  id="folio-cotizacion" style="display:none;"
  class="form-control fw-bold text-success mt-2" 
  value="0.00" 
  readonly>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="tabla-container">
          <table id="tablaCarrito" class="table table-bordered">
            <thead>
              <tr data-id="123">
                <th>Cliente</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Total</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <p><b>Total General:</b> <span id="totalCarrito">0</span></p>
        <div id="mensajeCarrito"></div>
      </div>
      <div class="modal-footer">
          
     <!-- BOTÓN PDF -->

<div class="d-flex gap-3 w-100">

<button id="btnGenerarPDF"
        class="btn btn-danger"
        style="flex:1; height:30px; font-size:18px; font-weight:600; box-shadow:0 2px 0 #b02a37;">
        <i class="fa-solid fa-file-pdf" style="font-size:22px;"></i>

    <button id="btnAgregarLibre" class="btn btn-primary" style="flex:1;">
        <i class="fa-solid fa-plus"></i>
    </button>

    <button id="btnGuardarCarrito" class="btn btn-dark" style="flex:1;">
        <i class="fas fa-save"></i>
    </button>
</div>
          
<div id="areaPDFWrapper" style="display:none;padding:20px; font-family:Arial; background:white; color:black;">

    <!-- HEADER EMPRESA -->
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #1400AD; padding-bottom:10px; margin-bottom:15px;">

        <!-- Logo Simulado -->
        <div style="display:flex; align-items:center; gap:10px;">
            <img 
  src="/asset/logo1023.png"
  style="width:110px; height:110px; object-fit:contain; margin-bottom:25px;"
><br>

            <div style="margin-left:10px;">
    <strong style="font-size:18px;">Fletes y Mudanzas El Lince</strong><br>
    <small>Formato de Cotizacion</small>
</div>
        </div>

        <!-- Datos empresa -->
            
       <?php
date_default_timezone_set('America/Mexico_City');
$fecha = date("d/m/Y");
$hora  = date("h:i A");
?>

<div style="text-align:right; font-size:12px;">

    <!-- 🔥 FOLIO ARRIBA -->
    <div class="folio-top">
        <span class="folio-label">Folio:</span>
        <span id="folio-display" class="folio-value"></span>
    </div>

    <!-- Datos empresa -->
    <div class="empresa-info">
        <strong>Fecha:</strong> <?php echo $fecha; ?><br>
        <strong>Hora:</strong> <?php echo $hora; ?><br>
        RFC: RODV930126UI7<br>
        DOCTOR ANDRADE 72 CDMX, México<br>
        Tel: 55 6328 5207<br>
    </div>
          

</div>

    </div>

    <!-- TABLA CARRITO -->
    <table style="
    width:100%; 
    border-collapse: separate;  /* 🔹 importante */
    border-spacing: 0;           /* evita espacios extra */
    border: 2px solid #1400AD;   /* borde principal */
    border-radius: 10px;         /* bordes redondeados */
    overflow: hidden;            /* recorta celdas que sobresalen */
">
        <thead style="background:#1400AD; color:white;">
            <tr>
                <th style="padding:6px;">Cliente</th>
                <th style="padding:6px;">Concepto</th>
                <th style="padding:6px;">Cantidad</th>
                <th style="padding:6px;">Total</th>
            </tr>
        </thead>

        <tbody id="pdfTablaBody">
        </tbody>
    </table>
   <div style="display:flex; justify-content:space-between; align-items:center; margin-top:15px; font-size:18px;">

    <div>
        <strong>mudanzasellince.com</strong>
    </div>

    <div>
        <strong>Total: $<span id="pdfTotal">0</span></strong>
    </div>

</div>
          <div style="margin-top:25px;">

    <textarea 
    id="pdfCondicionesInput"
    style="
        display:none;  
        width:100%;
        max-height:200px;      /* límite del modal si lo muestras */
        min-height:110px;
        border:2px solid #1400AD;
        border-radius:14px;
        padding:12px;
        font-size:12px;
        resize:none;
        overflow-y:auto;
    ">Observaciones del Servicio de Mudanza
Alcance: El servicio es exclusivo para el transporte de bienes; no se transporta a personas. El operador tiene la facultad de cancelar el servicio si las vialidades no son aptas para la unidad.
Puntualidad: La hora de llegada está sujeta a factores externos (tráfico, accidentes, etc.).
Tiempos de Espera: Se otorgan 30 minutos de tolerancia para carga y descarga. Excedido este tiempo, se aplicará un cargo del 10% del costo total por cada 30 minutos adicionales. Si la espera supera las 3 horas, la penalización será del 100% del costo.
Inventario: El Cliente es responsable de proporcionar un listado detallado y preciso de los bienes a trasladar.
Pagos y Anticipos: Se requiere un anticipo del 50% para reservar el servicio. El saldo restante debe liquidarse antes de iniciar la descarga en el destino.
Cancelaciones: Deben notificarse con al menos 8 horas de anticipación. De lo contrario, el anticipo no será reembolsable. Si la Empresa cancela por causas propias, se devolverá el total del anticipo.
Responsabilidad: La Empresa tomará las precauciones necesarias, pero se recomienda al Cliente contratar un seguro adicional para cubrir cualquier eventualidad.
Consulta nuestros términos y condiciones del servicio.
</textarea>
<div id="pdfCondiciones" style="
        width:100%;
        border:2px solid #1400AD;
        border-radius:6px;
        padding:12px;
        font-size:10px;
        white-space: pre-wrap; /* 🔥 importante para mantener saltos de línea */
        display:none; /* 🔥 oculto en modal */
    ">
</div>
</div>
    
     <div class="text-end mt-3">
    

    
</div>       

</div>
            <button id="btnDescargarPDF" class="btn btn-danger w-100" style="display:none;>
    <i class="fa fa-file-pdf"></i> Descargar Cotización
</button>
</div>
            
    </div>
  </div>
</div>
<h1 id="usuario" style="position:absolute; top:10px; right:85px; cursor:pointer; transition:color 0.3s; display:flex; align-items:center; gap:5px;">
    <i class="fa fa-user-circle" aria-hidden="true"></i>
    <?= htmlspecialchars($nombre) ?>
    <i class="fa-solid fa-location-dot" id="markerIcon" style="display:none; color:red;"></i>
</h1>

<style>
    #usuario { color: black; }
    #usuario.activo { color: green; }
</style>

<script>
const usuario = document.getElementById('usuario');
const markerIcon = document.getElementById('markerIcon');

usuario.addEventListener('click', () => {
    usuario.classList.toggle('activo');
    
    // Mostrar u ocultar el globo marcador
    markerIcon.style.display = usuario.classList.contains('activo') ? 'inline-block' : 'none';

    if(usuario.classList.contains('activo')){
        console.log("¡Activado!");
    } else {
        console.log("¡Desactivado!");
    }
});
</script>
	
      <!-- Input oculto con correo del usuario -->
<input type="hidden" id="inputCorreoPanel" value="<?= $_SESSION['usuario']['correo'] ?? '' ?>">

<script>
// 🎨 INYECCIÓN RADICAL DE CSS PARA BOTONES DE ALERTA
(function inyectarEstilosSweet() {
    const estilo = document.createElement('style');
    estilo.innerHTML = `
        /* Forzar que el contenedor de los botones ocupe el ancho correcto sin encogerse */
        .swal2-actions, .swal2-container .swal2-actions {
            display: flex !important;
            flex-direction: row !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 15px !important;
            width: 100% !important;
            margin-top: 25px !important;
            box-sizing: border-box !important;
        }

        /* Rediseño forzado e independiente para evitar que se deformen o se corten */
        .btn-lince-confirm, .btn-lince-cancel {
            display: block !important;
            width: 130px !important; /* Ancho fijo para que quepa el texto completo */
            height: 45px !important;
            line-height: 45px !important; /* Centra el texto verticalmente */
            font-size: 15px !important;
            font-weight: bold !important;
            text-align: center !important;
            color: #ffffff !important;
            border-radius: 6px !important;
            border: none !important;
            cursor: pointer !important;
            text-decoration: none !important;
            padding: 0 !important;
            margin: 0 !important;
            box-shadow: none !important;
            box-sizing: border-box !important;
        }

        /* Color azul para Registrar */
        .btn-lince-confirm {
            background-color: #1400AD !important;
        }

        /* Color gris para Cancelar/Más tarde */
        .btn-lince-cancel {
            background-color: #6c757d !important;
        }

        /* Efecto feedback al presionar en pantalla táctil */
        .btn-lince-confirm:active, .btn-lince-cancel:active {
            transform: scale(0.95) !important;
            opacity: 0.9 !important;
        }
    `;
    document.head.appendChild(estilo);
})();

window.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        iniciarVerificacionLince();
    }, 1500);
});

async function iniciarVerificacionLince() {
    const inputCorreo = document.getElementById('inputCorreoPanel');
    if (!inputCorreo) return;

    const correoUsuario = inputCorreo.value.trim();
    if (!correoUsuario) return;

    try {
        const res = await fetch('/view/home/get_credential.php?correo=' + encodeURIComponent(correoUsuario));
        if (!res.ok) return;

        const data = await res.json();

        if (data && data.credentialId) {
            console.log("✅ Acceso biométrico activo en servidor para: " + correoUsuario);
            if (typeof AndroidHuella !== 'undefined' && typeof AndroidHuella.guardarTokenSesion === 'function') {
                AndroidHuella.guardarTokenSesion(data.credentialId, correoUsuario);
            }
            return;
        }

        // Alerta con clases totalmente personalizadas (Evita heredar basura del framework de la web)
        Swal.fire({
            title: 'Acceso Biométrico',
            text: `Hola ${data.nombre_usuario || 'Asesor'}, no tienes una huella vinculada en este dispositivo. ¿Deseas registrarla para iniciar sesión más rápido?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Más tarde',
            customClass: {
                actions: 'swal2-actions',
                confirmButton: 'btn-lince-confirm', // Usamos nuestra clase limpia
                cancelButton: 'btn-lince-cancel'   // Usamos nuestra clase limpia
            },
            buttonsStyling: false // Le quita el estilo por defecto de Swal para usar el nuestro
        }).then((result) => {
            if (result.isConfirmed) {
                guardarHuellaDirecto(correoUsuario);
            }
        });

    } catch (err) {
        console.error("Fallo silencioso en verificación biométrica:", err);
    }
}

// Se guarda el correo pendiente para que resultadoRegistroHuella (llamado
// por Android SOLO tras un resultado real del sensor) sepa a quien pertenece.
window._correoRegistroHuellaPendiente = null;

async function guardarHuellaDirecto(correo) {
    window._correoRegistroHuellaPendiente = correo;

    if (typeof AndroidHuella !== 'undefined' && typeof AndroidHuella.dispararLectorNativoRegistro === 'function') {
        AndroidHuella.dispararLectorNativoRegistro(correo);
        return;
    }

    if (window.PublicKeyCredential && navigator.credentials && navigator.credentials.create) {
        try {
            const challenge = new Uint8Array(32);
            window.crypto.getRandomValues(challenge);
            const userId = new TextEncoder().encode(correo);

            const credential = await navigator.credentials.create({
                publicKey: {
                    challenge: challenge,
                    rp: { name: 'Transportes y Mudanzas Pantera' },
                    user: { id: userId, name: correo, displayName: correo },
                    pubKeyCredParams: [
                        { type: 'public-key', alg: -7 },
                        { type: 'public-key', alg: -257 }
                    ],
                    authenticatorSelection: {
                        authenticatorAttachment: 'platform',
                        userVerification: 'required'
                    },
                    timeout: 60000,
                    attestation: 'none'
                }
            });

            if (credential) {
                window.resultadoRegistroHuella(true, 'OK');
            } else {
                window.resultadoRegistroHuella(false, 'CANCELADO');
            }
        } catch (err) {
            console.error('Error WebAuthn al registrar huella:', err);
            window.resultadoRegistroHuella(false, 'CANCELADO');
        }
        return;
    }

    Swal.fire({
        icon: 'error',
        title: 'No disponible',
        text: 'Este dispositivo o navegador no soporta el registro de huella.',
        confirmButtonText: 'Cerrar',
        customClass: { confirmButton: 'btn-lince-cancel' },
        buttonsStyling: false
    });
}

// Llamada por Android UNICAMENTE despues de que el sensor de huella
// confirmo (o fallo) de verdad. Antes esto no existia, por lo que el
// registro se daba por exitoso sin depender del sensor.
window.resultadoRegistroHuella = async function(exito, mensaje) {
    const correo = window._correoRegistroHuellaPendiente;
    window._correoRegistroHuellaPendiente = null;

    if (!exito || !correo) {
        if (mensaje !== 'CANCELADO') {
            Swal.fire({
                icon: 'error',
                title: 'Huella no verificada',
                text: 'No se pudo leer tu huella. Intenta de nuevo.',
                confirmButtonText: 'Cerrar',
                customClass: { confirmButton: 'btn-lince-cancel' },
                buttonsStyling: false
            });
        }
        return;
    }

    const nuevoToken = 'LINCE_BIOMETRIC_' + Math.random().toString(36).substring(2) + Date.now().toString(36);

    try {
        const res = await fetch('/view/home/registrar_huella.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=utf-8' },
            body: JSON.stringify({
                credentialId: nuevoToken,
                correo: correo,
                publicKey: 'NATIVE_ANDROID_KEY'
            })
        });

        const respuestaBD = await res.json();

        if (respuestaBD.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Vinculación Exitosa!',
                text: 'Tu huella ha quedado registrada perfectamente en el servidor.',
                confirmButtonText: 'Entendido',
                customClass: {
                    confirmButton: 'btn-lince-confirm'
                },
                buttonsStyling: false
            }).then(() => {
                if (typeof AndroidHuella !== 'undefined' && typeof AndroidHuella.guardarTokenSesion === 'function') {
                    AndroidHuella.guardarTokenSesion(nuevoToken, correo);
                }
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error de Servidor',
                text: 'No se pudo guardar la huella: ' + respuestaBD.message,
                confirmButtonText: 'Cerrar',
                customClass: {
                    confirmButton: 'btn-lince-cancel'
                },
                buttonsStyling: false
            });
        }

    } catch (e) {
        console.error("Error de red en el registro:", e);
        Swal.fire({
            icon: 'error',
            title: 'Error de Red',
            text: 'Hubo un fallo de comunicación al conectar con el servidor.',
            confirmButtonText: 'Cerrar',
            customClass: {
                confirmButton: 'btn-lince-cancel'
            },
            buttonsStyling: false
        });
    }
}
</script>




<script>

const menu = document.querySelector('.nav');
const offcanvas = document.getElementById('offcanvasBottom');

function ocultarMenu() {
    menu.classList.add('oculto');
}

function mostrarMenu() {
    menu.classList.remove('oculto');
}

let tecladoAbierto = false;
let keyboardTimer;

// ===== Offcanvas =====
offcanvas.addEventListener('show.bs.offcanvas', ocultarMenu);

offcanvas.addEventListener('hidden.bs.offcanvas', () => {
    if (!tecladoAbierto) {
        mostrarMenu();
    }
});

// ===== Teclado =====
if (window.visualViewport) {

    const alturaInicial = window.visualViewport.height;

    window.visualViewport.addEventListener('resize', () => {

        clearTimeout(keyboardTimer);

        keyboardTimer = setTimeout(() => {

            tecladoAbierto =
                window.visualViewport.height < alturaInicial - 120;

            if (tecladoAbierto) {
                ocultarMenu();
            } else if (!offcanvas.classList.contains('show')) {
                mostrarMenu();
            }

        }, 120);

    });

}
	const btnDescargarPDF = document.getElementById('btnDescargarPDF');

const observer = new MutationObserver(() => {
    const visible = getComputedStyle(btnDescargarPDF).display !== 'none';

    if (visible) {
        ocultarMenu();
    } else if (
        !tecladoAbierto &&
        !offcanvas.classList.contains('show')
    ) {
        mostrarMenu();
    }
});

observer.observe(btnDescargarPDF, {
    attributes: true,
    attributeFilter: ['style', 'class']
});
</script>


</body>      
<?php
    require_once(__DIR__ . "/../head/footer.php");
?>