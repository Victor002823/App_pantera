<?php
/**
 * export_csv.php
 * Control El Lince — Administrador de Base de Datos
 *
 * Exporta una tabla completa como CSV (UTF-8 BOM, comportamiento original)
 * o como XLSX (nuevo). Siempre guarda una copia de respaldo en
 * backups/TABLA/ antes de enviar la descarga al navegador.
 *
 * Uso: export_csv.php?tabla=NOMBRE&formato=csv   (por defecto)
 *      export_csv.php?tabla=NOMBRE&formato=xlsx
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

$tabla = trim((string) ($_GET['tabla'] ?? ''));
$formato = strtolower(trim((string) ($_GET['formato'] ?? 'csv')));

if ($tabla === '' || !db_validate_table($pdo, $tabla)) {
    http_response_code(400);
    echo 'Tabla inválida.';
    exit;
}

if (!in_array($formato, ['csv', 'xlsx'], true)) {
    $formato = 'csv';
}

// El respaldo se genera en el mismo formato solicitado, de modo que
// backups/TABLA/ acumula copias en ambos formatos según cómo se exporte.
$rutaRespaldo = db_backup_table($pdo, $tabla, DB_MANAGER_BACKUPS_DIR, $formato);
$nombreDescarga = basename($rutaRespaldo);

$mime = $formato === 'xlsx'
    ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    : 'text/csv; charset=UTF-8';

header('Content-Type: '.$mime);
header('Content-Disposition: attachment; filename="'.$nombreDescarga.'"');
header('Content-Length: '.filesize($rutaRespaldo));
readfile($rutaRespaldo);
exit;
