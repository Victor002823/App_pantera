<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control - El Lince</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	
<style>.btn-fingerprint{width:40px;height:45px;border-radius:50%;padding:0;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin:auto;background-color:#000;border:none;transition:transform .2s,box-shadow .2s}.btn-fingerprint:hover{transform:scale(1.1);box-shadow:0 0 15px rgba(0,0,0,.3)}</style>
</head>
<body>

<div class="fondo-login" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
    <div class="titulo"></div>

    <form action="view/home/verificar.php" method="POST" class="col-11 col-sm-8 col-md-4 p-4 shadow bg-white rounded login" autocomplete="off" style="display:none;">
        <div class="mb-3">
            <label for="inputCorreo" class="form-label">Usuario</label>
            <input type="email" name="correo" class="form-control" id="inputCorreo" aria-describedby="emailHelp" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <div class="position-relative">
                <input type="password" name="contraseña" class="form-control" id="password">
                <button type="button" onclick="mostrarContre()" class="btn btn-sm position-absolute end-0 top-50 translate-middle-y me-2" style="background:none; border:none; z-index:10;">
                    <i id="eyepassword" class="fa-solid fa-eye text-muted"></i>
                </button>
            </div>
        </div>
                
        <div class="d-grid gap-2" style="margin-top:10px;">
            <button type="button" id="btnCambiarUsuario" style="display:none; border-radius:12px;" class="btn btn-warning">
                Cambiar Usuario
            </button>
        </div>    

        <?php if(!empty($_GET['error'])): ?>
            <div id="alertError" style="margin: auto;" class="alert alert-danger mb-2" role="alert">
                <?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="d-grid gap-2 mt-4">
            <button type="submit" id="btnAcceder" class="btn btn-dark">Acceder</button>

            <div class="d-grid gap-2" style="margin-top:10px;">
                <button type="button" id="btnRegistrar" style="display:none;" class="btn btn-primary">
                    Registrar Huella
                </button>
            </div>

            <div class="d-grid gap-2" style="margin-top:10px;">
                <button type="button" id="btnHuella" class="btn btn-success btn-fingerprint">
                    <i class="fa-solid fa-fingerprint fa-2x"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<h1 id="usuario" style="position:absolute; top:10px; right:85px; cursor:pointer; display:none; align-items:center; gap:5px; font-size: 1.2rem;">
    <i class="fa fa-user-circle" aria-hidden="true"></i>
    <span id="nombreUsuario">Asesor</span>
    <i class="fa-solid fa-location-dot" id="markerIcon" style="display:none; color:red;"></i>
</h1>
<script src="/view/home/funciones_login.js"></script>

</body>
</html>
