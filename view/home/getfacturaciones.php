<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

session_start();

require_once(__DIR__ . "/../../config/db.php");


try {


    $db = new db();
    $pdo = $db->conexion();


    if(is_string($pdo)){
        throw new Exception($pdo);
    }


    // BUG FIX: antes, si no había sesión iniciada, el código seguía
    // ejecutando la consulta como "no admin" con asesor = '' y
    // regresaba success:true con datos vacíos, en vez de rechazar
    // la petición explícitamente.
    if(!isset($_SESSION['usuario'])){

        http_response_code(401);

        echo json_encode([
            "success"=>false,
            "error"=>"Sesión no válida, inicia sesión de nuevo."
        ], JSON_UNESCAPED_UNICODE);

        exit;

    }


    $usuario = $_SESSION['usuario']['nombre_usuario'] ?? '';
    $rol = $_SESSION['usuario']['rol'] ?? '';


    $esAdmin = ($rol === 'admin');


    // BUG FIX: se unificó la consulta duplicada de admin/no-admin en una sola,
    // y se agrupó por servicio_id + producto para eliminar filas repetidas
    // del mismo producto dentro del mismo servicio.
    // - cantidad/subtotal/iva/total: se suman (son valores por producto,
    //   sumarlos al fusionar duplicados conserva el monto real).
    // - anticipo/cliente/asesor/fecha/fecha_servicio/hora_servicio: son
    //   valores a nivel del servicio completo (se repiten en cada fila),
    //   por eso se usa MAX() y NO SUM(), para no multiplicarlos.
    $sql = "
    SELECT
        MAX(id)              AS id,
        servicio_id,
        MAX(asesor)           AS asesor,
        MAX(fecha)            AS fecha,
        MAX(cliente)          AS cliente,
        MAX(fecha_servicio)   AS fecha_servicio,
        MAX(hora_servicio)    AS hora_servicio,
        producto,
        SUM(cantidad)         AS cantidad,
        MAX(anticipo)         AS anticipo,
        SUM(subtotal)         AS subtotal,
        SUM(iva)              AS iva,
        SUM(total)            AS total

    FROM facturaciones

    " . ($esAdmin ? "" : "WHERE asesor = ?") . "

    GROUP BY servicio_id, producto

    ORDER BY id DESC
    ";


    $stmt = $pdo->prepare($sql);

    if($esAdmin){
        $stmt->execute();
    }else{
        $stmt->execute([$usuario]);
    }


    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);



    echo json_encode([
        "success"=>true,
        "data"=>$data
    ], JSON_UNESCAPED_UNICODE);



}catch(Exception $e){


    http_response_code(500);

    echo json_encode([
        "success"=>false,
        "error"=>$e->getMessage()
    ], JSON_UNESCAPED_UNICODE);


}
