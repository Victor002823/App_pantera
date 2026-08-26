<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

// 1. Incluimos el archivo de la clase de conexión (ajusta el nombre si tu archivo se llama diferente)
require_once __DIR__ . '/../config/db.php';

try {
    // 2. Instanciamos la clase db
    $conexionDB = new db();
    
    // 3. Ejecutamos el método conexion() para obtener el objeto PDO
    $pdo = $conexionDB->conexion();

    // Verificamos si la conexión devolvió un mensaje de error (string) en lugar de un objeto PDO
    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

    // 4. Traemos todo de la tabla (igual que antes)
    $stmt = $pdo->query("SELECT * FROM servicios ORDER BY id DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formateamos los datos para evitar que valores NULL rompan el JSON
    $limpiarDatos = array_map(function($row) {
        $row['telefono'] = $row['telefono'] ?? '';
        return $row;
    }, $data);

    // Devolvemos la respuesta exitosa en JSON
    echo json_encode(['success' => true, 'data' => $limpiarDatos], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);

} catch (Exception $e) {
    // Si hay algún fallo, lo capturamos y lo mostramos en formato JSON
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
