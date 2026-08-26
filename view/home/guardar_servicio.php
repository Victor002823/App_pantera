<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

// 1. Incluimos el archivo de conexión centralizado
require_once(__DIR__ . "/../../config/db.php");

// Establecer zona horaria en PHP a CDMX
date_default_timezone_set('America/Mexico_City');
$fecha = date('Y-m-d H:i:s'); // Fecha y hora actual de CDMX

// Obtener datos del formulario
$nombre           = $_POST['nombre'] ?? '';
$tipo_servicio    = $_POST['tipo-servicio'] ?? '';
$tipo_inmueble    = $_POST['tipo-inmueble'] ?? '';
$destino          = $_POST['destino'] ?? '';
$inventario       = $_POST['inventario'] ?? '';
$direccion_origen = $_POST['direccion-origen'] ?? '';
$direccion_destino= $_POST['direccion-destino'] ?? '';
$tipo_camioneta   = $_POST['tipo-camioneta'] ?? '';
$cargadores       = $_POST['cargadores'] ?? 0;
$maniobra         = $_POST['maniobra'] ?? 0;
$total            = $_POST['totales'] ?? 0;

try {
    // 2. Instanciamos la clase y obtenemos el objeto PDO
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();

    // Verificamos si la conexión falló (si devolvió un string de error)
    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

    // 3. Preparar y ejecutar INSERT
    $sql = "INSERT INTO servicios 
    (nombre_cliente, tipo_servicio, inmueble, destino, direccion_origen, direccion_destino, tipo_camioneta, inventario, cargadores, maniobra, total, fecha_creacion)
    VALUES 
    (:nombre, :tipo_servicio, :tipo_inmueble, :destino, :direccion_origen, :direccion_destino, :tipo_camioneta, :inventario, :cargadores, :maniobra, :total, :fecha)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre'           => $nombre,
        ':tipo_servicio'    => $tipo_servicio,
        ':tipo_inmueble'    => $tipo_inmueble,
        ':destino'          => $destino,
        ':direccion_origen' => $direccion_origen,
        ':direccion_destino'=> $direccion_destino,
        ':tipo_camioneta'   => $tipo_camioneta,
        ':inventario'       => $inventario,
        ':cargadores'       => $cargadores,
        ':maniobra'         => $maniobra,
        ':total'            => $total,
        ':fecha'            => $fecha
    ]);

    $id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'id' => $id, 
        'fecha' => $fecha
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
