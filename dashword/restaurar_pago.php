<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Validar sesión
if (empty($_SESSION['usuario'])) {
    echo json_encode([
        "success" => false,
        "error" => "Sesión no válida"
    ]);
    exit;
}

// SOLO ADMIN
if (($_SESSION['rol'] ?? '') !== 'admin') {
    echo json_encode([
        "success" => false,
        "error" => "No tienes permisos para restaurar"
    ]);
    exit;
}

require_once __DIR__ . '/../config/db.php';

try {
    $pdo = (new db())->conexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode([
            "success" => false,
            "error" => "ID inválido o vacío"
        ]);
        exit;
    }
    
    // DEBUG: Ver qué registro vamos a actualizar
    $checkStmt = $pdo->prepare("
        SELECT id, nombre_cliente, deleted_at 
        FROM pagos 
        WHERE id = ?
    ");
    $checkStmt->execute([$id]);
    $registro = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registro) {
        echo json_encode([
            "success" => false,
            "error" => "El pago con ID $id no existe"
        ]);
        exit;
    }
    
    // Restaurar (poner deleted_at en NULL)
    $updateStmt = $pdo->prepare("
        UPDATE pagos
        SET deleted_at = NULL
        WHERE id = ?
    ");
    
    $updateStmt->execute([$id]);
    $rowsAffected = $updateStmt->rowCount();
    
    // Verificar que quedó NULL
    $verifyStmt = $pdo->prepare("
        SELECT deleted_at 
        FROM pagos 
        WHERE id = ?
    ");
    $verifyStmt->execute([$id]);
    $result = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($rowsAffected > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Pago restaurado correctamente",
            "debug" => [
                "id" => $id,
                "cliente" => $registro['nombre_cliente'],
                "deleted_at_antes" => $registro['deleted_at'],
                "deleted_at_despues" => $result['deleted_at'],
                "rows_affected" => $rowsAffected
            ]
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "No se actualizó ningún registro (rowCount = 0)",
            "debug" => [
                "id" => $id,
                "deleted_at_actual" => $result['deleted_at']
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "Error: " . $e->getMessage()
    ]);
}
?>