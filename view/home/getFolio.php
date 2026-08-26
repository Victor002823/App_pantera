<?php
header('Content-Type: application/json');

$host = "Localhost";
$db   = "fletehxn_login";
$user = "fletehxn_login";
$pass = "L64tk6MaDqvusRRsp2DW";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $pdo->exec("LOCK TABLES folios WRITE");

    $stmt = $pdo->query("SELECT ultimo_folio FROM folios WHERE id = 1");
    $row = $stmt->fetch();
    $ultimoFolio = $row['ultimo_folio'] ?? 0;

    $nuevoFolio = $ultimoFolio + 1;

    $stmt = $pdo->prepare("UPDATE folios SET ultimo_folio = ? WHERE id = 1");
    $stmt->execute([$nuevoFolio]);

    $pdo->exec("UNLOCK TABLES");

    echo json_encode(['folio' => 'F-' . str_pad($nuevoFolio, 5, '0', STR_PAD_LEFT)]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}