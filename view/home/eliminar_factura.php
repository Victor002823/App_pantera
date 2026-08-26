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

    // Recibir ID y validar como entero
    $id = trim($_POST['id'] ?? '');
    $id = filter_var($id, FILTER_VALIDATE_INT);

    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. Preparamos y ejecutamos DELETE usando PDO
    $stmt = $pdo->prepare("DELETE FROM facturaciones WHERE id = ?");
    $stmt->execute([$id]);

    // 4. Verificamos si realmente se eliminó el registro
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se encontró el registro con ese ID'], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
