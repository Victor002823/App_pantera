<?php
class RastreoModel
{
    private PDO $db;
    private array $shipdayConfig;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->shipdayConfig = require __DIR__ . '/../config/shipday.php';
    }

    private function shipdayGet(string $path): ?array
    {
        $ch = curl_init($this->shipdayConfig['base_url'] . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->shipdayConfig['timeout'],
            CURLOPT_CAINFO => __DIR__ . '/../config/cacert.pem',
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->shipdayConfig['auth_header'],
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode >= 400) {
            error_log("Shipday API error [$path]: HTTP $httpCode - $error");
            return null;
        }
        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function shipdayPost(string $path, array $payload): ?array
    {
        $ch = curl_init($this->shipdayConfig['base_url'] . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => $this->shipdayConfig['timeout'],
            CURLOPT_CAINFO => __DIR__ . '/../config/cacert.pem',
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->shipdayConfig['auth_header'],
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode >= 400) {
            error_log("Shipday API error [$path]: HTTP $httpCode - $error");
            return null;
        }
        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function obtenerOrden(int $orderId): ?array
    {
        // Nota: se quito el intento previo a /orders/{id} porque en la
        // practica siempre regresa vacio en cuentas estandar de Shipday
        // (confirmado por pruebas); era una llamada desperdiciada en
        // cada visita a la liga.

        // Intento 1: listado general /orders (rapido, cubre ordenes
        // recientes/activas).
        $todas = $this->shipdayGet('/orders');
        if ($todas) {
            foreach ($todas as $o) {
                if ((int)($o['orderId'] ?? 0) === $orderId) return $o;
            }
        }

        // Intento 2 (fallback): /orders/query, para ordenes que ya
        // salieron del listado reciente por antiguedad.
        return $this->buscarOrdenViaQuery($orderId);
    }

    private function buscarOrdenViaQuery(int $orderId): ?array
    {
        $estados = ['ACTIVE', 'ALREADY_DELIVERED', 'FAILED_DELIVERY', 'INCOMPLETE'];
        $payloadBase = [
            'startTime' => '2020-01-01T00:00:00Z',
            'endTime' => gmdate('Y-m-d\TH:i:s\Z'),
            'startCursor' => 1,
            'endCursor' => 200,
        ];

        foreach ($estados as $estado) {
            $payload = $payloadBase + ['orderStatus' => $estado];
            $resultado = $this->shipdayPost('/orders/query', $payload);
            if (!is_array($resultado)) continue;

            foreach ($resultado as $o) {
                if ((int)($o['orderId'] ?? 0) === $orderId) {
                    return $this->normalizarOrdenQuery($o);
                }
            }
        }
        return null;
    }

    private function normalizarOrdenQuery(array $o): array
    {
        $pickup = $o['pickup'] ?? [];
        $delivery = $o['delivery'] ?? [];
        $carrier = $o['carrier'] ?? [];

        return [
            'orderId' => $o['orderId'] ?? null,
            'orderNumber' => $o['orderNumber'] ?? null,
            'restaurant' => [
                'name' => $pickup['name'] ?? null,
                'address' => $pickup['address'] ?? null,
                'latitude' => $pickup['latitude'] ?? $pickup['lat'] ?? null,
                'longitude' => $pickup['longitude'] ?? $pickup['lng'] ?? null,
            ],
            'customer' => [
                'name' => $delivery['name'] ?? null,
                'address' => $delivery['address'] ?? null,
                'latitude' => $delivery['latitude'] ?? $delivery['lat'] ?? null,
                'longitude' => $delivery['longitude'] ?? $delivery['lng'] ?? null,
            ],
            'assignedCarrierId' => $carrier['id'] ?? null,
            'orderStatusAdmin' => $o['status'] ?? null,
            'etaTime' => '',
            'activityLog' => [
                'placementTime' => $o['placementTime'] ?? null,
                'expectedPickupTime' => $o['requestedPickupTime'] ?? null,
                'expectedDeliveryDate' => null,
                'expectedDeliveryTime' => $o['requestedDeliveryTime'] ?? null,
                'assignedTime' => $o['assignedTime'] ?? null,
                'startTime' => $o['startTime'] ?? null,
                'pickedUpTime' => $o['pickedupTime'] ?? null,
                'arrivedTime' => $o['arrivedTime'] ?? null,
                'deliveryTime' => $o['deliveryTime'] ?? null,
                'failedDeliveryTime' => $o['failedDeliveryTime'] ?? null,
            ],
        ];
    }

    public function obtenerCarrier(int $carrierId): ?array
    {
        $todos = $this->shipdayGet('/carriers');
        if (!$todos) return null;
        foreach ($todos as $c) {
            if ((int)($c['id'] ?? 0) === $carrierId) return $c;
        }
        return null;
    }

    public function crearLiga(int $shipdayOrderId, ?int $servicioId = null, ?string $clienteNombre = null, ?string $creadoPor = null, int $ttlHoras = 48): array
{
    $token = bin2hex(random_bytes(16));
    $expiraEn = (new DateTime())->modify("+{$ttlHoras} hours")->format('Y-m-d H:i:s');

    $stmt = $this->db->prepare(
        "INSERT INTO rastreo_links (token, servicio_id, shipday_order_id, cliente_nombre, creado_por, expira_en)
         VALUES (:token, :servicio_id, :shipday_order_id, :cliente_nombre, :creado_por, :expira_en)"
    );
    $stmt->execute([
        ':token' => $token,
        ':servicio_id' => $servicioId,
        ':shipday_order_id' => $shipdayOrderId,
        ':cliente_nombre' => $clienteNombre,
        ':creado_por' => $creadoPor,
        ':expira_en' => $expiraEn,
    ]);

    return ['token' => $token, 'url' => $this->construirUrl($token), 'expira_en' => $expiraEn];
}

    public function construirUrl(string $token): string
    {
        $baseUrl = require __DIR__ . "/../config/base_url.php";
        return "{$baseUrl}/rastreo.php?token={$token}";
    }

    public function obtenerLigaPorToken(string $token): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM rastreo_links WHERE token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        $liga = $stmt->fetch(PDO::FETCH_ASSOC);
        return $liga ?: null;
    }

    public function esLigaValida(array $liga): bool
    {
        // 'completado' y 'fallido' siguen siendo visibles (para mostrar la
        // tarjeta de cierre con los datos del servicio). Solo 'expirado'
        // (o el corte de tiempo de expira_en) bloquea el acceso.
        if ($liga['estado'] === 'expirado') return false;
        if (strtotime($liga['expira_en']) < time()) {
            $this->marcarExpirado($liga['id']);
            return false;
        }
        return true;
    }

    public function marcarEstado(int $ligaId, string $estado): void
    {
        $stmt = $this->db->prepare("UPDATE rastreo_links SET estado = :estado, completado_en = NOW() WHERE id = :id");
        $stmt->execute([':estado' => $estado, ':id' => $ligaId]);
    }

    public function marcarExpirado(int $ligaId): void
    {
        $stmt = $this->db->prepare("UPDATE rastreo_links SET estado = 'expirado' WHERE id = :id");
        $stmt->execute([':id' => $ligaId]);
    }

    // Guarda una "foto" final de los datos (orden + chofer) para que, una
    // vez que el servicio ya termino, futuras visitas a la liga no
    // vuelvan a llamar a la API de Shipday.
    public function guardarSnapshot(int $ligaId, array $datos): void
    {
        $stmt = $this->db->prepare("UPDATE rastreo_links SET datos_finales = :datos WHERE id = :id");
        $stmt->execute([':datos' => json_encode($datos, JSON_UNESCAPED_UNICODE), ':id' => $ligaId]);
    }

    public function obtenerSnapshot(array $liga): ?array
    {
        if (empty($liga['datos_finales'])) return null;
        $decoded = json_decode($liga['datos_finales'], true);
        return is_array($decoded) ? $decoded : null;
    }

    public function evaluarEstadoOrden(array $orden): array
    {
        $log = $orden['activityLog'] ?? [];
        if (!empty($log['deliveryTime'])) return ['estado' => 'completado', 'terminado' => true];
        if (!empty($log['failedDeliveryTime'])) return ['estado' => 'fallido', 'terminado' => true];

        // Cierre forzado: si el chofer marco "llegue a destino" pero Shipday
        // nunca confirma la entrega final (posible desincronizacion entre su
        // app y su dashboard), despues de 15 minutos se da por completado
        // de todos modos, para no dejar al cliente viendo "en camino"
        // indefinidamente por un problema ajeno a nuestro sistema.
        if (!empty($log['arrivedTime'])) {
            try {
                $llegada = new DateTime($log['arrivedTime']);
                $ahora = new DateTime('now', new DateTimeZone('UTC'));
                $minutosTranscurridos = ($ahora->getTimestamp() - $llegada->getTimestamp()) / 60;
                if ($minutosTranscurridos >= 15) {
                    return ['estado' => 'completado', 'terminado' => true, 'cierre_forzado' => true];
                }
            } catch (Exception $e) {
                // Si el formato de fecha falla, se ignora y sigue el flujo normal
            }
        }

        return ['estado' => 'activo', 'terminado' => false];
    }
    public function regenerarLiga(int $ligaId, int $ttlHoras = 48): array
{
    $expiraEn = (new DateTime())->modify("+{$ttlHoras} hours")->format('Y-m-d H:i:s');
    $stmt = $this->db->prepare(
        "UPDATE rastreo_links SET expira_en = :expira_en, estado = 'activo' WHERE id = :id"
    );
    $stmt->execute([':expira_en' => $expiraEn, ':id' => $ligaId]);
    return ['expira_en' => $expiraEn];
}
}
