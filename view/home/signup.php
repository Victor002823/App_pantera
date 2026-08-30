<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Control manual de registro
$registro_activo = true;

if(!empty($_SESSION['usuario'])){
    header("Location:panel_control.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta - Transportes y Mudanzas Pantera</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/view/home/styles_login.css">
</head>
<body>

<?php if (!$registro_activo): ?>
    <div class="fondo-login" style="text-align:center; margin-top:100px;">
        <h2 style="color:black;">El registro está cerrado por el momento.</h2>
    </div>
</body>
</html>
<?php
    exit;
endif;
?>

<div class="fondo-login"> 
    <div class="icon"></div>

    <form action="store.php" method="POST" class="col-3 login" autocomplete="off">

      <div class="mb-3">
    <label class="form-label">Nombre de usuario</label>
    <input 
        type="text" 
        name="nombre_usuario" 
        class="form-control"
        value="<?= $_GET['nombre_usuario'] ?? '' ?>"
    >
</div>

        <!-- CORREO -->
        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input 
                type="email" 
                placeholder="xx@nombre"
                name="correo" 
                value="<?= $_GET['correo'] ?? '' ?>" 
                class="form-control"
                autocomplete="off"
            >
        </div>

        <!-- CONTRASEÑA -->
        <div class="mb-3">
            <label class="form-label">Contraseña</label>

            <div class="box-eye">
                <button type="button" onclick="mostrarContraseña('password','eyepassword')">
                 
                </button>
            </div>

            <input 
                type="password" 
                name="contraseña" 
                class="form-control" 
                id="password"
                autocomplete="new-password"
            >
        </div>

        <!-- CONFIRMAR CONTRASEÑA -->
        <div class="mb-3">
            <label class="form-label">Repite tu contraseña</label>

            <div class="box-eye">
                <button type="button" onclick="mostrarContraseña('password2','eyepassword2')">
                   
                </button>
            </div>

            <input 
                type="password" 
                name="confirmarContraseña" 
                class="form-control" 
                id="password2"
                autocomplete="new-password"
            >
        </div>

        <!-- ERRORES -->
        <?php if(!empty($_GET['error'])): ?>
            <div class="alert alert-danger mb-2">
                <?= $_GET['error'] ?>
            </div>
        <?php endif; ?>

        <!-- ÉXITO -->
        <?php if(!empty($_GET['success'])): ?>
            <div class="alert alert-success mb-2">
                ¡Registro exitoso! Ahora puedes iniciar sesión.
            </div>
        <?php endif; ?>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">CREAR CUENTA</button>
        </div>

    </form>
</div>

</body>
</html>