<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../../config/db.php");

try {
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();

    if (is_string($pdo)) {
        throw new Exception($pdo);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->query("SELECT ultimo_folio FROM folios WHERE id = 1 FOR UPDATE");
    $row = $stmt->fetch();
    $ultimoFolio = $row['ultimo_folio'] ?? 0;

    $nuevoFolio = $ultimoFolio + 1;

    $stmt = $pdo->prepare("UPDATE folios SET ultimo_folio = ? WHERE id = 1");
    $stmt->execute([$nuevoFolio]);

    $pdo->commit();

    echo json_encode(['folio' => 'F-' . str_pad($nuevoFolio, 5, '0', STR_PAD_LEFT)]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
