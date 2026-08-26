<?php



date_default_timezone_set('America/Mexico_City');

$logDir = __DIR__ . '/logs';
$logFile = $logDir . '/backup.log';


if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}


function escribirLog($mensaje)
{
    global $logFile;

    file_put_contents(
        $logFile,
        "[" . date("Y-m-d H:i:s") . "] " . $mensaje . PHP_EOL,
        FILE_APPEND
    );
}


escribirLog("=================================");
escribirLog("Backup iniciado");

echo "Backup iniciado\n";


// ===============================
// GENERAR BASE DE DATOS
// ===============================

escribirLog("Generando base de datos...");

include __DIR__ . '/backup_db.php';


if (!file_exists(__DIR__ . '/database.sql')) {

    escribirLog("Error generando base de datos");
    echo "Error generando base de datos\n";
    exit;

}


escribirLog("Base de datos generada correctamente");

echo "Base de datos generada\n";



// ===============================
// CREAR ZIP
// ===============================

escribirLog("Comprimiendo sistema...");


$fecha = date("Y-m-d_His");

$nombreZip = "backup_lince_$fecha.zip";


$carpetaBackup = __DIR__ . "/backups/diarios";


if (!is_dir($carpetaBackup)) {

    mkdir($carpetaBackup, 0755, true);

}


$rutaZip = $carpetaBackup . "/" . $nombreZip;



$zip = new ZipArchive();


$resultado = $zip->open(
    $rutaZip,
    ZipArchive::CREATE
);



if ($resultado !== TRUE) {

    escribirLog("Error ZIP codigo: " . $resultado);

    echo "Error creando ZIP codigo: $resultado\n";

    exit;

}



$raiz = realpath(__DIR__);



$archivos = new RecursiveIteratorIterator(

    new RecursiveDirectoryIterator(
        $raiz,
        FilesystemIterator::SKIP_DOTS
    ),

    RecursiveIteratorIterator::LEAVES_ONLY

);



foreach ($archivos as $archivo) {


    if ($archivo->isDir()) {

        continue;

    }


    $rutaReal = $archivo->getRealPath();


    $rutaRelativa = substr(
        $rutaReal,
        strlen($raiz) + 1
    );


    $excluir = [

        'backups/',
        'logs/',
        '.git/',
        'ngrok-v3-stable-linux-arm64.zip',
        'test.txt'

    ];


    $saltar = false;


    foreach ($excluir as $item) {


        if (strpos($rutaRelativa, $item) === 0) {

            $saltar = true;
            break;

        }

    }


    if ($saltar) {

        continue;

    }


    $zip->addFile(
        $rutaReal,
        $rutaRelativa
    );


}



$zip->close();



if (!file_exists($rutaZip)) {


    escribirLog("Error creando archivo ZIP");

    echo "Error creando ZIP\n";

    exit;


}


escribirLog("ZIP creado: " . $nombreZip);

echo "ZIP creado correctamente:\n";

echo $rutaZip . "\n";



// ===============================
// SUBIR A CLOUDFLARE R2
// ===============================


escribirLog("Subiendo backup a Cloudflare R2...");

echo "Subiendo backup a R2...\n";


try {


    require_once __DIR__ . '/lib/R2Uploader.php';


    $configR2 = require __DIR__ . '/config/r2.php';


    $r2 = new R2Uploader($configR2);



    $r2->upload(

        $rutaZip,

        "diarios/" . $nombreZip

    );



    escribirLog("Backup enviado correctamente a R2");

    echo "Backup enviado correctamente a R2\n";



} catch (Exception $e) {


    escribirLog(
        "Error subiendo a R2: " . $e->getMessage()
    );


    echo "Error R2: " . $e->getMessage() . "\n";


    exit;


}



// ===============================
// LIMPIAR BACKUPS LOCALES ANTIGUOS
// ===============================


$archivos = glob(
    __DIR__ . "/backups/diarios/*.zip"
);



if (count($archivos) > 7) {


    usort($archivos, function($a, $b){

        return filemtime($a) - filemtime($b);

    });



    $cantidadEliminar = count($archivos) - 7;



    for ($i = 0; $i < $cantidadEliminar; $i++) {


        if (unlink($archivos[$i])) {


            escribirLog(
                "Backup local eliminado: " .
                basename($archivos[$i])
            );


        }


    }


}



// ===============================
// FINAL
// ===============================


escribirLog("Proceso terminado OK");


echo "Proceso terminado OK\n";
