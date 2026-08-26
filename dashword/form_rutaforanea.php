<?php

// Control de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$inactive = 900; // 15 minutos
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    $_SESSION = array();
    session_destroy();
    header("Location: /index.php?timeout=1");
    exit;
}

if (empty($_SESSION['usuario'])) {
    header("Location: /index.php");
    exit;
}

// Actualiza última actividad
$_SESSION['last_activity'] = time();

// Obtener nombre y correo del usuario para mostrar en el panel

?>
<?php include __DIR__ . '/nav.php'; ?>
<style>
.btn-icon:focus {
    outline: none;
    box-shadow: none;
}
</style>
<div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>

            <div class="page-heading">
                <div class="page-title">
                    <div class="row">
                        <div class="col-12 col-md-6 order-md-1 order-last">
                            <h3>Calculadora de Rutas</h3>
                            <p class="text-subtitle text-muted">Calcula los costos de rutas foraneas </p>
                        </div>
                        <div class="col-12 col-md-6 order-md-2 order-first">
                            <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page"></li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                <section class="app-foraneo">
    <h3 class="app-title">Ruta Foránea</h3>

    <form id="formulario" onsubmit="calcularCosto(event)" class="app-form">

        <div class="field">
    <label for="base" class="d-flex align-items-center gap-2">
        Base
        <button type="button"
        class="btn btn-icon p-0 border-0 bg-transparent"
        data-bs-toggle="popover"
        data-bs-trigger="focus"
        data-bs-title="Base Camionetas"
        data-bs-content="Base camioneta chica mudanza $ 450
Base flete camioneta chica $ 350
Base mudanza camioneta grande $700
Base flete camioneta grande $550">
    <i class="fa-solid fa-circle-info fs-5 text-primary"></i>
</button>
    </label>

    <input type="number" id="base" class="form-control" required>
</div>

        <div class="field">
            <label for="kmRecorrer">Kilómetros a recorrer</label>
            <input type="number" id="kmRecorrer" required>
        </div>

<div class="field">
    <label for="kmxL" class="d-flex align-items-center gap-2">
        Kilómetros por litro  (km/L)
        <button type="button"
                class="btn btn-icon p-0 border-0 bg-transparent"
                data-bs-toggle="popover"
                data-bs-trigger="focus"
                data-bs-placement="left"
                data-bs-html="true"
                data-bs-title="Info"
                data-bs-content="Ejemplo:<br>Camioneta chica: 10 km/L<br>Camioneta grande: 8 km/L">
            <i class="fa-solid fa-circle-info fs-5 text-primary"></i>
        </button>
    </label>

    <input type="number" id="kmxL" class="form-control" required>
</div>

      <div class="field">
    <label for="costoCombustible" class="d-flex align-items-center gap-2">
        Costo combustible (por litro)
        <button type="button"
                class="btn btn-icon p-0 border-0 bg-transparent"
                data-bs-toggle="popover"
                data-bs-trigger="focus"
                data-bs-placement="left"
                data-bs-html="true"
                data-bs-title="Info"
                data-bs-content="Ejemplo:<br>Camioneta chica $25<br>Camioneta grande $28">
            <i class="fa-solid fa-circle-info fs-5 text-primary"></i>
        </button>
    </label>

    <input type="number" id="costoCombustible" class="form-control" required>
</div>

        <div class="field">
            <label for="casetas">Costo de casetas (opcional)</label>
            <input type="number" id="casetas">
        </div>

        <div class="resultado">
            <span class="resultado-label">Total</span>
            <h2 id="resultadoTitulo" style="display:none;">
                $ <span id="resultado" style="color:black">0</span>
            </h2>
        </div>

        <button id="calcular" type="submit" class="btn-primary">
            Calcular costo
        </button>

    </form>
</section>
            </div>

            <footer>
                <div class="footer clearfix mb-0 text-muted">
                    <div class="float-start">
                        <p>2025 &copy; control/ellunce.com</p>
                    </div>
                    
                </div>
            </footer>
        </div>
    </div>
    <script src="assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script src="assets/js/main.js"></script>
    <script>
document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
  new bootstrap.Popover(el);
});
</script>            
   <script>
document.addEventListener("DOMContentLoaded", function () {
    const formulario = document.getElementById('formulario');
    const resultadoTitulo = document.getElementById('resultadoTitulo');
    const resultado = document.getElementById('resultado');

    // Ocultar resultado al inicio
    resultadoTitulo.style.display = 'none';

    // Opcional: limpiar resultado al reset manual
    formulario.addEventListener('reset', () => {
        resultado.textContent = '0';
        resultadoTitulo.style.display = 'none';
    });
});

// 👉 Tu cálculo ORIGINAL (intacto)
function calcularCosto(event) {
    event.preventDefault();

    const base = parseFloat(document.getElementById('base').value) || 0;
    const kmRecorrer = parseFloat(document.getElementById('kmRecorrer').value);
    const kmxL = parseFloat(document.getElementById('kmxL').value);
    const costoCombustible = parseFloat(document.getElementById('costoCombustible').value);
    const casetas = parseFloat(document.getElementById('casetas').value) || 0;

    if (
        isNaN(kmRecorrer) || kmRecorrer <= 0 ||
        isNaN(kmxL) || kmxL <= 0 ||
        isNaN(costoCombustible) || costoCombustible <= 0
    ) {
        alert("Por favor, ingresa valores válidos.");
        return;
    }

    // ✅ Fórmula original
    const litrosNecesarios = kmRecorrer / kmxL;
    const costoCombustibleTotal = litrosNecesarios * costoCombustible;

    let costoConBase = costoCombustibleTotal + base;
    costoConBase *= 3; // multiplicador 200% extra
    const costoTotal = costoConBase + casetas;

    // Mostrar resultado
    document.getElementById('resultado').textContent = costoTotal.toFixed(2);
    document.getElementById('resultadoTitulo').style.display = 'block';
}
</script>             
</body>

</html>