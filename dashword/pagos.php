<?php
// =======================
// CONTROL DE SESIÓN Y BD
// =======================
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['usuario'])) { header("Location: /index.php"); exit; }
// =======================
// PERMISOS
// =======================

$puedeEliminar = false;

if (
    isset($_SESSION['rol']) &&
    $_SESSION['rol'] === 'admin'
) {
    $puedeEliminar = true;
}

// Username del asesor logueado (la sesión guarda un array: correo, nombre_usuario)
$asesorActual = $_SESSION['usuario']['nombre_usuario'] ?? '';

require_once __DIR__ . '/../config/db.php';
$db = new db();
$pdo = $db->conexion();

// --- INICIALIZACIÓN DE VARIABLES (Para evitar los errores) ---
$total = 0;
$pagos = [];
$stats = ['total_cobrado' => 0, 'total_pendiente' => 0];

try {

    // =======================
    // FILTRO ARCHIVADOS + ASESOR
    // =======================

    $filtroArchivo = $_GET['archivo'] ?? 'activos';

    if (!$puedeEliminar) {
        $filtroArchivo = 'activos'; // usuarios normales solo ven activos, sin importar el query string
    }

    $condiciones = [];
    $params = [];

    if ($filtroArchivo == "activos") {
        $condiciones[] = "deleted_at IS NULL";
    }
    if ($filtroArchivo == "archivados") {
        $condiciones[] = "deleted_at IS NOT NULL";
    }
    // "todos" no agrega condición de archivo

    // Si NO es admin, solo ve sus propios pagos
    if (!$puedeEliminar) {
        $condiciones[] = "asesor = :asesor";
        $params['asesor'] = $asesorActual;
    }

    // Búsqueda en tabla (server-side, para que funcione a través de todas las páginas)
    $busqueda = trim($_GET['buscar'] ?? '');
    if ($busqueda !== '') {
        $condiciones[] = "(nombre_cliente LIKE :busqueda OR cotizacion_id LIKE :busqueda)";
        $params['busqueda'] = '%' . $busqueda . '%';
    }

    $where = count($condiciones) ? "WHERE " . implode(" AND ", $condiciones) : "";


    // =======================
    // ESTADÍSTICAS
    // =======================

    $statsQuery = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN status = 'completed' THEN monto ELSE 0 END),0) AS total_cobrado,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN monto ELSE 0 END),0) AS total_pendiente
    FROM pagos
    $where
    ");
    $statsQuery->execute($params);

    $stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

    // =======================
    // PAGINACIÓN Y LISTADO
    // =======================
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 10;
    $offset = ($page - 1) * $limit;
    
    $countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM pagos
    $where
");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("
    SELECT *
    FROM pagos
    $where
    ORDER BY created_at DESC
    LIMIT $limit OFFSET $offset
