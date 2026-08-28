<?php
// =======================
// CONTROL DE SESIÓN
// =======================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$inactive = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    $_SESSION = [];
    session_destroy();
    header("Location: /index.php?timeout=1");
    exit;
}

if (empty($_SESSION['usuario'])) {
    header("Location: /index.php");
    exit;
}

$_SESSION['last_activity'] = time();

// =======================
// CONEXIÓN BD
// =======================
require_once __DIR__ . '/../config/db.php';

$db = new db();
$pdo = $db->conexion();

if (!$pdo instanceof PDO) {
    die("Error de conexión a la base de datos");
}

// =======================
// FILTROS / PAGINACIÓN
// =======================
$search = $_GET['q'] ?? '';
$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$whereParts = [];
$params = [];

if ($search !== '') {
    $whereParts[] = "(comments LIKE :q OR phone LIKE :q)";
    $params[':q'] = "%$search%";
}

if ($from) {
    $whereParts[] = "DATE(created_at) >= :from";
    $params[':from'] = $from;
}

if ($to) {
    $whereParts[] = "DATE(created_at) <= :to";
    $params[':to'] = $to;
}

$where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

// =======================
// TOTAL REGISTROS
// =======================
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM reviews $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, ceil($total / $limit));

// =======================
// CONSULTA RESEÑAS
// =======================
$sql = "
    SELECT rating, phone, comments, created_at
    FROM reviews
    $where
    ORDER BY created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =======================
// ESTADÍSTICAS
// =======================
$stats = $pdo->query("
    SELECT 
        COUNT(*) AS total,
        ROUND(AVG(rating),1) AS avg_rating
    FROM reviews
")->fetch(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/nav.php'; ?>

<div id="main">

<header class="mb-3">
    <a href="#" class="burger-btn d-block d-xl-none">
        <i class="bi bi-justify fs-3"></i>
    </a>
</header>

<!-- =======================
     CARDS
======================= -->
<div class="row g-3 mb-4">

    <!-- Reseñas totales -->
    <div class="col-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="row align-items-center">

                    <div class="col-4">
                        <div class="stats-icon purple">
                            <i class="iconly-boldChat"></i>
                        </div>
                    </div>

                    <div class="col-8">
                        <h6 class="text-muted font-semibold mb-1">
                            Reseñas totales
                        </h6>

                        <h6 class="font-extrabold mb-0">
                            <?= $stats['total'] ?>
                        </h6>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Calificación promedio -->
    <div class="col-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="row align-items-center">

                    <div class="col-4">
                        <div class="stats-icon yellow">
                            <i class="iconly-boldStar"></i>
                        </div>
                    </div>

                    <div class="col-8">
                        <h6 class="text-muted font-semibold mb-1">
                            Promedio
                        </h6>

                        <h6 class="font-extrabold mb-0">
                            <?= number_format($stats['avg_rating'], 1) ?> ⭐
                        </h6>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>
<!-- =======================
     BUSCADOR
======================= -->
<form method="get" class="search-bar">
    <input
        type="text"
        name="q"
        placeholder="Buscar por comentario o teléfono"
        value="<?= htmlspecialchars($search) ?>"
    >
    <button type ="submit">Buscar</button>
</form>

<!-- =======================
     FILTRO FECHA
======================= -->
<form method="get" class="filter-bar">
    <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">

    <div class="filter-group">
        <label>Desde</label>
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
    </div>

    <div class="filter-group">
        <label>Hasta</label>
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
    </div>

    <button type="submit">Filtrar</button>
    <a href="reviews.php" class="btn-clear">Limpiar</a>

	 <button
        type="button"
        class="btn btn-primary ms-2"
        data-bs-toggle="modal"
        data-bs-target="#imageModal">
        Ver QR 
    </button>
</form>

		

<!-- =======================
     TABLA
======================= -->
<div class="table-wrapper">
    <div class="table-header">
        <h4>Listado de reseñas</h4>
        <span class="badge bg-primary">
            <?= $total ?> registros
        </span>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>⭐</th>
                    <th>Comentario</th>
                    <th>Teléfono</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>

            <?php if (empty($reviews)): ?>
                <tr>
                    <td colspan="4" class="empty">
                        No hay reseñas registradas
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($reviews as $r): ?>
                    <tr>
                        <td class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi <?= $i <= $r['rating'] ? 'bi-star-fill text-warning' : 'bi-star text-muted' ?>"></i>
                            <?php endfor; ?>
                        </td>
                        <td class="comment">
                            <?= nl2br(htmlspecialchars($r['comments'] ?: '—')) ?>
                        </td>
                        <td class="phone">
                            <?= htmlspecialchars($r['phone'] ?: '—') ?>
                        </td>
                        <td class="date">
                            <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            </tbody>
        </table>
    </div>

    <!-- =======================
         PAGINACIÓN
    ======================= -->
    <div class="pagination">
        <?php
        $queryParams = $_GET;
        for ($i = 1; $i <= $pages; $i++):
            $queryParams['page'] = $i;
        ?>
            <a
                class="<?= $i === $page ? 'active' : '' ?>"
                href="?<?= http_build_query($queryParams) ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

</div>
<!-- =======================
     POPUP
======================= -->
<!-- MODAL NEGRO -->
<div 
    class="modal fade" 
    id="imageModal" 
    tabindex="-1" 
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content bg-dark border-0">

            <div class="modal-header border-0">

                <h5 class="modal-title text-white">
                    QR
                </h5>

                <button 
                    type="button" 
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body text-center bg-dark">

                <img 
                    src="/dashword/qr.png"
                    class="img-fluid rounded"
                    alt="Imagen">

            </div>

        </div>

    </div>
</div>


<!-- JS del admin -->
<script src="assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>