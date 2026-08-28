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
    <link rel="stylesheet" href="assets/css/custom.css?v=<?= time() ?>">
    <script src="https://kit.fontawesome.com/65ea5e46f1.js" crossorigin="anonymous"></script>
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
</style>
<body>
    <div id="app">
        <div id="sidebar" class="active">
            <div class="sidebar-wrapper active"  style="background-color:black;">
                <div class="sidebar-header" >
                    <div class="d-flex justify-content-between">
                        <div class="logo">
                            <a style="whidt 100px;" href="/panel"><img src="assets/images/logo/logo.png" alt="" srcset="" ><h2>Pantera.TM</h2></a>
                        </div>
                        <div class="toggler">
                            <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                        </div>
                    </div>
                </div>
                <div class="sidebar-menu">
                    <ul class="menu">
                        <li class="sidebar-title">Menu</li>

                        <li class="sidebar-item  ">
                            <a href="./" class='sidebar-link'>
                                <i class="bi bi-grid-fill"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <li class="sidebar-item active has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-stack"></i>
                                <span>Components</span>
                            </a>
                            <ul class="submenu active">
                                <li class="submenu-item active">
                                    <a href="form_rutaforanea.php">Calculadora de Rutas</a>
                                </li>
                                <ul class="submenu active">
                                <li class="submenu-item active">
                                    <a href="pagos.php">Cobranza</a>
                                </li>
                                <ul class="submenu active">
                                <li class="submenu-item active">
                                    <a href="reporte_cotizacion.php">Cotizaciones</a>
                                </li>  
                                 <ul class="submenu active">
                                <li class="submenu-item active">
                                    <a href="enlaces_rastreo.php">Enlaces de rastreo</a>
                                </li>      
                               <li class="sidebar-item">
                                 <li class="submenu-item active">
                                 <a href="/dashword/reviews.php">
                                  <i class="bi bi-star-fill"></i>
                                     <span>Reseñas</span>
                                  </a>
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
                                     
                                
                                
                               
                </div><br>
                                                                        <li class="nav-item logout-item">
  <a href="/view/home/logout.php" class="logout-link">
    <i class="fa fa-sign-out" aria-hidden="true"></i>
    <span>Cerrar sesión</span>
  </a>
</li>

                <button class="sidebar-toggler btn x"><i data-feather="x"></i></button>
            </div>
        </div>