<?php
/**
 * db_manager_helpers.php
 * Funciones compartidas del módulo de administración de base de datos.
 * Control El Lince
 *
 * Este archivo NO genera salida. Solo define funciones utilizadas por
 * db_manager.php, export_csv.php, preview_csv.php e import_csv.php.
 */

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/* -------------------------------------------------------------------- */
/*  CORRECCIÓN DE UTF-8 / DOBLE CODIFICACIÓN (sin utf8_decode)          */
/* -------------------------------------------------------------------- */

/**
 * Corrige texto con codificación dañada, incluyendo doble codificación
 * UTF-8 (ej. "GalerÃ­as" -> "Galerías", "GalerÃÂ­as" -> "Galerías").
 *
 * No usa utf8_decode() (deprecated desde PHP 8.5). Usa mb_check_encoding,
 * mb_convert_encoding e iconv, aplicando correcciones solo cuando hay
 * evidencia real de mojibake, para no dañar texto ya correcto.
 */
function db_fix_encoding(?string $value): ?string
{
    if ($value === null || $value === '') {
        return $value;
    }

    $max = 3; // rondas máximas (cubre incluso doble-doble codificación)
    while ($max-- > 0) {

        // Caso 1: la cadena ni siquiera es UTF-8 válido -> asumir Windows-1252
        if (!mb_check_encoding($value, 'UTF-8')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
            if ($converted !== false) {
                $value = $converted;
                continue;
            }
            break;
        }

        // Caso 2: es UTF-8 válido, pero puede estar doblemente codificado.
        // Marcador típico: secuencias "Ã" o "Â" seguidas de un byte de
        // continuación (\x80-\xBF interpretado como carácter compuesto).
        if (!preg_match('/[ÃÂ][\x{0080}-\x{00BF}]/u', $value)) {
            break; // sin evidencia de doble codificación, no tocar
        }

        // Reinterpretar los bytes UTF-8 actuales como si fueran ISO-8859-1
        // y volver a intentar decodificarlos como UTF-8 (revierte la doble
        // codificación).
        $candidate = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $value);
        if ($candidate === false || $candidate === '' || !mb_check_encoding($candidate, 'UTF-8')) {
            break;
        }

        // No aceptar el resultado si introduce caracteres de control
        // (indicaría que no era realmente doble codificación).
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $candidate)) {
            break;
        }

        $value = $candidate;
    }

    return $value;
}

/**
 * Devuelve la celda en (columna, fila) usando coordenadas tipo "B3".
 * PhpSpreadsheet 5.x eliminó getCellByColumnAndRow(), así que se
 * construye la coordenada manualmente con Coordinate::stringFromColumnIndex().
 */
function db_get_cell(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $hoja, int $col, int $row): Cell
{
    $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    return $hoja->getCell($letra.$row);
}

/**
 * Asigna un valor en (columna, fila). Reemplaza setCellValueByColumnAndRow(),
 * también eliminado en PhpSpreadsheet 5.x.
 */
function db_set_cell(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $hoja, int $col, int $row, $valor): void
{
    $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $hoja->setCellValue($letra.$row, $valor);
}

/* -------------------------------------------------------------------- */
/*  LECTURA DE CELDAS (fechas / horas de Excel)                         */
/* -------------------------------------------------------------------- */

/**
 * Lee el valor real de una celda de PhpSpreadsheet, detectando fechas y
 * horas de Excel y devolviendo el tipo de dato correcto como string.
 *
 * IMPORTANTE: nunca usar toArray() para leer fechas, porque las convierte
 * de vuelta a número serial. Aquí se lee celda por celda con getCell().
 */
