<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: /index.php");
    exit;
}

// Tanto admin como usuario normal pueden entrar; el filtrado de qué
// enlaces ve cada quien ocurre en api_rastreo_admin.php según su rol.

$inactive = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    session_destroy();
    header("Location: /index.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

$esAdmin = ($_SESSION['rol'] ?? 'usuario') === 'admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rastreo | Panel Administrativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/vendors/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">

<?php include __DIR__ . '/nav.php'; ?>

<main class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">

    <header class="mb-6">
    <a href="#" class="burger-btn d-block d-xl-none">
        <i class="bi bi-justify fs-3"></i>
    </a>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Enlaces de Rastreo</h1>
        <p class="text-slate-500 text-sm mt-0.5">
            <?= $esAdmin
                ? 'Consulta y comparte los links de rastreo generados por todos los usuarios.'
                : 'Consulta y comparte los links de rastreo que has generado.' ?>
        </p>

    </header>

    <section class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 border-b border-slate-100 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-5 py-3.5">ID Servicio</th>
                        <th class="px-5 py-3.5">Cliente</th>
                        <th class="px-5 py-3.5">Estado</th>
                        <th class="px-5 py-3.5">Creado</th>
                        <th class="px-5 py-3.5">Expira</th>
                        <th class="px-5 py-3.5">Link</th>
                    </tr>
                </thead>
                <tbody id="tablaRastreo" class="divide-y divide-slate-100">
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/eruda"></script>
<script>eruda.init();</script>
<!-- JS del admin -->
<script src="assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/enlaces_rastreo.js?v=4"></script>
</body>
</html>
