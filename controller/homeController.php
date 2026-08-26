<?php
class homeController{
    private $MODEL;
    public function __construct()
    {
        require_once(__DIR__ . "/../model/homeModel.php");
        $this->MODEL = new homeModel();
    }

    // Ahora recibe $nombre_usuario también
    public function guardarUsuario($correo, $contraseña, $nombre_usuario){
        $valor = $this->MODEL->agregarNuevoUsuario(
            $this->limpiarcorreo($correo),
            $this->encriptarcontraseña($this->limpiarcadena($contraseña)),
            $this->limpiarcadena($nombre_usuario)
        );
        return $valor;
    }

    public function limpiarcadena($campo){
        $campo = strip_tags($campo);
        $campo = filter_var($campo, FILTER_UNSAFE_RAW);
        $campo = htmlspecialchars($campo);
        return $campo;
    }

    public function limpiarcorreo($campo){
        $campo = strip_tags($campo);
        $campo = filter_var($campo, FILTER_SANITIZE_EMAIL);
        $campo = htmlspecialchars($campo);
        return $campo;
    }

    public function encriptarcontraseña($contraseña){
        return password_hash($contraseña,PASSWORD_DEFAULT);
    }

    public function verificarusuario($correo, $contraseña){
        $keydb = $this->MODEL->obtenerclave($correo);

        if (!$keydb) {
            return false;
        }

        return password_verify($contraseña, $keydb);
    }

    // ===== MÉTODOS DE CONSULTA DE USUARIO =====
    public function obtenerUsuarioPorCorreo($correo){
        return $this->MODEL->obtenerUsuario($correo);
    }

    /**
     * Obtiene las credenciales de la huella digital vinculadas al correo
     * Utilizado por get_credential.php
     */
    public function obtenerHuella($correo){
        $correoLimpio = $this->limpiarcorreo($correo);
        // Llama al método correspondiente en tu homeModel
        return $this->MODEL->obtenerHuellaPorCorreo($correoLimpio);
    }

    /**
     * Registra o actualiza los datos de la huella (WebAuthn o APK Android)
     * Utilizado por registrar_huella.php
     */
    public function guardarHuella($correo, $credentialId, $publicKey){
        $correoLimpio = $this->limpiarcorreo($correo);
        $credentialIdLimpio = $this->limpiarcadena($credentialId);
        $publicKeyLimpia = $this->limpiarcadena($publicKey);

        // Llama al método correspondiente en tu homeModel para insertar o actualizar
        return $this->MODEL->guardarHuellaUsuario($correoLimpio, $credentialIdLimpio, $publicKeyLimpia);
    }
}
?>
