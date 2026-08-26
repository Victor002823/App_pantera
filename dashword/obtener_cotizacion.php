<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

// 1. Incluimos el archivo de conexión centralizado
require_once __DIR__ . '/../config/db.php';

// Recibimos el ID por GET (ej: ?id=5)
$id = $_GET['id'] ?? 0;

try {
    // 2. Instanciamos la clase y obtenemos el objeto PDO
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();

    // Verificamos si la conexión falló (si devolvió un string de error)
    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

    // 3. Preparamos y ejecutamos la consulta para buscar el registro único
    $stmt = $pdo->prepare("SELECT * FROM servicios WHERE id = ?");
    $stmt->execute([$id]);

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Si encuentra el registro, nos aseguramos de que el campo 'telefono' no sea null
        $data['telefono'] = $data['telefono'] ?? '';

        echo json_encode([
            "success" => true,
            "data" => $data
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Si el ID no existe en la base de datos
        echo json_encode([
            "success" => false,
            "error" => "No se encontró la cotización con el ID especificado."
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    // Capturamos cualquier error de conexión o de la consulta
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
