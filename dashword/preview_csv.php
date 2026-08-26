<?php
/**
 * preview_csv.php
 * Control El Lince — Administrador de Base de Datos
 *
 * Reconstruido desde cero. Compatible con PHP 8.5 y PhpSpreadsheet 5.9.
 *
 * Recibe un archivo CSV o XLSX subido desde db_manager.php, lo compara
 * contra la tabla de MySQL indicada (usando la llave primaria detectada
 * automáticamente) y muestra ÚNICAMENTE los cambios reales. No modifica
 * la base de datos. Al confirmar, envía la tabla y un archivo temporal
 * (JSON con los cambios) a import_csv.php.
 */

declare(strict_types=1);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', '0'); // no mostrar errores crudos al usuario final

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/db_manager_helpers.php';

require_once __DIR__.'/vendor/autoload.php';

const DB_MANAGER_BACKUPS_DIR = __DIR__.'/backups';

$db  = new db();
$pdo = $db->conexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = null;
$resultado = null; // arreglo con el resultado de la comparación

/* -------------------------------------------------------------------- */
/*  VALIDAR ENTRADA                                                     */
/* -------------------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['archivo']['tmp_name'])) {
    header('Location: db_manager.php');
    exit;
}

$tabla = trim((string) ($_POST['tabla'] ?? ''));

try {
    if ($tabla === '' || !db_validate_table($pdo, $tabla)) {
        throw new RuntimeException('La tabla indicada no existe.');
    }

    if ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir el archivo (código '.$_FILES['archivo']['error'].').');
    }

    $nombreOriginal = $_FILES['archivo']['name'];
    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

    if (!in_array($extension, ['csv', 'xlsx'], true)) {
        throw new RuntimeException('Formato no soportado. Solo se aceptan archivos CSV o XLSX.');
    }

    /* ------------------------------------------------------------ */
    /*  CARGAR ARCHIVO (celda por celda, nunca con toArray())        */
    /* ------------------------------------------------------------ */

    $rutaTmp = $_FILES['archivo']['tmp_name'];
    $cargado = db_load_spreadsheet_rows($rutaTmp, $extension);
    $encabezados = $cargado['encabezados'];

    if (empty($encabezados)) {
        throw new RuntimeException('No se pudieron leer los encabezados del archivo.');
    }
    if (empty($cargado['filas'])) {
        throw new RuntimeException('El archivo no contiene datos (solo encabezados o está vacío).');
    }

    /* ------------------------------------------------------------ */
    /*  VALIDAR COLUMNAS Y LLAVE PRIMARIA                            */
    /* ------------------------------------------------------------ */

    $columnasTabla = db_get_table_columns($pdo, $tabla);
    $llavePrimaria = db_get_primary_key($pdo, $tabla);

    if (empty($llavePrimaria)) {
        throw new RuntimeException('La tabla "'.$tabla.'" no tiene llave primaria definida. No es posible comparar registros de forma segura.');
    }

    foreach ($llavePrimaria as $pk) {
        if (!in_array($pk, $encabezados, true)) {
            throw new RuntimeException('El archivo no contiene la columna de llave primaria requerida: "'.$pk.'".');
        }
    }

    // Columnas del archivo que sí existen en la tabla (se ignoran las que no)
    $columnasValidas = array_values(array_intersect($encabezados, $columnasTabla));
    $columnasIgnoradas = array_values(array_diff($encabezados, $columnasTabla));

    $filasArchivo = $cargado['filas']; // cada elemento ya es ['nombreColumna' => valor, ...]

    /* ------------------------------------------------------------ */
    /*  PREPARAR CONSULTA DE COMPARACIÓN CONTRA LA BD                */
    /* ------------------------------------------------------------ */

    $condicionPk = implode(" AND ", array_map(fn($pk) => "\"$pk\" = ?", $llavePrimaria));
    $stmtBuscar = $pdo->prepare("SELECT * FROM \"$tabla\" WHERE $condicionPk LIMIT 1");

    $cambios = [];        // filas con al menos un cambio real
    $sinCambios = 0;      // filas iguales a la BD
    $noEncontradas = [];  // llaves primarias que no existen en la BD (se omiten, no se insertan)

    foreach ($filasArchivo as $registro) {
        $valoresPk = [];
        foreach ($llavePrimaria as $pk) {
            $valoresPk[] = $registro[$pk] ?? '';
        }

        if (in_array('', $valoresPk, true)) {
            continue; // fila sin llave primaria válida, se omite
        }

        $stmtBuscar->execute($valoresPk);
        $filaBd = $stmtBuscar->fetch(PDO::FETCH_ASSOC);

        if ($filaBd === false) {
            // No existe en la BD -> se omite (esta herramienta NO inserta registros)
            $noEncontradas[] = implode(' / ', $valoresPk);
            continue;
        }

        $cambiosFila = [];
        foreach ($columnasValidas as $columna) {
            if (in_array($columna, $llavePrimaria, true)) {
                continue; // no se actualiza la llave primaria
            }

            $valorArchivo = $registro[$columna] ?? '';
            $valorBd = $filaBd[$columna] ?? '';

            if (!db_values_equal((string) $valorBd, (string) $valorArchivo)) {
                $cambiosFila[$columna] = [
                    'antes'    => $valorBd,
                    'despues'  => $valorArchivo,
                ];
            }
        }

        if (!empty($cambiosFila)) {
            $cambios[] = [
                'pk'      => array_combine($llavePrimaria, $valoresPk),
                'cambios' => $cambiosFila,
            ];
        } else {
            $sinCambios++;
        }
    }

    /* ------------------------------------------------------------ */
    /*  GUARDAR RESULTADO EN ARCHIVO TEMPORAL PARA import_csv.php    */
    /* ------------------------------------------------------------ */

    $tmpDir = db_tmp_dir(DB_MANAGER_BACKUPS_DIR);
    $token = bin2hex(random_bytes(16));
    $rutaJson = $tmpDir.'/preview_'.$token.'.json';

    file_put_contents($rutaJson, json_encode([
        'tabla'          => $tabla,
        'llave_primaria' => $llavePrimaria,
        'cambios'        => $cambios,
        'creado'         => date('c'),
    ], JSON_UNESCAPED_UNICODE));

    $_SESSION['db_manager_preview_token'] = $token; // vínculo adicional de seguridad por sesión

    $resultado = [
        'tabla'              => $tabla,
        'token'              => $token,
        'total_filas_archivo' => count($filasArchivo),
        'filas_con_cambios'  => count($cambios),
        'filas_sin_cambios'  => $sinCambios,
        'filas_no_encontradas' => $noEncontradas,
        'columnas_ignoradas' => $columnasIgnoradas,
        'cambios'            => $cambios,
    ];

} catch (Throwable $e) {
    $error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vista previa de importación - Control El Lince</title>
<?php include __DIR__.'/nav.php'; ?>
</head>
<body>
<div id="main">
    <div class="page-heading">
        <h3>Vista previa de importación<?= $resultado ? ' — '.htmlspecialchars($resultado['tabla']) : '' ?></h3>
    </div>

    <div class="page-content">
        <section class="section">
            <div class="card">
                <div class="card-body">

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <strong>No se pudo procesar el archivo:</strong> <?= htmlspecialchars($error) ?>
                        </div>
                        <a href="db_manager.php" class="btn btn-secondary">Volver</a>

                    <?php else: ?>

                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="alert alert-info mb-0">Filas leídas<br><strong><?= $resultado['total_filas_archivo'] ?></strong></div>
                            </div>
                            <div class="col-md-3">
                                <div class="alert alert-warning mb-0">Con cambios reales<br><strong><?= $resultado['filas_con_cambios'] ?></strong></div>
                            </div>
                            <div class="col-md-3">
                                <div class="alert alert-success mb-0">Sin cambios (idénticas)<br><strong><?= $resultado['filas_sin_cambios'] ?></strong></div>
                            </div>
                            <div class="col-md-3">
                                <div class="alert alert-secondary mb-0">No encontradas en BD<br><strong><?= count($resultado['filas_no_encontradas']) ?></strong></div>
                            </div>
                        </div>

                        <?php if (!empty($resultado['columnas_ignoradas'])): ?>
                            <div class="alert alert-light border">
                                Columnas del archivo ignoradas (no existen en la tabla):
                                <?= htmlspecialchars(implode(', ', $resultado['columnas_ignoradas'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($resultado['filas_no_encontradas'])): ?>
                            <div class="alert alert-light border">
                                Registros omitidos por no existir en la base de datos (no se insertan registros nuevos):
                                <?= htmlspecialchars(implode(', ', $resultado['filas_no_encontradas'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($resultado['cambios'])): ?>
                            <div class="alert alert-success">No hay cambios reales que aplicar. La base de datos ya está actualizada.</div>
                            <a href="db_manager.php" class="btn btn-secondary">Volver</a>

                        <?php else: ?>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Llave primaria</th>
                                            <th>Columna</th>
                                            <th>Valor actual (BD)</th>
                                            <th>Valor nuevo (archivo)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resultado['cambios'] as $filaCambio): ?>
                                            <?php $pkTexto = implode(' / ', $filaCambio['pk']); ?>
                                            <?php foreach ($filaCambio['cambios'] as $columna => $valores): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($pkTexto) ?></td>
                                                    <td><?= htmlspecialchars($columna) ?></td>
                                                    <td class="text-danger"><?= htmlspecialchars((string) $valores['antes']) ?></td>
                                                    <td class="text-success"><?= htmlspecialchars((string) $valores['despues']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <form method="post" action="import_csv.php" class="mt-3">
                                <input type="hidden" name="tabla" value="<?= htmlspecialchars($resultado['tabla']) ?>">
                                <input type="hidden" name="token" value="<?= htmlspecialchars($resultado['token']) ?>">
                                <button type="submit" class="btn btn-primary"
                                    onclick="return confirm('¿Confirmar la actualización de <?= (int) $resultado['filas_con_cambios'] ?> registro(s)? Se generará un respaldo automático antes de aplicar los cambios.');">
                                    Confirmar importación (<?= $resultado['filas_con_cambios'] ?> registros)
                                </button>
                                <a href="db_manager.php" class="btn btn-secondary">Cancelar</a>
                            </form>

                        <?php endif; ?>

                    <?php endif; ?>

                </div>
            </div>
        </section>
    </div>
</div>

<script src="assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
