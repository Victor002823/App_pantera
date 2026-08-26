<?php

require_once __DIR__ . '/config/db.php';

try {

    $db = new db();
    $pdo = $db->conexion();

    if (!($pdo instanceof PDO)) {
        throw new Exception($pdo);
    }

    $tablas = [];

    $resultado = $pdo->query("SHOW TABLES");

    while ($fila = $resultado->fetch(PDO::FETCH_NUM)) {
        $tablas[] = $fila[0];
    }

    $sql = "-- Backup Control El Lince\n";
    $sql .= "-- Fecha: " . date("Y-m-d H:i:s") . "\n\n";

    foreach ($tablas as $tabla) {

        $sql .= "DROP TABLE IF EXISTS `$tabla`;\n";

        $estructura = $pdo->query("SHOW CREATE TABLE `$tabla`")->fetch(PDO::FETCH_ASSOC);

        $sql .= $estructura['Create Table'] . ";\n\n";

        $datos = $pdo->query("SELECT * FROM `$tabla`");

        while ($fila = $datos->fetch(PDO::FETCH_ASSOC)) {

            $valores = array_map(function($valor) use ($pdo) {
                return $valor === null ? "NULL" : $pdo->quote($valor);
            }, array_values($fila));

            $sql .= "INSERT INTO `$tabla` VALUES (" . implode(",", $valores) . ");\n";
        }

        $sql .= "\n";
    }

    file_put_contents(__DIR__ . "/database.sql", $sql);

    echo "Backup creado correctamente:\n";
    echo __DIR__ . "/database.sql\n";

} catch (Exception $e) {

    echo "Error: " . $e->getMessage();

}

?>
