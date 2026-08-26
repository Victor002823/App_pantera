<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';

$log = __DIR__ . "/openpay_log.txt";


function escribirLog($texto)
{
    global $log;

    file_put_contents(
        $log,
        $texto,
        FILE_APPEND
    );
}



try {

    $pdo = (new db())->conexion();

} catch(Exception $e){

    escribirLog(
        "ERROR BD CONEXION: ".$e->getMessage()."\n\n"
    );

    http_response_code(500);

    echo json_encode([
        "success"=>false,
        "error"=>"Error interno"
    ]);

    exit;
}



// =======================
// RECIBIR JSON
// =======================

$json = file_get_contents("php://input");

$data = json_decode($json,true);



escribirLog(
    "==============================\n".
    "FECHA: ".date('Y-m-d H:i:s')."\n".
    "BODY:\n".$json."\n\n"
);



if(!$data){

    escribirLog(
        "ERROR JSON VACIO\n\n"
    );

    http_response_code(400);

    echo json_encode([
        "success"=>false
    ]);

    exit;

}



$tipo = $data['type'] ?? null;



// =======================
// VERIFICACION OPENPAY
// =======================

if($tipo === "verification"){


    escribirLog(
        "VERIFICACION RECIBIDA\n\n"
    );


    http_response_code(200);


    echo json_encode([
        "success"=>true
    ]);

    exit;

}



// =======================
// OBTENER ID
// =======================


$openpay_id =
$data['transaction']['id']
??
$data['id']
??
null;



if(!$openpay_id){


    escribirLog(
        "SIN OPENPAY ID\n\n"
    );


    http_response_code(200);


    echo json_encode([
        "success"=>true
    ]);

    exit;

}



// =======================
// ESTADOS
// =======================


$estado=null;



switch($tipo){


    case "charge.created":

        $estado="pending";

        break;



    case "charge.succeeded":

        $estado="completed";

        break;



    case "charge.failed":

        $estado="failed";

        break;



    case "charge.cancelled":

        $estado="cancelled";

        break;



    case "charge.refunded":

        $estado="refunded";

        break;



    case "cashout.created":

        $estado="pending";

        break;



    case "cashout.charged":
    case "cashout.completed":

        $estado="completed";

        break;



    case "cashout.expired":

        $estado="expired";

        break;



    case "cashout.canceled":

        $estado="cancelled";

        break;

}



if(!$estado){


    escribirLog(
        "EVENTO NO UTILIZADO: ".$tipo."\n\n"
    );


    http_response_code(200);


    echo json_encode([
        "success"=>true
    ]);

    exit;

}




// =======================
// VALIDAR EXISTENCIA
// =======================


try {


    $buscar=$pdo->prepare(
        "
        SELECT id,status,openpay_id
        FROM pagos
        WHERE openpay_id=?
        "
    );


    $buscar->execute([
        $openpay_id
    ]);


    $registro=$buscar->fetch(PDO::FETCH_ASSOC);



    escribirLog(
        "BUSQUEDA:\n".
        print_r($registro,true).
        "\n\n"
    );



} catch(Exception $e){


    escribirLog(
        "ERROR BUSQUEDA: ".$e->getMessage()."\n\n"
    );


}





// =======================
// ACTUALIZAR
// =======================


try {


    $stmt=$pdo->prepare(
        "
        UPDATE pagos
        SET status=?
        WHERE openpay_id=?
        "
    );



    $stmt->execute([

        $estado,

        $openpay_id

    ]);



    escribirLog(

        "EVENTO: ".$tipo."\n".
        "OPENPAY ID: ".$openpay_id."\n".
        "ESTADO: ".$estado."\n".
        "FILAS UPDATE: ".$stmt->rowCount()."\n\n"

    );



}catch(Exception $e){


    escribirLog(

        "ERROR UPDATE: ".$e->getMessage()."\n\n"

    );


    http_response_code(500);


    echo json_encode([

        "success"=>false

    ]);

    exit;

}





// =======================
// RESPUESTA OPENPAY
// =======================


http_response_code(200);


echo json_encode([

    "success"=>true,

    "tipo"=>$tipo,

    "estado"=>$estado,

    "openpay_id"=>$openpay_id,

    "filas"=>$stmt->rowCount()

]);