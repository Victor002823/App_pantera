<?php
class homeModel{
    private $PDO;

    public function __construct()
    {
        require_once(__DIR__ . '/../config/db.php');
            $pdo = new db();
            $this->PDO = $pdo->conexion();
    }

    public function agregarNuevoUsuario($correo, $password, $nombre_usuario){
        // Verificar si el correo ya existe
        $check = $this->PDO->prepare("SELECT * FROM usuarios WHERE correo = :correo");
        $check->bindParam(":correo", $correo);
        $check->execute();

        if($check->rowCount() > 0){
            return false; // ya existe
        }

        // Insertar nuevo usuario
        $statement = $this->PDO->prepare("INSERT INTO usuarios (correo, password, nombre_usuario) VALUES (:correo, :password, :nombre_usuario)");
        $statement->bindParam(":correo", $correo);
        $statement->bindParam(":password", $password);
        $statement->bindParam(":nombre_usuario", $nombre_usuario);

        try {
            $statement->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function obtenerclave($correo){
        $statement = $this->PDO->prepare("SELECT password FROM usuarios WHERE correo = :correo");
        $statement->bindParam(":correo", $correo);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if ($result && isset($result['password'])) {
            return $result['password'];
        }
        return false; // Usuario no encontrado
    }

    public function obtenerUsuario($correo){
        $statement = $this->PDO->prepare("SELECT * FROM usuarios WHERE correo = :correo");
        $statement->bindParam(":correo", $correo);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    // =======================================================
    // 🔐 NUEVOS MÉTODOS PARA INTEGRACIÓN BIOMÉTRICA (HUELAS)
    // =======================================================

    /**
     * Busca y extrae el par de credenciales de huella digital registradas para un correo específico.
     * Mapea las columnas con alias idénticos a los esperados por get_credential.php
     */
    public function obtenerHuellaPorCorreo($correo) {
        $statement = $this->PDO->prepare("SELECT credential_id AS credentialId, public_key AS publicKey FROM usuarios WHERE correo = :correo");
        $statement->bindParam(":correo", $correo);
        
        try {
            $statement->execute();
            return $statement->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Vincula o sobreescribe las credenciales biométricas del usuario (WebAuthn o APK Nativa).
     * Modifica el registro actual del usuario sin alterar contraseñas o nombres.
     */
    public function guardarHuellaUsuario($correo, $credentialId, $publicKey) {
        $statement = $this->PDO->prepare("UPDATE usuarios SET credential_id = :credentialId, public_key = :publicKey WHERE correo = :correo");
        $statement->bindParam(":credentialId", $credentialId);
        $statement->bindParam(":publicKey", $publicKey);
        $statement->bindParam(":correo", $correo);
        
        try {
            return $statement->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
