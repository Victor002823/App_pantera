<?php
require_once __DIR__ . '/../config/db.php';

$pdo = (new db())->conexion();

$q = $_GET['q'] ?? '';

$stmt = $pdo->prepare("
    SELECT id, nombre_cliente, total, maniobra, 'servicio' as origen, NULL as status
    FROM servicios 
    WHERE nombre_cliente LIKE ? OR id = ?

    UNION

    SELECT id, nombre_cliente, monto, NULL as maniobra, 'pago' as origen, status
    FROM pagos 
    WHERE id = ?
       OR RIGHT(clabe,4) = ?
       OR RIGHT(paynet_reference,4) = ?
       OR payment_url LIKE ?
       OR openpay_id = ?

    LIMIT 5
");

$stmt->execute([
    "%$q%",
    $q,
    $q,
    $q,
    $q,
    "%$q%",
    $q
]);

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $nombre = htmlspecialchars($row['nombre_cliente'], ENT_QUOTES);

    if($row['origen']=="servicio"){

        $maniobra = (float)($row['maniobra'] ?? 0);
        $totalCompleto = round((float)$row['total'] + $maniobra, 2);
        $montoFinal = round($totalCompleto * 0.50, 2);
        $monto = number_format($totalCompleto, 2);

        $boton = "
        <div class='flex items-center gap-1.5 w-full'>
            <button 
            onclick='generarPago({$row['id']}, \"{$nombre}\", {$montoFinal}, \"spei\")'
            class='flex-1 flex items-center justify-center gap-1 bg-blue-500 hover:bg-blue-700 text-white px-2 py-1.5 rounded-md text-xs font-semibold shadow-sm transition-colors'>
                <i class=\"bi bi-bank\"></i> SPEI
            </button>

            <button 
            onclick='generarPago({$row['id']}, \"{$nombre}\", {$montoFinal}, \"paynet\")'
            class='flex-1 flex items-center justify-center gap-1 bg-emerald-500 hover:bg-emerald-700 text-white px-2 py-1.5 rounded-md text-xs font-semibold shadow-sm transition-colors'>
                <i class=\"bi bi-shop\"></i> Paynet
            </button>

            <button 
            onclick='generarPago({$row['id']}, \"{$nombre}\", {$montoFinal}, \"link\")'
            class='flex-1 flex items-center justify-center gap-1 bg-purple-500 hover:bg-purple-700 text-white px-2 py-1.5 rounded-md text-xs font-semibold shadow-sm transition-colors'>
                <i class=\"bi bi-link-45deg\"></i> Link
            </button>
        </div>
        ";

        $subtitulo="Nuevo Cobro: $$monto";

    }else{

        $monto = number_format($row['total'], 2);

        // Si es pago existente, traer datos adicionales incluyendo payment_url
        $pagoStmt = $pdo->prepare("SELECT clabe, paynet_reference, payment_url, status FROM pagos WHERE id = ?");
        $pagoStmt->execute([$row['id']]);
        $pago = $pagoStmt->fetch(PDO::FETCH_ASSOC);
        
        $clabe = htmlspecialchars($pago['clabe'] ?? '', ENT_QUOTES);
        $paynet = htmlspecialchars($pago['paynet_reference'] ?? '', ENT_QUOTES);
        $payment_url = htmlspecialchars($pago['payment_url'] ?? '', ENT_QUOTES);
        $status = htmlspecialchars($pago['status'] ?? '', ENT_QUOTES);

        $boton = "
        <button onclick=\"verDetalle('{$clabe}', '{$paynet}', '{$payment_url}', '{$row['id']}', '{$nombre}', '{$monto}', '{$status}')\"
        class='w-full flex items-center justify-center gap-1 bg-gray-700 hover:bg-gray-800 text-white px-2 py-1.5 rounded-md text-xs font-semibold shadow-sm transition-colors'>
            <i class='bi bi-eye'></i> Ver Pago
        </button>
        ";

        $subtitulo="Pago Existente: $$monto";
    }

    echo "
    <div class='flex flex-col gap-2 p-3 hover:bg-gray-50 border-b border-gray-100'>
        <div class='min-w-0'>
            <p class='font-semibold text-gray-800 text-sm truncate'>
                <span class='text-gray-400'>#{$row['id']}</span>
                - {$nombre}
            </p>
            <p class='text-xs text-gray-500 font-medium truncate'>
                {$subtitulo}
            </p>
        </div>
        {$boton}
    </div>
    ";
}
