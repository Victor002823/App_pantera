<?php
/**
 * db_manager.php
 * Control El Lince — Administrador de Base de Datos
 *
 * Panel principal: lista todas las tablas de MySQL con su número de
 * registros, permite descargar CSV/XLSX y subir un archivo CSV/XLSX
 * para revisar cambios (la revisión y aplicación real ocurre en
 * preview_csv.php / import_csv.php).
 */

declare(strict_types=1);

// =======================
// CONTROL DE SESIÓN
// =======================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$inactive = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    $_SESSION = [];
    session_destroy();
    header("Location: /index.php?timeout=1");
    exit;
}

if (empty($_SESSION['usuario'])) {
    header("Location: /index.php");
    exit;
}

$_SESSION['last_activity'] = time();

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/db_manager_helpers.php';

$db  = new db();
$pdo = $db->conexion();

if (!$pdo instanceof PDO) {
    die("Error de conexión a la base de datos");
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// =======================
// ESTADÍSTICAS GLOBALES
// =======================
$totalTablas = 0;
$totalRegistrosGeneral = 0;

$tablas = [];
foreach (db_get_table_names($pdo) as $nombreTabla) {
    $conteo = (int) $pdo->query('SELECT COUNT(*) FROM "'.$nombreTabla.'"')->fetchColumn();
    $tienePk = !empty(db_get_primary_key($pdo, $nombreTabla));
    
    $totalTablas++;
    $totalRegistrosGeneral += $conteo;

    $tablas[] = [
        'nombre'    => $nombreTabla,
        'registros' => $conteo,
        'tiene_pk'  => $tienePk,
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administrador de Base de Datos - Control El Lince</title>
<?php include __DIR__.'/nav.php'; ?>
<style>
    /* ==========================================
       ESTILOS LIMPIOS Y ARMONIZADOS PARA LA TABLA
       ========================================== */
    .table-wrapper {
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        padding: 20px;
        margin-bottom: 2rem;
    }
    
    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        margin-bottom: 0;
    }

    .admin-table th, 
    .admin-table td {
        padding: 12px 15px;
        vertical-align: middle;
        white-space: nowrap; /* Evita que el contenido de las celdas se rompa feo */
    }

    .admin-table thead th {
        background-color: #f8f9fa;
        color: #2b2b2b;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }

    .admin-table tbody td {
        border-bottom: 1px solid #dee2e6;
    }

    .admin-table tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }

    /* Ajuste específico para formularios y botones dentro de la tabla */
    .admin-table form {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .admin-table .form-control-sm {
        height: 31px;
        padding: 4px 8px;
        font-size: 0.875rem;
        display: inline-block;
        width: auto;
    }

    .admin-table .btn-sm {
        padding: 4px 10px;
        font-size: 0.875rem;
        line-height: 1.5;
        white-space: nowrap;
    }

    .action-buttons {
        display: flex;
        gap: 5px;
        align-items: center;
    }
</style>
</head>
<body>
<div id="main">

    <header class="mb-3">
        <a href="#" class="burger-btn d-block d-xl-none">
            <i class="bi bi-justify fs-3"></i>
        </a>
    </header>

    <!-- =======================
         CARDS DE ESTADÍSTICAS
    ======================= -->
    <div class="row g-3 mb-4">

        <!-- Tablas totales -->
        <div class="col-6">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-4">
                            <div class="stats-icon purple">
                                <i class="iconly-boldFolder"></i>
                            </div>
                        </div>
                        <div class="col-8">
                            <h6 class="text-muted font-semibold mb-1">
                                Tablas totales
                            </h6>
                            <h6 class="font-extrabold mb-0">
                                <?= $totalTablas ?>
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registros generales -->
        <div class="col-6">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-4">
                            <div class="stats-icon yellow">
                                <i class="iconly-boldGraph"></i>
                            </div>
                        </div>
                        <div class="col-8">
                            <h6 class="text-muted font-semibold mb-1">
                                Registros totales
                            </h6>
                            <h6 class="font-extrabold mb-0">
                                <?= number_format($totalRegistrosGeneral) ?>
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="page-heading d-flex justify-content-between align-items-center mb-4">
        <h3>Administrador de Base de Datos</h3>
        <a href="db_history.php" class="btn btn-outline-secondary btn-sm">Historial de respaldos</a>
    </div>

    <div class="page-content">
        <section class="section">
            
            <!-- =======================
                 TABLA CON DISEÑO CORREGIDO
            ======================= -->
            <div class="table-wrapper">
                <div class="table-header">
                    <h4 class="card-title mb-0">Tablas de la Base de Datos</h4>
                    <span class="badge bg-primary">
                        <?= $totalTablas ?> tablas detectadas
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Tabla</th>
                                <th>Registros</th>
                                <th>Llave primaria</th>
                                <th>Descargar</th>
                                <th>Importar archivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tablas as $t): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t['nombre']) ?></strong></td>
                                <td><?= number_format($t['registros']) ?></td>
                                <td>
                                    <?php if ($t['tiene_pk']): ?>
                                        <span class="badge bg-success">Sí</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary" title="Sin llave primaria: no se puede importar">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="export_csv.php?tabla=<?= urlencode($t['nombre']) ?>&formato=csv">CSV</a>
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="export_csv.php?tabla=<?= urlencode($t['nombre']) ?>&formato=xlsx">XLSX</a>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($t['tiene_pk']): ?>
                                    <form method="post" action="preview_csv.php" enctype="multipart/form-data">
                                        <input type="hidden" name="tabla" value="<?= htmlspecialchars($t['nombre']) ?>">
                                        <input type="file" name="archivo" accept=".csv,.xlsx" required class="form-control form-control-sm" style="width: 200px;">
                                        <button type="submit" class="btn btn-sm btn-primary">Revisar</button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-muted small">No disponible</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="text-muted mt-3 mb-0">
                    Al subir un archivo se muestra primero una vista previa con únicamente los
                    cambios reales detectados; la base de datos no se modifica hasta confirmar
                    la importación. Se genera un respaldo automático antes de cada exportación
                    y de cada importación.
                </p>
            </div>

        </section>
    </div>
</div>

<script src="assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
