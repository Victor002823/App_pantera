<?php
// Vista pura: el Controller (RastreoController::mostrarPagina) ya validó el token,
// la liga y el estado de la orden antes de hacer require de este archivo.
// Aquí solo esperamos que $tokenParaVista y $ordenDataParaVista vengan definidas.

if (!isset($tokenParaVista)) {
    http_response_code(400);
    die('Acceso inválido a la vista');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta property="og:title" content="Rastreo en vivo · El Lince">
<meta property="og:description" content="Sigue en tiempo real tu mudanza <?php echo htmlspecialchars($destinoPreview); ?>. Toca para ver la ubicación.">
<meta property="og:image" content="https://app-pantera.onrender.com/img.php/v<?php echo time(); ?>.png">
<meta property="og:url" content="<?php echo htmlspecialchars($urlActual); ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Fletes y Mudanzas El Lince">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Rastreo en vivo · El Lince">
<meta name="twitter:description" content="Sigue en tiempo real tu mudanza.">
<meta name="twitter:image" content="https://app-pantera.onrender.com/icon-512.png">
<title>Rastreo · El Lince</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<style>
  :root{
    --navy: #0B1F3A;
    --navy-2: #123262;
    --blue: #2E5AAC;
    --blue-light: #6E93D6;
    --amber: #F2A93B;
    --amber-dark: #C97F13;
    --mist: #F5F7FA;
    --paper: #FFFFFF;
    --ink: #10203A;
    --ink-soft: #5B6B85;
    --line: #E3E8F0;
    --ok: #2F9E6E;
    --err: #C0392B;
    --radius: 22px;
  }
  *{ box-sizing:border-box; margin:0; padding:0; }
  body{
    font-family:'Inter', sans-serif;
    background: var(--mist);
    color: var(--ink);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:32px 16px;
  }
  @media (prefers-reduced-motion: reduce){
    *{ animation-duration:0.01ms !important; animation-iteration-count:1 !important; transition-duration:0.01ms !important; }
  }
  .device{
    width:100%;
    max-width:420px;
    background:var(--paper);
    border-radius:32px;
    overflow:hidden;
    box-shadow: 0 30px 60px -20px rgba(11,31,58,0.35), 0 2px 8px rgba(11,31,58,0.06);
    position:relative;
  }
    .map-hero{
    position: relative;
    height: 320px;
    background: var(--mist);
    overflow: hidden;
    isolation: isolate; /* Aisla el contexto de apilamiento para evitar bugs de renderizado */
  }
  #map{ 
    position: absolute; 
    inset: 0; 
    width: 100%; 
    height: 100%; 
    z-index: 1; 
  }
    .map-hero::after{
    content: "";
    position: absolute; 
    top: 0; left: 0; right: 0;
    height: 80px; /* Controla qué tan alto abarca la sombra superior */
    background: linear-gradient(180deg, rgba(4,12,26,0.6) 0%, rgba(4,12,26,0) 100%);
    pointer-events: none;
    z-index: 2;
  }

  .brand{ 
    position: absolute; 
    top: 16px; 
    left: 16px; 
    display: flex; 
    align-items: center; 
    gap: 8px; 
    z-index: 10; 
    transform: translateZ(0); /* Estabilidad de hardware */
  }
  .brand-mark{
    width: 34px; height: 34px; border-radius: 10px;
    background: rgba(11, 31, 58, 0.7); /* Fondo translúcido seguro contra parpadeos */
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    display: flex; align-items: center; justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.18);
    overflow: hidden;
  }
  .brand-mark img{
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .brand-name{ font-family:'Space Grotesk', sans-serif; font-weight:700; font-size:13px; color:#fff; letter-spacing:0.02em; }
  .brand-sub{ font-size:9px; color:rgba(255,255,255,0.55); letter-spacing:0.12em; text-transform:uppercase; font-family:'JetBrains Mono', monospace; }
  
  .live-badge{
    position: absolute; 
    top: 16px; 
    right: 16px; 
    z-index: 10; 
    display: flex; 
    align-items: center; 
    gap: 6px;
    background: rgba(11, 31, 58, 0.7); /* Fondo translúcido seguro contra parpadeos */
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    padding: 6px 10px 6px 8px;
    border-radius: 20px;
    transform: translateZ(0); /* Estabilidad de hardware */
  }

  .live-dot{
    width:7px; height:7px; border-radius:50%;
    background:#5CD98F;
    box-shadow:0 0 0 0 rgba(92,217,143,0.6);
    animation: pulse-dot 1.8s infinite;
  }
  .live-badge.detenido .live-dot{ background:#9AA5B5; animation:none; }
  @keyframes pulse-dot{
    0%{ box-shadow:0 0 0 0 rgba(92,217,143,0.55); }
    70%{ box-shadow:0 0 0 7px rgba(92,217,143,0); }
    100%{ box-shadow:0 0 0 0 rgba(92,217,143,0); }
  }
  .live-badge span{ font-family:'JetBrains Mono', monospace; font-size:10px; letter-spacing:0.08em; color:#fff; text-transform:uppercase; }
  .pin-home{
    width:34px; height:34px; border-radius:50%;
    background: var(--navy-2);
    border:3px solid #fff;
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 6px 14px rgba(0,0,0,0.28);
  }
  .pin-home svg{ width:15px; height:15px; }
  .pin-origin{
    width:14px; height:14px; border-radius:50%;
    background: var(--amber);
    border:2.5px solid #0B1F3A;
    box-shadow:0 3px 8px rgba(0,0,0,0.4);
  }
  .truck-chip{
    display:flex; align-items:center; gap:6px;
    background:#fff;
    padding:6px 10px 6px 6px;
    border-radius:20px;
    box-shadow:0 8px 18px rgba(0,0,0,0.3);
    white-space:nowrap;
  }
  .truck-chip .icon-circle{
    width:26px; height:26px; border-radius:50%;
    background: var(--blue);
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
  }
  .truck-chip .icon-circle svg{ width:15px; height:15px; }
  .truck-chip b{ font-size:11px; color:var(--navy); font-family:'JetBrains Mono', monospace; }
  .leaflet-container{ background: var(--mist); font-family:'Inter', sans-serif; }
  .leaflet-control-attribution{ font-size:8.5px !important; }
  .alert{
    display:none;
    align-items:flex-start; gap:10px;
    padding:12px 20px;
    border-bottom:1px solid var(--line);
  }
  .alert.mostrar{ display:flex; }
  .alert.ok{ background: linear-gradient(180deg,#EAFBF2,#DFF7EA); border-bottom-color:#BFEAD1; }
  .alert.err{ background: linear-gradient(180deg,#FDECEA,#FBDFDB); border-bottom-color:#F3C4BC; }
  .alert-icon{
    width:22px; height:22px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; margin-top:1px;
  }
  .alert.ok .alert-icon{ background:var(--ok); }
  .alert.err .alert-icon{ background:var(--err); }
  .alert-icon svg{ width:12px; height:12px; }
  .alert-text{ font-size:12.5px; line-height:1.4; }
  .alert-text b{ display:block; font-size:12.5px; }
  .alert.ok .alert-text b{ color:#1E7A50; }
  .alert.err .alert-text b{ color:#8C2E22; }
  .alert-text span{ color:#5B6B85; }
  .info{ padding:22px 22px 26px; }
  .order-row{ display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; }
  .order-id-label{ font-family:'JetBrains Mono', monospace; font-size:10px; letter-spacing:0.1em; text-transform:uppercase; color:var(--ink-soft); margin-bottom:3px; }
  .order-id{ font-family:'Space Grotesk', sans-serif; font-size:22px; font-weight:700; color:var(--navy); letter-spacing:0.01em; }
  .status-pill{ display:flex; align-items:center; gap:6px; background: rgba(46,90,172,0.09); color:var(--blue); padding:7px 13px; border-radius:20px; font-size:11.5px; font-weight:600; }
  .status-pill.ok{ background: rgba(47,158,110,0.12); color:var(--ok); }
  .status-pill.err{ background: rgba(192,57,43,0.1); color:var(--err); }
  .status-pill svg{ width:13px; height:13px; }
  .divider{ height:1px; background:var(--line); margin:16px 0; }
  .field{ display:flex; gap:14px; padding:12px 0; }
  .field-icon{ width:38px; height:38px; border-radius:12px; background: var(--mist); border:1px solid var(--line); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .field-icon svg{ width:18px; height:18px; color:var(--blue); }
  .field-label{ font-size:10.5px; text-transform:uppercase; letter-spacing:0.08em; color:var(--ink-soft); font-weight:600; margin-bottom:3px; }
  .field-value{ font-size:14.5px; font-weight:500; color:var(--ink); line-height:1.4; }
  .eta-value{ font-family:'JetBrains Mono', monospace; font-size:15px; font-weight:600; color:var(--amber-dark); }
  .progress-track{ height:6px; border-radius:6px; background:var(--line); margin-top:10px; overflow:hidden; position:relative; }
  .progress-fill{ position:absolute; left:0; top:0; bottom:0; width:5%; border-radius:6px; background: linear-gradient(90deg, var(--blue), var(--amber)); transition: width 0.6s ease; }
  .actions{ display:flex; gap:10px; margin-top:22px; }
  .btn{ flex:1; border:none; border-radius:14px; padding:14px 0; font-family:'Inter', sans-serif; font-weight:600; font-size:13.5px; cursor:pointer; transition: transform 0.15s ease, box-shadow 0.15s ease; display:flex; align-items:center; justify-content:center; gap:7px; }
  .btn:disabled{ opacity:0.45; cursor:not-allowed; }
  .btn:focus-visible{ outline:2px solid var(--blue); outline-offset:2px; }
  .btn-primary{ background: var(--navy); color:#fff; box-shadow: 0 8px 18px rgba(11,31,58,0.28); }
  .btn-primary:hover{ transform: translateY(-1px); box-shadow:0 10px 22px rgba(11,31,58,0.35); }
  .btn-ghost{ background: var(--mist); color: var(--navy); border:1px solid var(--line); }
  .btn-ghost:hover{ transform: translateY(-1px); background:#EEF1F6; }
  .btn svg{ width:15px; height:15px; }
</style>
</head>
<body>

<div class="device">

  <div class="map-hero">
    <div class="brand">
      <div class="brand-mark">
        <img src="https://app-pantera.onrender.com/icon-512.png" alt="Logo Transportes y Mudanzas Pantera">
      </div>
      <div>
        <div class="brand-name">El Lince</div>
        <div class="brand-sub">Live tracking</div>
      </div>
    </div>

    <div class="live-badge" id="liveBadge">
      <span class="live-dot"></span>
      <span id="liveText">En vivo</span>
    </div>

    <div id="map"></div>
  </div>

  <div class="alert" id="alertBox">
    <div class="alert-icon" id="alertIcon">
      <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><path d="M12 9v4M12 17h.01" stroke-linecap="round"/><path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" stroke-linejoin="round"/></svg>
    </div>
    <div class="alert-text">
      <b id="alertTitulo">Aviso</b>
      <span id="alertTexto"></span>
    </div>
  </div>

  <div class="info">
    <div class="order-row">
      <div>
        <div class="order-id-label">Pedido</div>
        <div class="order-id" id="ordenNumero">Cargando…</div>
      </div>
      <div class="status-pill" id="statusPill">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="1" y="7" width="13" height="10" rx="1.5"/><path d="M14 10h4l3 3v4h-7z"/></svg>
        <span id="statusTexto">Cargando</span>
      </div>
    </div>

    <div class="progress-track"><div class="progress-fill" id="progressFill"></div></div>

    <div class="divider"></div>

    <div class="field">
      <div class="field-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3" stroke-linecap="round"/></svg>
      </div>
      <div>
        <div class="field-label">ETA</div>
        <div class="eta-value" id="ordenEta">—</div>
      </div>
    </div>

    <div class="field">
      <div class="field-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
      </div>
      <div>
        <div class="field-label">Destino</div>
        <div class="field-value" id="ordenDestino">—</div>
      </div>
    </div>

    <div class="field" id="choferField" style="display:none;">
      <div class="field-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6" stroke-linecap="round"/></svg>
      </div>
      <div>
        <div class="field-label">Conductor</div>
        <div class="field-value" id="choferNombre">—</div>
      </div>
    </div>

    <div class="actions">
      <button class="btn btn-ghost" type="button" id="btnMensaje" disabled>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.38 8.5 8.5 0 0 1-4-1L3 20l1.12-5.5A8.5 8.5 0 1 1 21 11.5Z" stroke-linejoin="round"/></svg>
        Llamar
      </button>
      <button class="btn btn-primary" type="button" id="btnActualizar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-3-6.7" stroke-linecap="round"/><path d="M21 4v5h-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Actualizar
      </button>
    </div>
  </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
  const TOKEN = <?php echo json_encode($tokenParaVista, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
  const INITIAL_ORDER = <?php echo json_encode($ordenDataParaVista, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script src="/asset/js/rastreo-map.js?v=19"></script>
	
</body>
</html>
