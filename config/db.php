<?php
class db {
    public function conexion(){
        try {
            $host    = getenv('DB_HOST') ?: 'localhost';
            $port    = getenv('DB_PORT') ?: '5432';
            $dbname  = getenv('DB_NAME') ?: '';
            $user    = getenv('DB_USER') ?: '';
            $pass    = getenv('DB_PASSWORD') ?: '';

            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            $pdo = new PDO($dsn, $user, $pass, $options);
            return $pdo;
        } catch (PDOException $e) {
            return "Error de conexión: " . $e->getMessage();
        }
    }
}
?>
