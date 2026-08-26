<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

// 1. Incluimos el archivo de conexión centralizado
require_once __DIR__ . '/../config/db.php';

// Recibimos el ID a eliminar por POST
$id = $_POST['id'] ?? 0;

try {
    // 2. Instanciamos la clase y obtenemos el objeto PDO
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();

    // Verificamos si la conexión devolvió un mensaje de error
    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

    // 3. Preparamos y ejecutamos la sentencia de eliminación
    $stmt = $pdo->prepare("DELETE FROM servicios WHERE id = ?");
    $stmt->execute([$id]);

    // 4. Verificamos si realmente se eliminó una fila
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "No se encontró ningún registro con el ID especificado o ya fue eliminado."
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    // Capturamos cualquier fallo de conexión o de la consulta SQL
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
