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

$fecha = new DateTime('now', new DateTimeZone('America/Mexico_City'));

$formatter = new IntlDateFormatter(
    'es_MX',
    IntlDateFormatter::NONE,
    IntlDateFormatter::NONE,
    'America/Mexico_City',
    IntlDateFormatter::GREGORIAN,
    'LLLL' // mes completo
);

$mesActual = ucfirst($formatter->format($fecha));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="assets/vendors/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/vendors/iconly/bold.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="/asset/css/style.css">
    
<script src="https://kit.fontawesome.com/65ea5e46f1.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<style>
 .submenu-item > a,h2,.sidebar-title {
  color: #ffffff !important;
}
.sidebar a:hover span,
.sidebar a:focus span,
.sidebar a.active span {
  color: red;
}
.sub {
    color:#5774C9;
}
.logout-item {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.logout-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    width: 160px;
    padding: 10px 14px;

    color: #8A1500;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;

    border: 1.5px solid #8A1500;
    border-radius: 12px;

    transition: all 0.25s ease;
}

.logout-link i {
    font-size: 18px;
}

/* Hover / toque */
.logout-link:hover {
    background-color: #8A1500;
    color: #ffffff;
}


  .dashboard {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
  }

  /* Tarjeta principal moderna (ahora con dos columnas internas) */
  .card {
    background: linear-gradient(135deg, #4e79a7, #77a6d2);
    border-radius: 16px;
    padding: 18px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    min-width: 340px;              /* mayor ancho mínimo para evitar apilamiento */
    flex: 1 1 340px;
    color: white;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.2);
  }

  /* Contenido en fila: dos columnas para Totales */
  .card .totals {
    display: flex;
    gap: 12px;
    align-items: center;
    justify-content: space-between;
    flex-wrap: nowrap;            /* evitar que se envuelvan */
  }
  .total-item {
    flex: 0 0 calc(50% - 6px);    /* forzar 50% cada una (restando la mitad del gap) */
    background: rgba(255,255,255,0.06);
    border-radius: 12px;
    padding: 12px 50px;
    text-align: center;
    backdrop-filter: blur(4px);
    transition: transform 0.18s ease;
    box-sizing: border-box;
  }
  .total-item:hover { transform: translateY(-4px); }
  .total-item h4 {
    color:white;  
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    opacity: 0.95;
  }
  .total-item p {
    margin: 6px 0 0;
    font-size: 1.6rem;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;          /* prevenir salto de línea en números largos */
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Ajuste para pantallas muy pequeñas: apilar debajo de 420px */
@media (max-width: 420px) {
  .card {
    min-width: auto;
    flex-basis: 100%;
  }

  .card .totals {
    flex-direction: column;
    gap: 10px;
  }

  .total-item {
    flex: 1 1 auto;
  }
}
    .chart-container { flex-basis: 100%; }
  }

  .chart-container {
    background: #ffffff;
    padding: 25px;
    border-radius: 18px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    flex: 3 1 600px;
    margin-bottom: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
  }
  .chart-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
  }

  h3.section-title {
    margin-bottom: 15px;
    font-size: 1.25rem;
    color: #2e3a59;
    font-weight: 600;
    border-left: 5px solid #4e79a7;
    padding-left: 10px;
  }

  .filters {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
  }
  .filters select, .filters input[type="date"] {
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
    background-color: #f8fafc;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .filters button {
    padding: 8px 16px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #4e79a7, #3b5f8a);
    color: #fff;
    font-weight: bold;
    cursor: pointer;
  }

  /* Canvas con efecto moderno */
  canvas {
    width: 100% !important;
    max-height: 340px;
    background: linear-gradient(145deg, #f7f9fb, #ffffff);
    border-radius: 14px;
    padding: 12px;
    box-shadow: inset 0 2px 8px rgba(0,0,0,0.05);
    animation: fadeIn 0.6s ease;
  }
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .minmax {
    display: flex;
    justify-content: space-around;
    margin-top: 15px;
    font-weight: bold;
  }
  .max { color: #1a7c1a; }
  .min { color: #d33; }
      .skeleton {
  display: inline-block;
  background: linear-gradient(
    90deg,
    #e0e0e0 25%,
    #f5f5f5 37%,
    #e0e0e0 63%
  );
  background-size: 400% 100%;
  animation: shimmer 1.4s ease infinite;
  border-radius: 4px;
}

.skeleton {
  display: inline-block;
  background: linear-gradient(
    90deg,
    rgba(200, 200, 200, 0.25) 25%,
    rgba(230, 230, 230, 0.45) 37%,
    rgba(200, 200, 200, 0.25) 63%
  );
  background-size: 400% 100%;
  animation: shimmer 1.4s ease infinite;
  border-radius: 4px;
}

.skeleton-text {
  width: 90px;
  height: 1.1em;
}

@keyframes shimmer {
  0% { background-position: 100% 0; }
  100% { background-position: 0 0; }
}
</style>

<body>
    <div id="app">
        <div id="sidebar" class="active">
            <div class="sidebar-wrapper active"  style="background-color:black;">
                <div class="sidebar-header" >
                    <div class="d-flex justify-content-between">
                        <div class="logo">
                            <a style="whidt 100px;" href="https://control.mudanzasellince.com/panel"><img src="assets/images/logo/logo.png" alt="" srcset="" ><h2>control/ellince.com</h2></a>
                        </div>
                        <div class="toggler">
                            <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                        </div>
                    </div>
                </div>
                <div class="sidebar-menu">
                    <ul class="menu">
                        <li class="sidebar-title">Menu</li>

                        <li class="sidebar-item active ">
                            <a href="./" class='sidebar-link'>
                                <i class="bi bi-grid-fill"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <li class="sidebar-item  has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-stack"></i>
                                <span class="sub">Components</span>
                            </a>
                            <ul class="submenu ">
                                <li class="submenu-item ">
                                    <a href="form_rutaforanea.php">Calculafora de Rutas</a>
                                </li>
                                <li class="submenu-item active">
                                    <a href="pagos.php">Cobranza</a>
                                        <ul class="submenu active">
                                <li class="submenu-item active">
                                    <a href="reporte_cotizacion.php">Cotizaciones</a>
                                </li>   
                                 <ul class="submenu active">
                                <li class="submenu-item active">
                                    <a href="enlaces_rastreo.php">Enlaces de rastreo</a>
                                </li>         
                                  <li class="submenu-item active">
                                 <a href="/dashword/reviews.php">
                                  <i class="bi bi-star-fill"></i>
                                     <span>Reseñas</span>
                                  </a>
                                     </li>
                                   <li class="submenu-item active">
                                    <a href="https://tarifascapufe.com.mx/traza-tu-ruta/"
                                        target="_blank"
                                        rel="noopener noreferrer">
                                        Calcular casetas
                                       </a>     
                                </li>
                                 <li class="submenu-item active">
                                    <a href="https://facturasgas.com/facturacion/autofactura.php"
   target="_blank"
   rel="noopener noreferrer">
   Abrir facturación
</a>
                                </li>
                                     
                                
                                
                               
                </div>
                                                                        <li class="nav-item logout-item">
  <a href="/view/home/logout.php" class="logout-link">
    <i class="fa fa-sign-out" aria-hidden="true"></i>
    <span>Cerrar sesión</span>
  </a>
</li>

                <button class="sidebar-toggler btn x"><i data-feather="x"></i></button>
            </div>
        </div>
                                     
        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>

            <div class="page-heading">
                <h3>Profile Statistics</h3>
            </div>
            <div class="dashboard">
  <!-- Tarjeta moderna con totales uno enfrente de otro -->
  <div class="card">
  <div class="totals">

<div class="total-item">
  <h4>Ventas totales <?= $mesActual ?></h4>

  <p id="total-ventas-display">
    $
    <span id="total-ventas" class="skeleton skeleton-text"></span>
  </p>
</div>

    <div class="total-item">
      <h4>Cotizaciones totales <?= $mesActual ?></h4>
      <p id="total-cot-display">
        $
        <span id="total-cot" class="skeleton skeleton-text"></span>
      </p>
    </div>

  </div>
</div>

  <div class="chart-container">
    <h3 class="section-title">Ventas del período</h3>
    <div class="filters">
      <select id="filter-period">
        <option value="week">Semana</option>
        <option value="month">Mes</option>
        <option value="year">Año</option>
      </select>
      <label>Desde: <input type="date" id="start-date"></label>
      <label>Hasta: <input type="date" id="end-date"></label>
      <button id="apply-range">Aplicar</button>
    </div>
    <canvas id="salesChart"></canvas>
   <div class="minmax">
  <div class="max">
    Ventas máxima: $
    <span id="cot-max" class="skeleton skeleton-text"></span>
  </div>

  <div class="min">
    Ventas mínima: $
    <span id="cot-min" class="skeleton skeleton-text"></span>
  </div>
</div>
  </div>

  <div class="chart-container">
    <h3 class="section-title">Cotizaciones</h3>
    <canvas id="cotizacionesChart"></canvas>
<div class="minmax">
  <div class="max">
    Cotización máxima: $
    <span id="cot-max" class="skeleton skeleton-text"></span>
  </div>

  <div class="min">
    Cotización mínima: $
    <span id="cot-min" class="skeleton skeleton-text"></span>
  </div>
</div>
  </div>
</div>

<script>
/* --- NO MODIFIQUÉ LA LÓGICA DE LOS GRÁFICOS --- */
let filtroGlobal = 'week';
let startDateGlobal = new Date();
let endDateGlobal = new Date();

function generarDatos(n, min=100, max=1000){
  return Array.from({ length: n }, () => Math.floor(Math.random()*(max-min)+min));
}

function generarEtiquetas(filtro){
  if(filtro==='week') return ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
  if(filtro==='month') return ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  if(filtro==='year'){
    const anios = [];
    const currentYear = new Date().getFullYear();
    for(let i=0;i<5;i++){ anios.push(currentYear-4+i); }
    return anios;
  }
  return [];
}

function generarTooltipLabel(context){
  const index = context.dataIndex;
  const value = context.dataset.data[index];
  if(filtroGlobal==='week'){
    const fecha = new Date(startDateGlobal);
    fecha.setDate(startDateGlobal.getDate()+index);
    return context.dataset.label + ': $' + value + ' (' + fecha.toLocaleDateString('es-ES') + ')';
  } else if(filtroGlobal==='month'){
    const meses = generarEtiquetas('month');
    return context.dataset.label + ': $' + value + ' (' + meses[index] + ' ' + new Date().getFullYear() + ')';
  } else if(filtroGlobal==='year'){
    const anios = generarEtiquetas('year');
    return context.dataset.label + ': $' + value + ' (' + anios[index] + ')';
  }
  return context.dataset.label + ': $' + value;
}

const salesCtx = document.getElementById('salesChart').getContext('2d');
const cotCtx = document.getElementById('cotizacionesChart').getContext('2d');

let salesChart = new Chart(salesCtx,{
  type:'line',
  data:{ labels:[], datasets:[{label:'Ventas', data:[], borderColor:'#4e79a7', backgroundColor:'rgba(78,121,167,0.15)', fill:true, tension:0.4, pointRadius:5, pointBackgroundColor:'#4e79a7'}]},
  options:{ responsive:true,
    plugins:{
      legend:{ display:false },
      tooltip:{ mode:'index', intersect:false, callbacks:{ label:generarTooltipLabel } }
    },
    scales:{ 
      y:{ beginAtZero:true, grid:{ color:'rgba(0,0,0,0.05)' } },
      x:{ grid:{ display:false } }
    },
    animation:{ duration:1000, easing:'easeOutQuart' }
  }
});

let cotChart = new Chart(cotCtx,{
  type:'line',
  data:{ labels:[], datasets:[{label:'Cotizaciones', data:[], borderColor:'#f28e2b', backgroundColor:'rgba(242,142,43,0.15)', fill:true, tension:0.4, pointRadius:5, pointBackgroundColor:'#f28e2b'}]},
  options:{ responsive:true,
    plugins:{
      legend:{ display:false },
      tooltip:{ mode:'index', intersect:false, callbacks:{ label:generarTooltipLabel } }
    },
    scales:{ 
      y:{ beginAtZero:false, grid:{ color:'rgba(0,0,0,0.05)' } },
      x:{ grid:{ display:false } }
    },
    animation:{ duration:1000, easing:'easeOutQuart' }
  }
});

function actualizarGraficos(filtro='week'){
  filtroGlobal = filtro;
  const labels = generarEtiquetas(filtro);
  const n = labels.length;
  const sales = generarDatos(n,100,1000);
  const cot = generarDatos(n,2800,3200);

  salesChart.data.labels = labels;
  salesChart.data.datasets[0].data = sales;
  salesChart.update();

  cotChart.data.labels = labels;
  cotChart.data.datasets[0].data = cot;
  cotChart.update();

  document.getElementById('sales-max').textContent='Ventas máximas: $'+Math.max(...sales);
  document.getElementById('sales-min').textContent='Ventas mínimas: $'+Math.min(...sales);
  document.getElementById('cot-max').textContent='Cotización máxima: $'+Math.max(...cot);
  document.getElementById('cot-min').textContent='Cotización mínima: $'+Math.min(...cot);

  /* Opcional: actualizar totales de la tarjeta automáticamente (descomenta si quieres) */
  // document.getElementById('total-ventas-display').textContent = '$' + sales.reduce((a,b)=>a+b,0);
  // document.getElementById('total-cot-display').textContent = '$' + cot.reduce((a,b)=>a+b,0);
}

document.getElementById('filter-period').addEventListener('change',function(){
  actualizarGraficos(this.value);
});

document.getElementById('apply-range').addEventListener('click',function(){
  actualizarGraficos(document.getElementById('filter-period').value);
});

// Inicializar
actualizarGraficos('week');
      
</script>

    <script src="assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script src="assets/vendors/apexcharts/apexcharts.js"></script>
    <script src="assets/js/pages/dashboard.js"></script>

    <script src="assets/js/main.js"></script>
</body>

</html>