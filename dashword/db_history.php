<?php
/**
 * db_history.php
 * Control El Lince — Administrador de Base de Datos
 *
 * Administrador de respaldos. Lista todo lo que hay en backups/, permite
 * descargar y eliminar archivos de respaldo individuales, y permite
 * restaurar un respaldo completo sobre su tabla correspondiente.
 *
 * IMPORTANTE sobre "restaurar": a diferencia de import_csv.php (que solo
 * actualiza columnas modificadas y nunca inserta ni elimina), restaurar un
 * respaldo es una operación DESTRUCTIVA e intencional: reemplaza el
 * contenido completo de la tabla por el contenido del respaldo elegido
 * (vacía la tabla e inserta todas las filas del respaldo). Antes de
 * restaurar, siempre se genera un respaldo de seguridad del estado actual.
 */

declare(strict_types=1);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/db_manager_helpers.php';
require_once __DIR__.'/vendor/autoload.php';

const DB_MANAGER_BACKUPS_DIR = __DIR__.'/backups';

$db  = new db();
$pdo = $db->conexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mensaje = null;
$error = null;

/* -------------------------------------------------------------------- */
/*  DESCARGA (GET, no destructiva)                                      */
/* -------------------------------------------------------------------- */

if (($_GET['accion'] ?? '') === 'descargar') {
    $tabla = trim((string) ($_GET['tabla'] ?? ''));
    $archivo = trim((string) ($_GET['archivo'] ?? ''));

    // Validación estricta: solo el nombre de archivo, sin rutas, y debe
    // pertenecer realmente a la carpeta de respaldos de esa tabla.
    if ($tabla === '' || $archivo === '' || basename($archivo) !== $archivo) {
        http_response_code(400);
        exit('Solicitud inválida.');
    }

    $ruta = DB_MANAGER_BACKUPS_DIR.'/'.$tabla.'/'.$archivo;
    if (!is_file($ruta)) {
        http_response_code(404);
        exit('Archivo no encontrado.');
    }

    $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
    $mime = $ext === 'xlsx'
        ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        : 'text/csv; charset=UTF-8';

    header('Content-Type: '.$mime);
    header('Content-Disposition: attachment; filename="'.$archivo.'"');
    header('Content-Length: '.filesize($ruta));
    readfile($ruta);
    exit;
}

/* -------------------------------------------------------------------- */
/*  ELIMINAR / RESTAURAR (POST, requiere confirmación en la UI)          */
/* -------------------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $accion = $_POST['accion'] ?? '';
        $tabla = trim((string) ($_POST['tabla'] ?? ''));
        $archivo = trim((string) ($_POST['archivo'] ?? ''));

        if ($tabla === '' || $archivo === '' || basename($archivo) !== $archivo) {
            throw new RuntimeException('Solicitud inválida.');
        }

        $ruta = DB_MANAGER_BACKUPS_DIR.'/'.$tabla.'/'.$archivo;
        if (!is_file($ruta)) {
            throw new RuntimeException('El archivo de respaldo no existe.');
        }

        if ($accion === 'eliminar') {
            unlink($ruta);
            $mensaje = 'Respaldo eliminado: '.$archivo;

        } elseif ($accion === 'restaurar') {
            if (!db_validate_table($pdo, $tabla)) {
                throw new RuntimeException('La tabla indicada no existe.');
            }

            $llavePrimaria = db_get_primary_key($pdo, $tabla);
            if (empty($llavePrimaria)) {
                throw new RuntimeException('La tabla no tiene llave primaria; no se puede restaurar de forma segura.');
            }

            $columnasTabla = db_get_table_columns($pdo, $tabla);
            $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

            $cargado = db_load_spreadsheet_rows($ruta, $extension);
            $encabezados = array_values(array_intersect($cargado['encabezados'], $columnasTabla));

            if (empty($encabezados)) {
                throw new RuntimeException('El respaldo no contiene columnas reconocibles de la tabla actual.');
            }
            if (empty($cargado['filas'])) {
                throw new RuntimeException('El respaldo está vacío; no hay nada que restaurar.');
            }

            // Respaldo de seguridad del estado actual, antes de reemplazarlo
            $rutaSeguridad = db_backup_table($pdo, $tabla, DB_MANAGER_BACKUPS_DIR, 'csv');

            $pdo->beginTransaction();

            // Reemplazo completo: se vacía la tabla y se insertan todas las
            // filas del respaldo. Esta es la única operación del módulo que
            // hace INSERT/DELETE, y solo ocurre aquí, de forma explícita.
            $pdo->exec('DELETE FROM "'.$tabla.'"');

            $placeholders = implode(', ', array_fill(0, count($encabezados), '?'));
            $columnasSql = implode(", ", array_map(fn($c) => "\"$c\"", $encabezados));
            $stmtInsert = $pdo->prepare("INSERT INTO \"$tabla\" ($columnasSql) VALUES ($placeholders)");

            $filasInsertadas = 0;
            foreach ($cargado['filas'] as $fila) {
                $valores = array_map(fn($col) => $fila[$col] ?? null, $encabezados);
                $stmtInsert->execute($valores);
                $filasInsertadas++;
            }

            $pdo->commit();

            $mensaje = "Tabla '$tabla' restaurada desde $archivo. Filas insertadas: $filasInsertadas. "
                     . 'Respaldo de seguridad previo: '.basename($rutaSeguridad);
        } else {
            throw new RuntimeException('Acción no reconocida.');
        }

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

/* -------------------------------------------------------------------- */
/*  LISTADO                                                              */
/* -------------------------------------------------------------------- */

