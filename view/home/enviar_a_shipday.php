<?php
// enviar_a_shipday.php

function enviarOrdenAShipday($pdo, $facturaId) {
    // 1. Obtener los datos usando JOIN
    $stmt = $pdo->prepare("
        SELECT f.id, f.fecha_servicio, f.hora_servicio, 
               s.nombre_cliente, s.telefono, 
               s.direccion_origen, s.direccion_destino 
        FROM facturaciones f
        INNER JOIN servicios s ON f.servicio_id = s.id
        WHERE f.id = ?
    ");
    $stmt->execute([$facturaId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) return false;

    // 2. Mapeo estricto para Shipday
    // RESTAURANT = RECOGIDA | CUSTOMER = ENTREGA
    $payload = [
        "orderNumber"           => "FACT-" . $data['id'],
        "orderType"             => "Delivery",
        
        // Recogida (Donde el repartidor recoge la mercancía)
        "restaurantName"        => "Punto de Origen",
        "restaurantAddress"     => $data['direccion_origen'],
        "restaurantPhoneNumber" => $data['telefono'],
        
        // Entrega (Donde el cliente recibe)
        "customerName"          => $data['nombre_cliente'],
        "customerAddress"       => $data['direccion_destino'],
        "customerPhoneNumber"   => $data['telefono'],
        
        "deliveryDate"          => $data['fecha_servicio'],
        "deliveryTime"          => $data['hora_servicio']
    ];

    $ch = curl_init("https://webhook.site/800c233c-ae24-4563-afaf-2a9f15a4c286");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic mkR1GdPPH8.2z8D5UGaJ6P3IEWhBF9R",
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode >= 200 && $httpCode < 300);
}