");
    $stmt->execute($params);
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Si hay un error (ej. tabla no existe), no hacemos nada, 
    // las variables ya están inicializadas vacías arriba.
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .loading-spinner { border: 2px solid #f3f3f3; border-top: 2px solid #3b82f6; border-radius: 50%; width: 16px; height: 16px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

<?php include __DIR__ . '/nav.php'; ?>

<div id="main" class="p-4 md:p-6 lg:p-8">
   
<header class="mb-3">
    <a href="#" class="burger-btn d-block d-xl-none">
        <i class="bi bi-justify fs-3"></i>
    </a>
</header>         
    <div class="max-w-6xl mx-auto">
        
        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Panel de Cobranza</h1>
                <p class="text-gray-500">Gestiona tus servicios y genera CLABEs de pago.</p>
            </div>
        </div>

        <!-- CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Tarjeta 1: Total Cobrado -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center transition-transform hover:shadow-md">
        <div class="p-4 bg-emerald-50 text-emerald-600 rounded-2xl mr-4">
            <i class="bi bi-cash-stack text-2xl"></i>
        </div>
        <div>
            <p class="text-xs !text-gray-500 font-bold uppercase tracking-wider">Total Cobrado</p>
            <h2 class="text-2xl font-extrabold !text-black">$<?= number_format($stats['total_cobrado'] ?? 0, 2) ?></h2>
        </div>
    </div>

    <!-- Tarjeta 2: Pendiente -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center transition-transform hover:shadow-md">
        <div class="p-4 bg-amber-50 text-amber-600 rounded-2xl mr-4">
            <i class="bi bi-clock-history text-2xl"></i>
        </div>
        <div>
            <p class="text-xs !text-gray-500 font-bold uppercase tracking-wider">Pendiente</p>
            <h2 class="text-2xl font-extrabold !text-black">$<?= number_format($stats['total_pendiente'] ?? 0, 2) ?></h2>
        </div>
    </div>
</div>

        <!-- BUSCADOR -->
        <div class="relative mb-8 max-w-xl">
            <label class="block text-sm font-medium text-gray-700 mb-2">Crear nueva cobranza</label>
            <div class="relative">
                <input type="text" id="buscador" 
                       class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-200 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all shadow-sm"
                       placeholder="Escribe el nombre del servicio..." onkeyup="buscar(this.value)" autocomplete="off">
                <i class="bi bi-search absolute left-4 top-4 text-gray-400"></i>
            </div>
            <div id="resultados" class="absolute w-full mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 z-50 hidden overflow-hidden">
                <!-- Se llena vía JS -->
            </div>
        </div>
                
<!-- FILTRO DE TABLA -->
<div class="mb-4 flex flex-wrap gap-3 items-center">

    <form method="GET" id="filtroForm" class="flex flex-1 flex-wrap gap-3 items-center">

        <?php if ($puedeEliminar): ?>
        <select name="archivo"
                onchange="this.form.submit()"
                class="border rounded-lg px-4 py-2 shrink-0">
            <option value="activos" <?= ($_GET['archivo'] ?? 'activos')=='activos'?'selected':'' ?>>Pagos activos</option>
            <option value="archivados" <?= ($_GET['archivo'] ?? '')=='archivados'?'selected':'' ?>>Archivados</option>
            <option value="todos" <?= ($_GET['archivo'] ?? '')=='todos'?'selected':'' ?>>Todos</option>
        </select>
        <?php endif; ?>

        <input
            type="text"
            name="buscar"
            id="buscadorTabla"
            value="<?= htmlspecialchars($_GET['buscar'] ?? '', ENT_QUOTES) ?>"
            class="flex-1 min-w-0 px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            placeholder="Buscar en tabla..."
            oninput="debounceBuscarTabla()"
        >

    </form>

</div>

<script>
let debounceTablaTimer;
function debounceBuscarTabla() {
    clearTimeout(debounceTablaTimer);
    debounceTablaTimer = setTimeout(() => {
        document.getElementById('filtroForm').submit();
    }, 500);
}
</script>

        <!-- TABLA -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-900">Historial de Cobros</h3>
                <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold uppercase tracking-wider"><?= $total ?> registros</span>
            </div>
            
            <div class="overflow-x-auto">
                <table id="tablaPagos" class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Folio</th>
                            <th class="px-6 py-4">Cliente</th>
                            <th class="px-6 py-4">Monto</th>
                            <th class="px-6 py-4 text-center">Estatus</th>
                            <th class="px-6 py-4 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($pagos)): ?>
                            <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                <i class="bi bi-inbox text-4xl block mb-2 opacity-50"></i>
                                No hay pagos registrados aún.
                            </td></tr>
                        <?php else: ?>
                          <?php foreach ($pagos as $p): ?>
    <tr class="hover:bg-blue-50/30 transition-colors" data-pago-id="<?= $p['id'] ?>">
        <td class="px-6 py-4 font-mono text-sm text-gray-500">#<?= $p['cotizacion_id'] ?></td>
        <td class="px-6 py-4 font-medium text-gray-700"><?= $p['nombre_cliente'] ?></td>
        <td class="px-6 py-4 font-bold text-gray-900">$<?= number_format($p['monto'], 2) ?></td>
        <td class="px-6 py-4 text-center">
    <?php
    switch ($p['status']) {
        case 'completed':
            echo '<span class="px-3 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full text-[10px] font-bold uppercase tracking-widest">Pagado</span>';
            break;
        case 'pending':
            echo '<span class="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-bold uppercase tracking-widest">Pendiente</span>';
            break;
        case 'failed':
            echo '<span class="px-3 py-1 bg-red-50 text-red-700 border border-red-200 rounded-full text-[10px] font-bold uppercase tracking-widest">Fallido</span>';
            break;
        case 'cancelled':
            echo '<span class="px-3 py-1 bg-gray-100 text-gray-700 border border-gray-300 rounded-full text-[10px] font-bold uppercase tracking-widest">Cancelado</span>';
            break;
        case 'refunded':
            echo '<span class="px-3 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-full text-[10px] font-bold uppercase tracking-widest">Reembolsado</span>';
            break;
        default:
            echo '<span class="px-3 py-1 bg-slate-100 text-slate-700 border border-slate-300 rounded-full text-[10px] font-bold uppercase tracking-widest">' . htmlspecialchars($p['status']) . '</span>';
            break;
    }
    ?>
</td>
        <td class="px-6 py-4 text-right">
            <div class="inline-flex gap-2">
                <button onclick="verDetalle(
    '<?= htmlspecialchars($p['clabe'] ?? '', ENT_QUOTES) ?>',
    '<?= htmlspecialchars($p['paynet_reference'] ?? '', ENT_QUOTES) ?>',
    '<?= htmlspecialchars($p['payment_url'] ?? '', ENT_QUOTES) ?>',
    '<?= $p['id'] ?>',
    '<?= htmlspecialchars($p['nombre_cliente'], ENT_QUOTES) ?>',
    '<?= number_format($p['monto'], 2) ?>',
    '<?= htmlspecialchars($p['status'] ?? '', ENT_QUOTES) ?>'
)"
class="text-blue-600 hover:text-blue-800 font-bold text-xs bg-blue-50 px-4 py-2 rounded-lg hover:bg-blue-100 transition-all">
    <i class="bi bi-eye"></i> Ver Detalles
