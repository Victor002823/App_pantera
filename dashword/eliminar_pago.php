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
        "error" => "No tienes permisos para archivar"
    ]);

    exit;
}


require_once __DIR__ . '/../config/db.php';

$pdo = (new db())->conexion();


$id = $_POST['id'] ?? null;


if (!$id) {

    echo json_encode([
        "success" => false,
        "error" => "ID faltante"
    ]);

    exit;
}


$stmt = $pdo->prepare("
    UPDATE pagos
    SET deleted_at = NOW()
    WHERE id = ?
");


$stmt->execute([$id]);


if($stmt->rowCount()){

    echo json_encode([
        "success" => true,
        "message" => "Pago archivado correctamente"
    ]);

}else{

    echo json_encode([
        "success" => false,
        "error" => "No se pudo archivar"
    ]);

}