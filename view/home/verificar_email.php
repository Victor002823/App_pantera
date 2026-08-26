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

    // ACCIÓN 1: VERIFICAR SI EXISTE EL CORREO
    if ($metodo === 'GET') {
        $cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';

        if (empty($cliente)) {
            echo json_encode(['success' => false, 'error' => 'Falta el nombre del cliente']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT correo FROM servicios WHERE nombre_cliente = ? AND correo IS NOT NULL AND correo != '' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$cliente]);
        $resultado = $stmt->fetch();

        if ($resultado && !empty(trim($resultado['correo']))) {
            echo json_encode(['success' => true, 'tiene_email' => true, 'correo' => trim($resultado['correo'])]);
        } else {
            echo json_encode(['success' => true, 'tiene_email' => false]);
        }
        exit;
    }

    // ACCIÓN 2: GUARDAR EL CORREO SI EL USUARIO LO INGRESA
    if ($metodo === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $cliente = isset($input['cliente']) ? trim($input['cliente']) : '';
        $correo = isset($input['email']) ? trim($input['email']) : '';

        if (empty($cliente) || empty($correo)) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos (cliente o correo vacíos)']);
            exit;
        }

        // Validamos formato de correo también en el servidor, no solo en el modal
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'El correo ingresado no tiene un formato válido']);
            exit;
        }

        // 1. Intentamos actualizar de manera general por el nombre exacto
        $stmt = $pdo->prepare("UPDATE servicios SET correo = ? WHERE nombre_cliente = ?");
        $stmt->execute([$correo, $cliente]);

        // Si rowCount es mayor a 0, se actualizó con éxito la coincidencia exacta
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Correo actualizado correctamente']);
        } else {
            $idStmt = $pdo->prepare("SELECT id FROM servicios WHERE nombre_cliente LIKE ? ORDER BY id DESC LIMIT 1");
            $idStmt->execute(["%$cliente%"]);
            $ultimoServicio = $idStmt->fetch();

            if ($ultimoServicio) {
                // Forzamos la actualización directo a la ID del servicio del carrito activo
                $updateForzado = $pdo->prepare("UPDATE servicios SET correo = ? WHERE id = ?");
                $updateForzado->execute([$correo, $ultimoServicio['id']]);

                echo json_encode(['success' => true, 'message' => 'Correo forzado en el último servicio del cliente']);
            } else {
                // Si de plano no existe rastro del cliente, creamos el registro de respaldo
                $insert = $pdo->prepare("INSERT INTO servicios (nombre_cliente, correo) VALUES (?, ?)");
                $insert->execute([$cliente, $correo]);
                echo json_encode(['success' => true, 'message' => 'Cliente nuevo registrado en servicios']);
            }
        }
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>