</button>
                
                <?php if($puedeEliminar): ?>
                    <?php if($p['deleted_at'] !== null): ?>
                        <button 
                        onclick="restaurarPago(<?= $p['id'] ?>)"
                        class="text-green-600 hover:text-green-800 font-bold text-xs bg-green-50 px-4 py-2 rounded-lg hover:bg-green-100 transition-all">
                            <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                        </button>
                    <?php else: ?>
                        <button 
                        onclick="eliminarPago(<?= $p['id'] ?>)"
                        class="text-red-600 hover:text-red-800 font-bold text-xs bg-red-50 px-4 py-2 rounded-lg hover:bg-red-100 transition-all">
                            <i class="bi bi-trash"></i> Archivar
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php
                $totalPaginas = (int)ceil(($total ?: 0) / $limit);
                $archivoActual = htmlspecialchars($_GET['archivo'] ?? 'activos', ENT_QUOTES);
                $buscarActual = urlencode($_GET['buscar'] ?? '');
                $qsBase = "archivo=$archivoActual" . ($buscarActual !== '' ? "&buscar=$buscarActual" : '');
            ?>
            <?php if ($totalPaginas > 1): ?>
            <div class="px-6 py-4 border-t border-gray-50 flex items-center justify-between">
                <p class="text-xs text-gray-400">
                    Página <span class="font-semibold text-gray-600"><?= $page ?></span> de
                    <span class="font-semibold text-gray-600"><?= $totalPaginas ?></span>
                </p>
                <div class="flex items-center gap-1">
                    <!-- Anterior -->
                    <?php if ($page > 1): ?>
                        <a href="?<?= $qsBase ?>&page=<?= $page - 1 ?>"
                           class="px-3 py-2 rounded-lg text-sm font-semibold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-all">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 rounded-lg text-sm font-semibold text-gray-300 border border-gray-100 cursor-not-allowed">
                            <i class="bi bi-chevron-left"></i>
                        </span>
                    <?php endif; ?>

                    <!-- Números de página -->
                    <?php
                        $rango = 2;
                        $inicio = max(1, $page - $rango);
                        $fin = min($totalPaginas, $page + $rango);

                        if ($inicio > 1) {
                            echo '<a href="?' . $qsBase . '&page=1" class="px-3 py-2 rounded-lg text-sm font-semibold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-all">1</a>';
                            if ($inicio > 2) echo '<span class="px-2 text-gray-300">…</span>';
                        }

                        for ($i = $inicio; $i <= $fin; $i++) {
                            if ($i == $page) {
                                echo '<span class="px-3 py-2 rounded-lg text-sm font-bold text-white bg-blue-600">' . $i . '</span>';
                            } else {
                                echo '<a href="?' . $qsBase . '&page=' . $i . '" class="px-3 py-2 rounded-lg text-sm font-semibold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-all">' . $i . '</a>';
                            }
                        }

                        if ($fin < $totalPaginas) {
                            if ($fin < $totalPaginas - 1) echo '<span class="px-2 text-gray-300">…</span>';
                            echo '<a href="?' . $qsBase . '&page=' . $totalPaginas . '" class="px-3 py-2 rounded-lg text-sm font-semibold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-all">' . $totalPaginas . '</a>';
                        }
                    ?>

                    <!-- Siguiente -->
                    <?php if ($page < $totalPaginas): ?>
                        <a href="?<?= $qsBase ?>&page=<?= $page + 1 ?>"
                           class="px-3 py-2 rounded-lg text-sm font-semibold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-all">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 rounded-lg text-sm font-semibold text-gray-300 border border-gray-100 cursor-not-allowed">
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
                        
