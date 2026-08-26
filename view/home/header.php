<?php
    require_once(__DIR__ . "/../head/head.php"); 
?>
<head>
  <meta charset="UTF-8">
  <title>Panel de Control - Ellince</title>
</head>

<div class="container-fluid">
  <nav class="navbar bg-white d-flex justify-content-between align-items-center" data-bs-theme="light" style="flex-wrap: nowrap;">
    
    <!-- Marca -->
    <a class="navbar-brand mb-0" href="" style="white-space: nowrap; font-size: 18px;">
      Control/ellince.com
    </a>

    <!-- Botones -->
    <ul class="navbar-nav d-flex flex-row align-items-center gap-2 mb-0" style="flex-wrap: nowrap;">
      <?php if(empty($_SESSION['usuario'])): ?>
        <!--  Si no está logueado -->
         <!--<li class="nav-item">
          <a href="/view/home/login.php" class="btn btn-outline-primary btn-sm">
            Iniciar sesión
          </a>
        </li>-->
        <li class="nav-item">
          <a href="/view/home/signup.php" class="btn btn-outline-success" role="button">Regístrate</a>
        </li> 
      <?php else: ?>
        <!-- Si está logueado -->
       
        <li class="nav-item">
          <a href="/view/home/logout.php" class="btn btn-outline-danger"style="width:50px;padding: 10px 10px;border:none;color:#8A1500;font-size: 18px;">
            <i class="fa fa-sign-out" aria-hidden="true"></i>
          </a>
        </li>
      <?php endif; ?>
    </ul>
  </nav>
</div>
          

<div class="fondo">
    <!-- Aquí puedes agregar contenido adicional si es necesario -->
</div>
</body>
 </html>               
