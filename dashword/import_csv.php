<?php
/**
 * import_csv.php
 * Control El Lince — Administrador de Base de Datos
 *
 * Aplica los cambios generados por preview_csv.php (nunca datos crudos del
 * usuario). Genera un respaldo automático antes de modificar la tabla.
 * Solo ejecuta UPDATE sobre columnas que realmente cambiaron.
 * Nunca inserta ni elimina registros.
 */

declare(strict_types=1);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/db_manager_helpers.php';

const DB_MANAGER_BACKUPS_DIR = __DIR__.'/backups';

$db  = new db();
$pdo = $db->conexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = null;
$resumen = null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: db_manager.php');
    exit;
}

try {
    $tabla = trim((string) ($_POST['tabla'] ?? ''));
    $token = trim((string) ($_POST['token'] ?? ''));

    if ($tabla === '' || !db_validate_table($pdo, $tabla)) {
        throw new RuntimeException('La tabla indicada no existe.');
    }

    if ($token === '' || !preg_match('/^[a-f0-9]{32}$/', $token)) {
        throw new RuntimeException('Token de vista previa inválido.');
    }

    if (($_SESSION['db_manager_preview_token'] ?? null) !== $token) {
        throw new RuntimeException('La vista previa expiró o no corresponde a esta sesión. Vuelve a subir el archivo.');
    }

    $tmpDir = db_tmp_dir(DB_MANAGER_BACKUPS_DIR);
    $rutaJson = $tmpDir.'/preview_'.$token.'.json';

    if (!is_file($rutaJson)) {
        throw new RuntimeException('El archivo de vista previa no se encontró. Vuelve a subir el archivo.');
    }

    $datos = json_decode((string) file_get_contents($rutaJson), true, 512, JSON_THROW_ON_ERROR);

    if (($datos['tabla'] ?? null) !== $tabla) {
        throw new RuntimeException('La vista previa no corresponde a la tabla indicada.');
    }

    $llavePrimaria = $datos['llave_primaria'] ?? [];
    $cambios = $datos['cambios'] ?? [];
    $columnasTabla = db_get_table_columns($pdo, $tabla);

    if (empty($cambios)) {
        throw new RuntimeException('No hay cambios pendientes por aplicar.');
    }

    /* ------------------------------------------------------------ */
    /*  RESPALDO AUTOMÁTICO ANTES DE MODIFICAR                       */
    /* ------------------------------------------------------------ */

    $rutaRespaldo = db_backup_table($pdo, $tabla, DB_MANAGER_BACKUPS_DIR, 'csv');

    /* ------------------------------------------------------------ */
    /*  APLICAR SOLO UPDATE, SOLO EN COLUMNAS QUE CAMBIARON           */
    /* ------------------------------------------------------------ */

    $pdo->beginTransaction();

    $filasActualizadas = 0;
    $columnasActualizadas = 0;
    $errores = [];

    foreach ($cambios as $filaCambio) {
        $pk = $filaCambio['pk'] ?? [];
        $cols = $filaCambio['cambios'] ?? [];

        if (empty($pk) || empty($cols)) {
            continue;
        }

        // Whitelist estricta: solo columnas reales de la tabla, y nunca la PK
        $setPartes = [];
        $valores = [];
        foreach ($cols as $columna => $valorPar) {
            if (!in_array($columna, $columnasTabla, true) || in_array($columna, $llavePrimaria, true)) {
                continue;
            }
            $setPartes[] = "\"$columna\" = ?";
            $valores[] = $valorPar['despues'] ?? '';
        }

        if (empty($setPartes)) {
            continue;
        }

        $condicionPk = [];
        foreach ($llavePrimaria as $colPk) {
            if (!array_key_exists($colPk, $pk)) {
                continue 2; // fila sin llave primaria completa, se omite
            }
            $condicionPk[] = "\"$colPk\" = ?";
            $valores[] = $pk[$colPk];
        }

        $sql = "UPDATE \"$tabla\" SET ".implode(", ", $setPartes)." WHERE ".implode(" AND ", $condicionPk);

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($valores);
            if ($stmt->rowCount() > 0) {
                $filasActualizadas++;
                $columnasActualizadas += count($setPartes);
            }
        } catch (Throwable $e) {
            $errores[] = implode(' / ', $pk).': '.$e->getMessage();
        }
    }

    if (!empty($errores)) {
        $pdo->rollBack();
        throw new RuntimeException('Se revirtieron los cambios por errores: '.implode('; ', $errores));
    }

    $pdo->commit();

    // Limpiar el archivo temporal de vista previa (ya aplicado)
    @unlink($rutaJson);
    unset($_SESSION['db_manager_preview_token']);

    $resumen = [
        'tabla' => $tabla,
        'filas_actualizadas' => $filasActualizadas,
        'columnas_actualizadas' => $columnasActualizadas,
        'respaldo' => basename($rutaRespaldo),
    ];

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Importación completada - Control El Lince</title>
<?php include __DIR__.'/nav.php'; ?>
</head>
<body>
<div id="main">
    <div class="page-heading">
        <h3>Resultado de la importación</h3>
    </div>

    <div class="page-content">
        <section class="section">
            <div class="card">
                <div class="card-body">

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <strong>No se aplicaron cambios:</strong> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            Importación completada correctamente sobre la tabla
                            <strong><?= htmlspecialchars($resumen['tabla']) ?></strong>.
                        </div>
                        <ul>
                            <li>Registros actualizados: <strong><?= $resumen['filas_actualizadas'] ?></strong></li>
                            <li>Columnas modificadas en total: <strong><?= $resumen['columnas_actualizadas'] ?></strong></li>
                            <li>Respaldo generado antes de aplicar cambios: <code><?= htmlspecialchars($resumen['respaldo']) ?></code></li>
                        </ul>
                    <?php endif; ?>

                    <a href="db_manager.php" class="btn btn-primary mt-3">Volver al administrador</a>
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
