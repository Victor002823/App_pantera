<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

// 1. Incluimos el archivo de conexión centralizado
require_once __DIR__ . '/../config/db.php';

// Recibimos los parámetros por POST
$id             = $_POST['id'] ?? 0;
$nombre_cliente = $_POST['nombre_cliente'] ?? '';
$telefono       = $_POST['telefono'] ?? '';
$maniobra       = $_POST['maniobra'] ?? 0;
$total          = $_POST['total'] ?? 0;

try {
    // 2. Instanciamos la clase y obtenemos el objeto PDO
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();

    // Verificamos si la conexión falló (si devolvió un mensaje de error en string)
    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

    // 3. Preparamos la consulta SQL de actualización
    $stmt = $pdo->prepare("
        UPDATE servicios 
        SET nombre_cliente = ?, 
            telefono = ?, 
            maniobra = ?, 
            total = ? 
        WHERE id = ?
    ");

    // 4. Ejecutamos pasando los parámetros en orden
    $stmt->execute([
        $nombre_cliente,
        $telefono,
        $maniobra,
        $total,
        $id
    ]);

    // Respondemos con éxito
    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Capturamos tanto errores de conexión como errores en la consulta SQL
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
