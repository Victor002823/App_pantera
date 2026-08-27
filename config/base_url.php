<?php
// URL base del sitio, usada para construir ligas de rastreo y similares.
// Configurable via variable de entorno BASE_URL; si no está definida,
// usa el dominio de Render como respaldo.
return getenv('BASE_URL') ?: 'https://app-pantera.onrender.com';