function db_read_cell_value(Cell $cell): string
{
    $format = $cell->getWorksheet()
        ->getStyle($cell->getCoordinate())
        ->getNumberFormat()
        ->getFormatCode();

    $raw = $cell->getValue();

    if ($raw === null || $raw === '') {
        return '';
    }

    // Fecha u hora almacenada como número serial de Excel
    if (is_numeric($raw) && ExcelDate::isDateTimeFormatCode($format)) {
        $dt = ExcelDate::excelToDateTimeObject($raw);

        $formatoMin = strtolower($format);
        $tieneFecha = (bool) preg_match('/[ymd]/', $formatoMin);
        $tieneHora  = (bool) preg_match('/[hs]/', $formatoMin);

        if ($tieneFecha && $tieneHora) {
            return $dt->format('Y-m-d H:i:s');
        }
        if ($tieneHora && !$tieneFecha) {
            return $dt->format('H:i:s');
        }
        return $dt->format('Y-m-d');
    }

    if ($cell->getDataType() === DataType::TYPE_STRING) {
        return db_fix_encoding((string) $raw) ?? '';
    }

    return db_fix_encoding((string) $raw) ?? '';
}

/* -------------------------------------------------------------------- */
/*  COMPARACIÓN DE VALORES (normaliza números: 0 == 0.0 == "0.00")      */
/* -------------------------------------------------------------------- */

function db_normalize_value(?string $value): string
{
    if ($value === null) {
        return '';
    }
    $trim = trim($value);

    if ($trim === '') {
        return '';
    }

    if (is_numeric($trim)) {
        $float = (float) $trim;
        if ($float == (int) $float) {
            return (string) (int) $float;
        }
        return rtrim(rtrim(sprintf('%.6f', $float), '0'), '.');
    }

    return $trim;
}

/**
 * Compara dos valores (BD vs archivo) de forma tolerante a formato numérico
 * y espacios en blanco. Devuelve true si deben considerarse iguales.
 */
function db_values_equal(?string $a, ?string $b): bool
{
    return db_normalize_value($a) === db_normalize_value($b);
}

/* -------------------------------------------------------------------- */
/*  METADATOS DE TABLA                                                  */
/* -------------------------------------------------------------------- */

/** Devuelve la lista de nombres de tablas existentes (para validar entradas). */
function db_get_table_names(PDO $pdo): array
{
    return $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
}

/** Valida que un nombre de tabla exista realmente en la BD (previene inyección). */
function db_validate_table(PDO $pdo, string $table): bool
{
    return in_array($table, db_get_table_names($pdo), true);
}

