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
<div id="main">
<header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
<div class="page-heading">
                <div class="page-title"><br>
                    <div class="row">
                        <div class="col-12 col-md-6 order-md-1 order-last">
                            <h3>Cuentas de deposito</h3>
                            <p class="text-subtitle text-muted">Cuentas autorazadas de depositos bancarios </p>
                        </div>
            </header>

<div class="card">
  <div class="copied">¡Copiado!</div>
  <p><strong>VICTOR IVÁN ROJAS DURÁN</strong></p>

  <ul id="text1">
    <li>
      <strong>BANCOMER</strong><br>
      Número de cuenta: 283 819 4777<br>
      Cuenta CLABE: 012180028381947772
    </li>
    <li>
      <strong>HSBC</strong><br>
      Número de cuenta: 6297678650<br>
      Cuenta CLABE: 021180062976786502
    </li>
     <li>
      <strong>BANREGIO</strong><br>
      Número de cuenta: 995427250018<br>
      Cuenta CLABE: 058597000012862885
    </li>
     <li>
      <strong>SANTANDER</strong><br>
      Número de cuenta: 14013450094<br>
      Cuenta CLABE: 014180140134500941
    </li>
     <li>
      <strong>NU BANK</strong><br>
      Número de cuenta: 00012394840<br>
      Cuenta CLABE: 638180000123948409
    </li>
  </ul>

  <button class="copy-btn" onclick="copyText('text1', this)">Copiar</button>
</div>

<div class="card">
  <div class="copied">¡Copiado!</div>
  <p><strong>Laura Alejandra Cedillo Flores</strong></p>

  <ul id="text2">
    <li>
      <strong>BANCOMER</strong><br>
      Número de cuenta: 2992894718<br>
      Cuenta CLABE: 012180029928947189
    </li>
    <li>
      <strong>HSBC</strong><br>
      Número de cuenta: 6595255212<br>
      Cuenta CLABE: 021180065952552126
    </li>
    <li>
      <strong>NU MÉXICO</strong><br>
      Número de cuenta: 00015782599<br>
      Cuenta CLABE: 638180000157825996
    </li>
    <li>
      <strong>SANTANDER</strong><br>
      Número de cuenta: 14014003175<br>
      Cuenta CLABE: 014180140140031750
    </li>
    <li>
      <strong>BANREGIO</strong><br>
      Número de cuenta: 942332340013<br>
      Cuenta CLABE: 058597000073473077
    </li>
  </ul>

  <button class="copy-btn" onclick="copyText('text2', this)">Copiar</button>
</div>
    <script src="assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script src="assets/js/main.js"></script>
<script>
function copyText(elementId, btn) {
    const element = document.getElementById(elementId);
    let text = '';
    
    element.querySelectorAll('li, p').forEach(item => {
        text += item.innerText + '\n';
    });

    navigator.clipboard.writeText(text).then(() => {
        const card = btn.parentElement;
        const copiedMsg = card.querySelector('.copied');
        copiedMsg.classList.add('show-copied');
        setTimeout(() => copiedMsg.classList.remove('show-copied'), 1500);
    }).catch(err => {
        alert('Error al copiar: ' + err);
    });
}
</script>

</body>
</html>