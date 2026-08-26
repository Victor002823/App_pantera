<?php
class db {
    private $host="Localhost";
    private $dbname="CAMBIAR_NOMBRE_BD";
    private $user="CAMBIAR_USUARIO";
    private $password="CAMBIAR_PASSWORD";
    private $charset = 'utf8mb4';

    public function conexion(){
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->dbname . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '-06:00'"
            ];
            $pdo = new PDO($dsn, $this->user, $this->password, $options);
            return $pdo;
        } catch (PDOException $e) {
            return "Error de conexión: " . $e->getMessage();
        }
    }
}
?>