$respaldos = db_list_backups(DB_MANAGER_BACKUPS_DIR);

// Agrupar por tabla para la vista
$porTabla = [];
foreach ($respaldos as $r) {
    $porTabla[$r['tabla']][] = $r;
}
ksort($porTabla);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historial de respaldos - Control El Lince</title>
<?php include __DIR__.'/nav.php'; ?>
</head>
<body>
<div id="main">
    <div class="page-heading">
        <h3>Historial de respaldos</h3>
    </div>

    <div class="page-content">
        <section class="section">

            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (empty($porTabla)): ?>
                <div class="alert alert-info">Todavía no hay respaldos generados.</div>
            <?php endif; ?>

            <?php foreach ($porTabla as $tabla => $items): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($tabla) ?></h5>

                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Archivo</th>
                                        <th>Formato</th>
                                        <th>Fecha</th>
                                        <th>Tamaño</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['archivo']) ?></td>
                                        <td><span class="badge bg-secondary"><?= strtoupper($r['formato']) ?></span></td>
                                        <td><?= htmlspecialchars($r['fecha']) ?></td>
                                        <td><?= htmlspecialchars($r['tamano']) ?></td>
                                        <td class="d-flex gap-1">
                                            <a class="btn btn-sm btn-outline-primary"
                                               href="db_history.php?accion=descargar&tabla=<?= urlencode($tabla) ?>&archivo=<?= urlencode($r['archivo']) ?>">
                                                Descargar
                                            </a>

                                            <form method="post" onsubmit="return confirm('¿ELIMINAR este respaldo? Esta acción no se puede deshacer.');">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="tabla" value="<?= htmlspecialchars($tabla) ?>">
                                                <input type="hidden" name="archivo" value="<?= htmlspecialchars($r['archivo']) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                            </form>

                                            <form method="post" onsubmit="return confirm('¿RESTAURAR la tabla \'<?= htmlspecialchars($tabla) ?>\' completa desde este respaldo?\n\nEsto reemplazará TODO el contenido actual de la tabla. Se creará un respaldo de seguridad antes de continuar, pero esta acción no se puede deshacer directamente.');">
                                                <input type="hidden" name="accion" value="restaurar">
                                                <input type="hidden" name="tabla" value="<?= htmlspecialchars($tabla) ?>">
                                                <input type="hidden" name="archivo" value="<?= htmlspecialchars($r['archivo']) ?>">
                                                <button type="submit" class="btn btn-sm btn-warning">Restaurar</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <a href="db_manager.php" class="btn btn-secondary">Volver al administrador</a>
        </section>
    </div>
</div>

<script src="assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
