<?php
    require_once(__DIR__ . "/../head/head.php"); 
?>
<head>
  <meta charset="UTF-8">
  <title>Panel de Control - Ellince</title>
</head>
<style>
.transition-navbar {
    background: transparent;
    transition: all 0.3s ease-in-out;
    border-bottom: 1px solid transparent; /* Sin borde al inicio */
}

/* Esta clase se añadirá con JS */
.navbar-scrolled {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
}
</style>
<nav id="mainNavbar" class="navbar fixed-top navbar-light transition-navbar" style="height: 60px;">
  <div class="container-fluid d-flex justify-content-between align-items-center px-3">
    <!-- Marca -->
    <a class="navbar-brand mb-0 fw-bold" href="https://control.mudanzasellince.com/panel" style="font-size: 1.2rem; letter-spacing: -0.5px;">
      Control<span class="text-primary">.ellince</span>
    </a>
    <!-- Botones -->
    <ul class="navbar-nav d-flex flex-row align-items-center gap-2 mb-0">
      <?php if(empty($_SESSION['usuario'])): ?>
         <!-- ... -->
      <?php else: ?>
        <li class="nav-item">
          <a href="/view/home/logout.php" class="btn btn-light rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; color: #8A1500; background: rgba(255, 255, 255, 0.8);">
            <i class="fa fa-sign-out" aria-hidden="true"></i>
          </a>
        </li>
      <?php endif; ?>
    </ul>
  </div>
</nav>



          

<div class="fondo">
    <!-- Aquí puedes agregar contenido adicional si es necesario -->
</div>
  <script>
window.addEventListener('scroll', function() {
    const navbar = document.getElementById('mainNavbar');
    if (window.scrollY > 50) {
        navbar.classList.add('navbar-scrolled');
    } else {
        navbar.classList.remove('navbar-scrolled');
    }
});
</script>
    
</body>
 </html>               