<script src="assets/js/pagos_buscar_tabla.js"></script>
<script src="assets/js/pagos.js?v=9"></script>

                        
  <!-- JS del admin -->                      
<script src="assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>

<!-- Modal de Confirmación -->
<div id="modalConfirm" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-[200] p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform transition-all">
        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-yellow-100 rounded-full mb-4">
    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
        d="M12 9v2m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20h15.6a2 2 0 001.73-3l-7.82-13.5a2 2 0 00-3.42 0z"/>
    </svg>
</div>
        <h3 class="text-lg font-bold text-gray-900 text-center mb-2">Confirmar acción</h3>
        <p id="confirmMessage" class="text-gray-600 text-center mb-6 text-sm">¿Estás seguro?</p>
        <div class="flex gap-3">
            <button onclick="cerrarConfirm()" class="flex-1 px-4 py-2.5 rounded-lg border border-gray-200 text-gray-700 font-semibold hover:bg-gray-50 transition-all">Cancelar</button>
            <button id="confirmBtn" class="flex-1 px-4 py-2.5 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700 transition-all">Confirmar</button>
        </div>
    </div>
</div>

<!-- Contenedor de notificaciones flotantes -->
<div id="toast-container" class="fixed bottom-5 right-5 z-[100] flex flex-col gap-3"></div>

<!-- Modal de Detalles -->
<div id="modalDetalle" class="fixed inset-0 bg-gray-900/50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl">
        <h3 class="text-lg font-bold mb-4 text-gray-800">Detalles de Cobro</h3>
        <div id="contenidoModal" class="space-y-3 text-sm"></div>
        <button onclick="document.getElementById('modalDetalle').classList.add('hidden')" class="mt-6 w-full bg-gray-100 py-2 rounded-lg font-bold text-gray-600 hover:bg-gray-200">Cerrar</button>
    </div>
</div>
  
</body>
</html>
