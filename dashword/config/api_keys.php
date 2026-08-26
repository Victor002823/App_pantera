<?php
/**
 * Configuración de credenciales OpenPay
 *
 * SEGURIDAD: Nunca compartas este archivo ni lo subas a repositorios públicos (GitHub/GitLab).
 * Si usas Git, añade este archivo a tu .gitignore inmediatamente.
 */

return [
    // Tu ID de Comercio (Merchant ID)
    'merchant_id' => getenv('OPENPAY_MERCHANT_ID') ?: 'CAMBIAR_MERCHANT_ID',

    // Tu Llave Privada (Private Key)
    'private_key' => getenv('OPENPAY_PRIVATE_KEY') ?: 'CAMBIAR_PRIVATE_KEY',

    // Cambia a 'true' solamente cuando estés listo para recibir dinero real
    'is_production' => filter_var(getenv('OPENPAY_IS_PRODUCTION') ?: 'false', FILTER_VALIDATE_BOOLEAN)
];
