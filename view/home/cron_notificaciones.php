<?php
// cron_notificaciones.php — PRODUCCIÓN
// Revisa la tabla notificaciones_programadas y dispara las que ya vencieron.
// Correr cada minuto vía crontab, por ejemplo:
//   * * * * * curl -s "https://TU-URL/view/home/cron_notificaciones.php?token=TU_TOKEN" >> /ruta/logs/cron_notif.log 2>&1

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0); // nunca mostrar errores en pantalla en producción

// 🔐 Token de acceso — solo quien conozca este valor puede disparar el cron.
// ⚠️ Cambia este valor: el que traías quedó expuesto en el chat de soporte.
define('CRON_TOKEN', '0b6f3879a3666b344f6618705d2df632c15730c86274dc6aac6d5838cef362ec');

$tokenRecibido = $_GET['token'] ?? '';
if (!hash_equals(CRON_TOKEN, $tokenRecibido)) {
    http_response_code(403);
    error_log("[cron_notificaciones] Acceso rechazado, token inválido o ausente");
    exit;
}

require_once(__DIR__ . "/../../config/db.php");

// URL de tu API en Render
define('NOTIFY_ENDPOINT', 'https://galeria-api-5pel.onrender.com/notify');

try {
    $conexionDB = new db();
    $pdo = $conexionDB->conexion();

    if (is_string($pdo)) {
        throw new Exception($pdo);
    }
} catch (Exception $e) {
    error_log("[cron_notificaciones] Error de conexión a BD: " . $e->getMessage());
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, factura_id, titulo, mensaje
        FROM notificaciones_programadas
        WHERE enviado = 0
          AND programado_para <= NOW()
        ORDER BY programado_para ASC
        LIMIT 50
    ");
    $stmt->execute();
    $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$pendientes) {
        exit; // nada que hacer, silencioso
    }

    foreach ($pendientes as $notif) {
        $enviado = false;

        // Cada envío está aislado: si uno falla, seguimos con los demás
        try {
            $payload = json_encode([
                'titulo'  => $notif['titulo'],
                'mensaje' => $notif['mensaje']
            ], JSON_UNESCAPED_UNICODE);

            $ch = curl_init(NOTIFY_ENDPOINT);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_TIMEOUT => 45, // Render free tier puede tardar en despertar
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json"
                ],
                // El curl.cainfo del php.ini de este entorno (app de hosting Android)
                // apunta a un cert.pem inaccesible/roto. Como este endpoint es fijo
                // y de confianza (nuestra propia API en Render), se desactiva la
                // verificación de certificado solo para esta llamada puntual.
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0
            ]);
            $resultado = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $enviado = true;
            } else {
                error_log("[cron_notificaciones] Fallo notificando id={$notif['id']}: HTTP {$httpCode} - {$resultado} - {$curlError}");
            }

        } catch (Throwable $eNotif) {
            error_log("[cron_notificaciones] Excepción notificando id={$notif['id']}: " . $eNotif->getMessage());
        }

        // Solo marcamos como enviado si de verdad llegó bien.
        // Si falló, se queda pendiente y se reintenta en el siguiente minuto
        // (mientras siga dentro de la ventana de programado_para <= NOW()).
        if ($enviado) {
            try {
                $pdo->prepare("UPDATE notificaciones_programadas SET enviado = 1 WHERE id = :id")
                    ->execute([':id' => $notif['id']]);
            } catch (Throwable $eUpdate) {
                error_log("[cron_notificaciones] Error marcando enviado id={$notif['id']}: " . $eUpdate->getMessage());
            }
        }
    }

} catch (Throwable $eGeneral) {
    error_log("[cron_notificaciones] Error general: " . $eGeneral->getMessage());
}

// 🧹 Limpieza: borra notificaciones ya enviadas con más de 7 días de antigüedad,
// para no acumular historial indefinidamente en la tabla.
try {
    $borradas = $pdo->exec("
        DELETE FROM notificaciones_programadas
        WHERE enviado = 1
          AND programado_para <= (NOW() - INTERVAL 7 DAY)
    ");
    if ($borradas > 0) {
        error_log("[cron_notificaciones] Limpieza: {$borradas} notificaciones viejas borradas");
    }
} catch (Throwable $eLimpieza) {
    error_log("[cron_notificaciones] Error en limpieza: " . $eLimpieza->getMessage());
}