/** Devuelve los nombres de columna reales de una tabla (whitelist para UPDATE). */
function db_get_table_columns(PDO $pdo, string $table): array
{
    $stmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position");
    $stmt->execute([$table]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Obtiene la llave primaria (simple o compuesta) de una tabla.
 * @return string[] Nombres de columnas que forman la llave primaria.
 */
function db_get_primary_key(PDO $pdo, string $table): array
{
    $stmt = $pdo->prepare("SELECT kcu.column_name FROM information_schema.table_constraints tc JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name WHERE tc.table_name = ? AND tc.constraint_type = 'PRIMARY KEY'");
    $stmt->execute([$table]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array_map(static fn($r) => $r["column_name"], $rows);
}

/* -------------------------------------------------------------------- */
/*  RESPALDOS                                                            */
/* -------------------------------------------------------------------- */

/** Devuelve (y crea si hace falta) la carpeta de respaldos de una tabla. */
function db_backup_dir_for_table(string $table, string $backupsDir): string
{
    $dir = rtrim($backupsDir, '/').'/'.$table;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

/**
 * Genera un respaldo CSV (UTF-8 con BOM) de la tabla completa.
 */
function db_backup_table_csv(PDO $pdo, string $table, string $backupsDir): string
{
    $dir = db_backup_dir_for_table($table, $backupsDir);
    $path = $dir.'/'.$table.'_'.date('Y-m-d_H-i-s').'.csv';

    $stmt = $pdo->query('SELECT * FROM "'.$table.'"');
    $fh = fopen($path, 'w');
    fwrite($fh, "\xEF\xBB\xBF"); // BOM UTF-8

    $first = true;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($first) {
            fputcsv($fh, array_keys($row));
            $first = false;
        }
        fputcsv($fh, $row);
    }
    if ($first) {
        fputcsv($fh, db_get_table_columns($pdo, $table));
    }
    fclose($fh);

    return $path;
}

/**
 * Genera un respaldo XLSX de la tabla completa usando PhpSpreadsheet.
 * El autoload de PhpSpreadsheet debe estar cargado por quien llama a esta función.
 */
function db_backup_table_xlsx(PDO $pdo, string $table, string $backupsDir): string
{
    $dir = db_backup_dir_for_table($table, $backupsDir);
    $path = $dir.'/'.$table.'_'.date('Y-m-d_H-i-s').'.xlsx';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $hoja = $spreadsheet->getActiveSheet();
    $hoja->setTitle(substr($table, 0, 31));

    $stmt = $pdo->query('SELECT * FROM "'.$table.'"');
    $fila = 1;
    $columnas = null;

    while ($registro = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($columnas === null) {
            $columnas = array_keys($registro);
            $col = 1;
            foreach ($columnas as $nombreCol) {
                db_set_cell($hoja, $col, 1, $nombreCol);
                $col++;
            }
            $fila = 2;
        }
        $col = 1;
        foreach ($columnas as $nombreCol) {
            db_set_cell($hoja, $col, $fila, $registro[$nombreCol]);
            $col++;
        }
        $fila++;
    }

    if ($columnas === null) {
        $columnas = db_get_table_columns($pdo, $table);
        $col = 1;
        foreach ($columnas as $nombreCol) {
            db_set_cell($hoja, $col, 1, $nombreCol);
            $col++;
        }
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($path);

    return $path;
}

/**
 * Genera un respaldo en el formato indicado ('csv' o 'xlsx').
 * Usada antes de cualquier UPDATE (import_csv.php) y antes de cada
 * exportación (export_csv.php), para que backups/TABLA/ conserve
 * copias en ambos formatos según cómo se haya usado el módulo.
 */
function db_backup_table(PDO $pdo, string $table, string $backupsDir, string $formato = 'csv'): string
{
    return $formato === 'xlsx'
        ? db_backup_table_xlsx($pdo, $table, $backupsDir)
        : db_backup_table_csv($pdo, $table, $backupsDir);
}

/* -------------------------------------------------------------------- */
/*  LECTURA GENÉRICA DE ARCHIVOS (CSV o XLSX) CON PhpSpreadsheet         */
/* -------------------------------------------------------------------- */

/**
 * Detecta si un CSV usa coma o punto y coma como delimitador, inspeccionando
 * la primera línea del archivo.
 */
function db_detectar_delimitador(string $ruta): string
{
    $fh = fopen($ruta, 'r');
    $primeraLinea = fgets($fh) ?: '';
    fclose($fh);

    $primeraLinea = preg_replace('/^\xEF\xBB\xBF/', '', $primeraLinea);

    $comas = substr_count($primeraLinea, ',');
    $puntoComa = substr_count($primeraLinea, ';');

    return $puntoComa > $comas ? ';' : ',';
}

/**
 * Carga un archivo CSV o XLSX y devuelve encabezados + filas ya
 * normalizadas (fechas/horas de Excel resueltas, UTF-8 corregido).
 * El autoload de PhpSpreadsheet debe estar cargado por quien llama.
 *
 * @return array{encabezados: array<int,string>, filas: array<int,array<string,string>>}
 */
function db_load_spreadsheet_rows(string $ruta, string $extension): array
{
    if ($extension === 'csv') {
        $delimitador = db_detectar_delimitador($ruta);
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        $reader->setDelimiter($delimitador);
        $reader->setInputEncoding('UTF-8');
        $reader->setEscapeCharacter('');
        $spreadsheet = $reader->load($ruta);
    } else {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($ruta);
    }

    $hoja = $spreadsheet->getActiveSheet();
    $filasTotales = $hoja->getHighestDataRow();
    $columnaMax = $hoja->getHighestDataColumn();
    $indiceColumnaMax = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnaMax);

    $encabezados = [];
    for ($col = 1; $col <= $indiceColumnaMax; $col++) {
        $celda = db_get_cell($hoja, $col, 1);
        $nombre = trim(db_fix_encoding((string) $celda->getValue()) ?? '');
        if ($nombre !== '') {
            $encabezados[$col] = $nombre;
        }
    }

    $filas = [];
    for ($fila = 2; $fila <= $filasTotales; $fila++) {
        $registro = [];
        $vacia = true;
        foreach ($encabezados as $col => $nombreCol) {
            $celda = db_get_cell($hoja, $col, $fila);
            $valor = db_read_cell_value($celda);
            if ($valor !== '') {
                $vacia = false;
            }
            $registro[$nombreCol] = $valor;
        }
        if (!$vacia) {
            $filas[] = $registro;
        }
    }

    return ['encabezados' => array_values($encabezados), 'filas' => $filas];
}

/* -------------------------------------------------------------------- */
/*  HISTORIAL DE RESPALDOS (db_history.php)                             */
/* -------------------------------------------------------------------- */

/** Formatea un tamaño en bytes a texto legible (KB, MB...). */
function db_human_filesize(int $bytes): string
{
    $unidades = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $valor = (float) $bytes;
    while ($valor >= 1024 && $i < count($unidades) - 1) {
        $valor /= 1024;
        $i++;
    }
    return round($valor, $i === 0 ? 0 : 1).' '.$unidades[$i];
}

/**
 * Lista todos los respaldos existentes en backups/, agrupados por tabla,
 * ordenados del más reciente al más antiguo.
 *
 * @return array<int,array{tabla:string, archivo:string, ruta:string, formato:string, fecha:string, tamano:string}>
 */
function db_list_backups(string $backupsDir): array
{
    $backupsDir = rtrim($backupsDir, '/');
    $resultado = [];

    if (!is_dir($backupsDir)) {
        return $resultado;
    }

    foreach (scandir($backupsDir) as $carpeta) {
        if ($carpeta === '.' || $carpeta === '..' || $carpeta === 'tmp') {
            continue;
        }
        $rutaCarpeta = $backupsDir.'/'.$carpeta;
        if (!is_dir($rutaCarpeta)) {
            continue;
        }

        foreach (scandir($rutaCarpeta) as $archivo) {
            if ($archivo === '.' || $archivo === '..') {
                continue;
            }
            $rutaArchivo = $rutaCarpeta.'/'.$archivo;
            if (!is_file($rutaArchivo)) {
                continue;
            }

            $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
            if (!in_array($extension, ['csv', 'xlsx'], true)) {
                continue;
            }

            // Extraer fecha del nombre: tabla_YYYY-MM-DD_HH-MM-SS.ext
            $fechaTexto = '';
            if (preg_match('/_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\./', $archivo, $m)) {
                $fechaTexto = str_replace('_', ' ', $m[1]);
                $fechaTexto = preg_replace('/(\d{2})-(\d{2})-(\d{2})$/', '$1:$2:$3', $fechaTexto);
            } else {
                $fechaTexto = date('Y-m-d H:i:s', filemtime($rutaArchivo));
            }

            $resultado[] = [
                'tabla'   => $carpeta,
                'archivo' => $archivo,
                'ruta'    => $rutaArchivo,
                'formato' => $extension,
                'fecha'   => $fechaTexto,
                'tamano'  => db_human_filesize(filesize($rutaArchivo)),
            ];
        }
    }

    usort($resultado, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));

    return $resultado;
}

/* -------------------------------------------------------------------- */
/*  DIRECTORIO TEMPORAL PARA VISTAS PREVIAS                              */
/* -------------------------------------------------------------------- */

function db_tmp_dir(string $backupsDir): string
{
    $dir = rtrim($backupsDir, '/').'/tmp';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}
