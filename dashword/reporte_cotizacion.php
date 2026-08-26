<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: /index.php");
    exit;
}

if (($_SESSION['rol'] ?? 'usuario') !== 'admin') {
    header("Location: /no_autorizado.php");
    exit;
}

$inactive = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    session_destroy();
    header("Location: /index.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotizaciones | Panel Administrativo</title>

    <!-- Tailwind CSS & Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/vendors/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        .stat-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        /* SweetAlert Custom UI */
        .swal2-popup {
            border-radius: 1rem !important;
            padding: 1.5rem !important;
        }

        .swal-iso-actions {
            display: flex !important;
            gap: 0.75rem !important;
            margin-top: 1.25rem !important;
        }

        .swal-iso-btn {
            padding: 0.625rem 1.25rem !important;
            border-radius: 0.5rem !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            transition: all 0.15s ease !important;
        }

        .swal-iso-danger { background-color: #ef4444 !important; color: white !important; }
        .swal-iso-danger:hover { background-color: #dc2626 !important; }
        .swal-iso-success { background-color: #2563eb !important; color: white !important; }
        .swal-iso-success:hover { background-color: #1d4ed8 !important; }
        .swal-iso-cancel { background-color: #f1f5f9 !important; color: #475569 !important; }
        .swal-iso-cancel:hover { background-color: #e2e8f0 !important; }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">

<?php include __DIR__ . '/nav.php'; ?>

<main id="main" class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">

    <!-- Header -->
    <header class="mb-8 space-y-4">
        <!-- Barra Móvil: Solo se muestra en pantallas pequeñas (oculto en pantallas xl) -->
        <div class="flex xl:hidden items-center justify-between bg-white p-3 rounded-2xl border border-slate-200/80 shadow-sm">
            <button id="toggleSidebarBtn" type="button" class="inline-flex items-center justify-center px-3 py-1.5 text-slate-700 hover:text-blue-600 hover:bg-slate-100 rounded-xl transition-all focus:outline-none">
                <i class="bi bi-list text-2xl leading-none"></i>
                <span class="ml-2 text-sm font-semibold">Menú</span>
            </button>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider px-2">Panel Cotizaciones</span>
        </div>

        <!-- Titular Principal de la Página -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 bg-blue-600/10 text-blue-600 rounded-2xl flex items-center justify-center shrink-0">
                    <i class="bi bi-clipboard-check text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Gestión de Cotizaciones</h1>
                    <p class="text-slate-500 text-sm mt-0.5">Revisa, calcula y aprueba las solicitudes de mudanza registradas.</p>
                </div>
            </div>
            
            <div class="self-start sm:self-auto">
                <button onclick="cargarCotizaciones()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl font-medium text-sm shadow-sm transition active:scale-95">
                    <i class="bi bi-arrow-clockwise text-base"></i>
                    <span>Actualizar tabla</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Metrics Grid -->
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <div class="stat-card bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Solicitudes</span>
                <div class="w-9 h-9 bg-orange-50 text-orange-600 rounded-lg flex items-center justify-center">
                    <i class="bi bi-inbox text-lg"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-extrabold text-slate-900" id="statPendientes">0</div>
                <p class="text-xs text-slate-500 mt-1">Registradas en el sistema</p>
            </div>
        </div>

        <div class="stat-card bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Valor Estimado</span>
                <div class="w-9 h-9 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center">
                    <i class="bi bi-cash-stack text-lg"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-extrabold text-slate-900" id="statMontoTotal">$0</div>
                <p class="text-xs text-slate-500 mt-1">Suma acumulada de servicios</p>
            </div>
        </div>

        <div class="stat-card bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Ingresadas Hoy</span>
                <div class="w-9 h-9 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                    <i class="bi bi-calendar-event text-lg"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-extrabold text-slate-900" id="statHoy">0</div>
                <p class="text-xs text-slate-500 mt-1">Recibidas en las últimas 24 hrs</p>
            </div>
        </div>
    </section>

    <!-- Main Table Container -->
    <section class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="relative w-full sm:w-96">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input 
                    id="BuscadorCotizaciones" 
                    type="text" 
                    class="w-full bg-white pl-10 pr-10 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition placeholder:text-slate-400" 
                    placeholder="Buscar cliente, teléfono, destino..."
                >
                <button id="btnLimpiarBusqueda" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
            <div class="text-xs text-slate-500 w-full sm:w-auto text-right">
                Mostrando cotizaciones activas
            </div>
        </div>

        <div class="overflow-x-auto">
            <table id="tablaCotizaciones" class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 border-b border-slate-100 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-5 py-3.5">ID</th>
                        <th class="px-5 py-3.5">Cliente / Servicio</th>
                        <th class="px-5 py-3.5">Contacto</th>
                        <th class="px-5 py-3.5">Ruta (Origen → Destino)</th>
                        <th class="px-5 py-3.5">Inventario</th>
                        <th class="px-5 py-3.5 text-right">Maniobra</th>
                        <th class="px-5 py-3.5 text-right">Total</th>
                        <th class="px-5 py-3.5 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                            <i class="bi bi-arrow-repeat animate-spin text-2xl mb-2 block text-blue-500"></i>
                            Cargando información...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="emptyState" class="hidden p-12 text-center">
            <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="bi bi-inbox text-2xl"></i>
            </div>
            <h3 class="text-base font-semibold text-slate-800">No se encontraron resultados</h3>
            <p class="text-slate-400 text-xs mt-1">Intenta ajustando los términos de búsqueda.</p>
        </div>
    </section>

</main>

<!-- Modal Editar -->
<div id="modalEditar" class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-opacity">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform transition-all">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Revisar Cotización</h3>
                <p class="text-xs text-slate-500">ID de registro: <span id="modalIdDisplay" class="font-mono font-semibold text-blue-600">#—</span></p>
            </div>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-200/50 transition">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <form id="formEditar" onsubmit="event.preventDefault(); guardarCambios();">
                <input type="hidden" name="id" id="edit_id">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nombre del Cliente</label>
                        <input type="text" name="nombre_cliente" id="edit_cliente" 
                               class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-slate-800">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Teléfono de Contacto</label>
                        <input type="tel" name="telefono" id="edit_telefono" 
                               class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-slate-800">
                    </div>
                </div>

                <div class="mt-4 p-4 bg-slate-50/80 rounded-xl border border-slate-200/60 space-y-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-2">Desglose de Costos</span>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-slate-600 mb-1">Flete / Subtotal ($)</label>
                            <input type="number" id="edit_flete" 
                                   class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-slate-900 calcular-total"
                                   placeholder="0.00" step="0.01">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-600 mb-1">Maniobra ($)</label>
                            <input type="number" name="maniobra" id="edit_maniobra" 
                                   class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-slate-900 calcular-total"
                                   placeholder="0.00" step="0.01">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-200/80 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-700">Total Final Cotizado</p>
                            <p class="text-[11px] text-slate-400">Sumatoria automática</p>
                        </div>
                        <input type="hidden" name="total" id="edit_total">
                        <span id="edit_suma_final" class="text-2xl font-bold text-blue-600">$0.00</span>
                    </div>
                </div>
            </form>
        </div>

        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            <button type="button" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-xl font-medium text-sm transition" onclick="closeModal()">
                Cancelar
            </button>
            <button type="button" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium text-sm transition shadow-sm shadow-blue-500/20 active:scale-95" onclick="guardarCambios()">
                Guardar Cambios
            </button>
        </div>
    </div>
</div>

<!-- JS de Plantilla y Librerías -->
<script src="assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/reporte.js? v=55"></script>




<?php include __DIR__ . '/calculadora_msi.php'; ?>

</body>
</html>
