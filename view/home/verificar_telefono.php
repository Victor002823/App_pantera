<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

// Incluimos el archivo de conexión centralizado
require_once(__DIR__ . "/../../config/db.php");

try {
    // Instanciamos la clase y obtenemos el objeto PDO
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();

    // Verificamos si la conexión falló (si devolvió un string de error)
    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

    // Configuramos el modo de fetch por defecto para simplificar los fetches
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $metodo = $_SERVER['REQUEST_METHOD'];

    // ACCIÓN 1: VERIFICAR SI EXISTE EL TELÉFONO
    if ($metodo === 'GET') {
        $cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';

        if (empty($cliente)) {
            echo json_encode(['success' => false, 'error' => 'Falta el nombre del cliente']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT telefono FROM servicios WHERE nombre_cliente = ? AND telefono IS NOT NULL AND telefono != '' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$cliente]);
        $resultado = $stmt->fetch();

        if ($resultado && !empty(trim($resultado['telefono']))) {
            echo json_encode(['success' => true, 'tiene_telefono' => true, 'telefono' => trim($resultado['telefono'])]);
        } else {
            echo json_encode(['success' => true, 'tiene_telefono' => false]);
        }
        exit;
    }

    // ACCIÓN 2: GUARDAR EL TELÉFONO SI EL USUARIO LO INGRESA
    if ($metodo === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $cliente = isset($input['cliente']) ? trim($input['cliente']) : '';
        $telefono = isset($input['telefono']) ? trim($input['telefono']) : '';

        if (empty($cliente) || empty($telefono)) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos (cliente o teléfono vacíos)']);
            exit;
        }

        // 1. Intentamos actualizar de manera general por el nombre exacto
        $stmt = $pdo->prepare("UPDATE servicios SET telefono = ? WHERE nombre_cliente = ?");
        $stmt->execute([$telefono, $cliente]);
        
        // Si rowCount es mayor a 0, se actualizó con éxito la coincidencia exacta
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Teléfono actualizado correctamente']);
        } else {
            $idStmt = $pdo->prepare("SELECT id FROM servicios WHERE nombre_cliente LIKE ? ORDER BY id DESC LIMIT 1");
            $idStmt->execute(["%$cliente%"]);
            $ultimoServicio = $idStmt->fetch();

            if ($ultimoServicio) {
                // Forzamos la actualización directo a la ID del servicio del carrito activo
                $updateForzado = $pdo->prepare("UPDATE servicios SET telefono = ? WHERE id = ?");
                $updateForzado->execute([$telefono, $ultimoServicio['id']]);
                
                echo json_encode(['success' => true, 'message' => 'Teléfono forzado en el último servicio del cliente']);
            } else {
                // Si de plano no existe rastro del cliente, creamos el registro de respaldo
                $insert = $pdo->prepare("INSERT INTO servicios (nombre_cliente, telefono) VALUES (?, ?)");
                $insert->execute([$cliente, $telefono]);
                echo json_encode(['success' => true, 'message' => 'Cliente nuevo registrado en servicios']);
            }
        }
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>
