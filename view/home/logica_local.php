<script>
(function () {
  const KEY = 'AIzaSyANTwTOjjKz4SYZRejFX2aWyP7xzRnv490';
  let timer;
  const ok = () => window.google && google.maps && google.maps.Map;

  function overlay() {
    const c = document.querySelector('.map-container');
    if (!c) return null;
    let o = document.getElementById('map-retry');
    if (!o) {
      c.style.position = 'relative';
      o = document.createElement('div');
      o.id = 'map-retry';
      o.style.cssText = 'position:absolute;inset:0;z-index:5;display:none;flex-direction:column;align-items:center;justify-content:center;gap:12px;background:#f0f0f0;font-family:sans-serif;color:#444;text-align:center;padding:16px';
      o.innerHTML = '<span id="map-retry-msg"></span><button id="map-retry-btn" type="button" style="width:auto;min-width:160px;height:auto;white-space:nowrap;padding:12px 24px;border:0;border-radius:8px;background:#000;color:#fff;font-weight:600;cursor:pointer">Recargar mapa</button>';
      c.appendChild(o);
      o.querySelector('#map-retry-btn').onclick = () => window.cargarMapaGoogle();
    }
    return o;
  }

  function mostrar(msg, btn) {
    const o = overlay();
    if (!o) return;
    o.style.display = 'flex';
    o.querySelector('#map-retry-msg').textContent = msg;
    o.querySelector('#map-retry-btn').style.display = btn ? 'inline-block' : 'none';
  }

  window.__mapaOk = function () {
    clearTimeout(timer);
    const o = document.getElementById('map-retry');
    if (o) o.style.display = 'none';
    initMap();
  };

  window.cargarMapaGoogle = function () {
    document.querySelectorAll('script[data-gmaps]').forEach(s => s.remove());
    if (ok()) return window.__mapaOk();
    mostrar('Cargando mapa...', false);
    const s = document.createElement('script');
    s.dataset.gmaps = '1';
    s.async = true;
    s.src = 'https://maps.googleapis.com/maps/api/js?key=' + KEY + '&libraries=places&callback=__mapaOk';
    s.onerror = () => { clearTimeout(timer); mostrar('No se pudo cargar el mapa', true); };
    clearTimeout(timer);
    timer = setTimeout(() => { if (!ok()) mostrar('El mapa tarda demasiado', true); }, 12000);
    document.head.appendChild(s);
  };

  document.addEventListener('DOMContentLoaded', () => window.cargarMapaGoogle());
})();
</script>
<script src="/view/home/js/api/logica_local.js?v=3"></script>
<script src="/view/home/js/api/logica_cargadores.js?v=7"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

