<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

// 1. Incluimos el archivo de conexión centralizado
require_once(__DIR__ . "/../../config/db.php");

try {
    // 2. Instanciamos la clase y obtenemos el objeto PDO
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();

    // Verificamos si la conexión falló (si devolvió un string de error)
    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

    // Recibir y validar datos básicos
    $id = $_POST['id'] ?? '';
    if (!$id || !is_numeric($id)) {
        echo json_encode(['success' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Asignación de variables
    $tipo_servicio     = $_POST['tipo_servicio'] ?? '';
    $inmueble          = $_POST['inmueble'] ?? '';
    $destino           = $_POST['destino'] ?? '';
    $direccion_origen  = $_POST['direccion_origen'] ?? '';
    $direccion_destino = $_POST['direccion_destino'] ?? '';
    $tipo_camioneta    = $_POST['tipo_camioneta'] ?? '';
    $inventario        = trim($_POST['inventario'] ?? '');
    $cargadores        = $_POST['cargadores'] ?? '';
    $maniobra          = $_POST['maniobra'] ?? 0;
    $total             = $_POST['total'] ?? 0;

    // 3. Preparamos la consulta UPDATE utilizando PDO (compatible con db.php)
    $stmt = $pdo->prepare("UPDATE servicios SET 
        tipo_servicio = ?, 
        inmueble = ?, 
        destino = ?, 
        direccion_origen = ?, 
        direccion_destino = ?, 
        tipo_camioneta = ?, 
        inventario = ?, 
        cargadores = ?, 
        maniobra = ?, 
        total = ? 
        WHERE id = ?");

    // 4. Ejecutamos pasando los parámetros en orden (PDO maneja los tipos automáticamente)
    $stmt->execute([
        $tipo_servicio, 
        $inmueble, 
        $destino,
        $direccion_origen, 
        $direccion_destino, 
        $tipo_camioneta,
        $inventario, 
        $cargadores, 
        $maniobra, 
        $total, 
        $id
    ]);

    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
