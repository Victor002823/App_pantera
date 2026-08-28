const INTERVALO_MS = 12000;
let mapa, marcadorOrigen, marcadorDestino, marcadorChofer, lineaRuta = null, intervalId = null;

window.onerror = function(msg, url, line) {
  const box = document.getElementById('alertBox');
  if (box) {
    box.className = 'alert mostrar err';
    document.getElementById('alertTitulo').textContent = 'Error';
    document.getElementById('alertTexto').textContent = msg + ' (linea ' + line + ')';
  }
};

function iniciarMapa() {
  mapa = L.map('map', {
    zoomControl: false,
    attributionControl: true,
    scrollWheelZoom: false,
    dragging: true,
    tap: false
  }).setView([19.4326, -99.1332], 12);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(mapa);

  // Asegurar que Leaflet calcule bien el espacio y evite parpadeos en los elementos flotantes
  setTimeout(() => {
    if (mapa) mapa.invalidateSize();
  }, 250);
}

const iconoOrigen = L.divIcon({
  className: '',
  html: `
    <div style="
      background-color: #000000;
      width: 36px;
      height: 36px;
      border-radius: 50% 50% 50% 0;
      transform: rotate(-45deg);
      display: flex;
      justify-content: center;
      align-items: center;
      box-shadow: 0 3px 6px rgba(0,0,0,0.3);
    ">
      <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="
        width: 16px; 
        height: 16px; 
        transform: rotate(45deg);
      ">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
        <line x1="12" y1="22.08" x2="12" y2="12"></line>
      </svg>
    </div>
  `,
  iconSize: [34, 34],
  iconAnchor: [18, 36]
});

const iconoDestino = L.divIcon({
  className: '',
  html: '<div class="pin-home"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 11l9-8 9 8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 10v10h14V10" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 20v-6h6v6" stroke-linecap="round" stroke-linejoin="round"/></svg></div>',
  iconSize: [34, 34], iconAnchor: [17, 34]
});

function iconoChofer(numero) {
  return L.divIcon({
    className: '',
    html: '<div class="truck-chip"><div class="icon-circle"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="1" y="7" width="13" height="10" rx="1.5"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="6" cy="19" r="1.6" fill="#fff"/><circle cx="17.5" cy="19" r="1.6" fill="#fff"/></svg></div><b>' + (numero || '') + '</b></div>',
    iconSize: [0, 0], iconAnchor: [20, 15]
  });
}

function crearArcoCurvo(latlng1, latlng2, numPoints = 25) {
  const lat1 = latlng1[0], lng1 = latlng1[1];
  const lat2 = latlng2[0], lng2 = latlng2[1];
  
  const midLat = (lat1 + lat2) / 2;
  const midLng = (lng1 + lng2) / 2;
  
  const dLat = lat2 - lat1;
  const dLng = lng2 - lng1;
  
  const curvature = 0.2; 
  const ctrlLat = midLat - dLng * curvature;
  const ctrlLng = midLng + dLat * curvature;
  
  const points = [];
  for (let i = 0; i <= numPoints; i++) {
    const t = i / numPoints;
    const lat = Math.pow(1 - t, 2) * lat1 + 2 * (1 - t) * t * ctrlLat + Math.pow(t, 2) * lat2;
    const lng = Math.pow(1 - t, 2) * lng1 + 2 * (1 - t) * t * ctrlLng + Math.pow(t, 2) * lng2;
    points.push([lat, lng]);
  }
  return points;
}

function actualizarRuta(origenCoords, destinoCoords) {
  if (!origenCoords || !destinoCoords) return;
  
  const latlng1 = [origenCoords.lat, origenCoords.lng];
  const latlng2 = [destinoCoords.lat, destinoCoords.lng];
  
  const puntosArco = crearArcoCurvo(latlng1, latlng2);
  
  if (!lineaRuta) {
    lineaRuta = L.polyline(puntosArco, {
      color: '#E74C3C',
      weight: 3.5,
      dashArray: '6, 8',
      opacity: 0.85,
      interactive: false
    }).addTo(mapa);
  } else {
    lineaRuta.setLatLngs(puntosArco);
  }
}

function calcularProgreso(activityLog) {
  if (!activityLog) return 5;
  if (activityLog.deliveryTime) return 100;
  if (activityLog.arrivedTime) return 85;
  if (activityLog.pickedUpTime) return 60;
  if (activityLog.startTime) return 40;
  if (activityLog.assignedTime) return 20;
  return 5;
}

function obtenerTextoEstado(activityLog) {
  if (!activityLog) return 'Procesando';
  if (activityLog.deliveryTime) return 'Entregado';
  if (activityLog.failedDeliveryTime) return 'Entrega fallida';
  if (activityLog.arrivedTime) return 'Llegó a destino';
  if (activityLog.pickedUpTime) return 'En camino a destino';
  if (activityLog.startTime) return 'En camino a origen';
  if (activityLog.assignedTime) return 'Conductor asignado';
  return 'Orden creada';
}

function actualizarUI(data) {
  const liveBadge = document.getElementById('liveBadge');
  const liveText = document.getElementById('liveText');
  const alertBox = document.getElementById('alertBox');
  const statusPill = document.getElementById('statusPill');
  const statusTexto = document.getElementById('statusTexto');

  const orden = data.orden || {};

  if (data.terminado) {
    detenerPolling();
    if (liveBadge) liveBadge.classList.add('detenido');
    if (liveText) liveText.textContent = data.estado_liga === 'completado' ? 'Entregado' : 'No entregado';

    if (alertBox) {
      alertBox.className = 'alert mostrar ' + (data.estado_liga === 'completado' ? 'ok' : 'err');
      const alertTitulo = document.getElementById('alertTitulo');
      const alertTexto = document.getElementById('alertTexto');
      if (alertTitulo) alertTitulo.textContent = data.estado_liga === 'completado' ? 'Servicio entregado' : 'Entrega no completada';
      if (alertTexto) alertTexto.textContent = data.mensaje_cierre || (data.estado_liga === 'completado' ? 'Gracias por confiar en El Lince.' : 'Contacta a El Lince para más información.');
    }

    if (statusPill) statusPill.className = 'status-pill ' + (data.estado_liga === 'completado' ? 'ok' : 'err');
    if (statusTexto) statusTexto.textContent = data.estado_liga === 'completado' ? 'Entregado' : 'No entregado';

    setTimeout(() => {
      window.location.href = '/';
    }, 4000);
  } else {
    if (statusPill) statusPill.className = 'status-pill';
    if (statusTexto) statusTexto.textContent = obtenerTextoEstado(orden.activityLog);
  }

  const ordenNum = document.getElementById('ordenNumero');
  const ordenEta = document.getElementById('ordenEta');
  const ordenDestino = document.getElementById('ordenDestino');
  const progressFill = document.getElementById('progressFill');

  if (ordenNum) ordenNum.textContent = orden.numero || '—';
  if (ordenEta) ordenEta.textContent = orden.etaTime || 'Por confirmar';
  if (ordenDestino) ordenDestino.textContent = (orden.destino && orden.destino.direccion) || '—';
  
  const origenEl = document.getElementById('ordenOrigen');
  if (origenEl) {
    origenEl.textContent = (orden.origen && orden.origen.direccion) || '—';
  }

  if (progressFill) progressFill.style.width = calcularProgreso(orden.activityLog) + '%';
  document.title = 'Seguimiento · ' + (orden.numero || 'El Lince');

  const btnActualizar = document.getElementById('btnActualizar');
  if (btnActualizar) {
    btnActualizar.onclick = () => consultarEstado(true);
  }

  const btnMensaje = document.getElementById('btnMensaje');
  const choferField = document.getElementById('choferField');
  const choferNombre = document.getElementById('choferNombre');
  const choferTelefono = document.getElementById('choferTelefono');

  if (data.chofer) {
    if (choferField) choferField.style.display = 'flex';
    if (choferNombre) choferNombre.textContent = data.chofer.nombre || 'Conductor asignado';
    if (choferTelefono) choferTelefono.textContent = data.chofer.telefono || '';

    if (data.chofer.telefono && btnMensaje) {
      btnMensaje.disabled = false;
      btnMensaje.onclick = () => { window.location.href = 'tel:' + data.chofer.telefono; };
    }
  } else {
    if (choferField) choferField.style.display = 'none';
    if (btnMensaje) btnMensaje.disabled = true;
  }

  if (data.chofer && data.chofer.lat && data.chofer.lng) {
    const pos = [data.chofer.lat, data.chofer.lng];
    if (!marcadorChofer) {
      marcadorChofer = L.marker(pos, { icon: iconoChofer(orden.numero), interactive: false }).addTo(mapa);
    } else {
      marcadorChofer.setLatLng(pos);
      marcadorChofer.setIcon(iconoChofer(orden.numero));
    }
  }

  if (orden.origen && orden.origen.lat && !marcadorOrigen) {
    marcadorOrigen = L.marker([orden.origen.lat, orden.origen.lng], { icon: iconoOrigen, interactive: false }).addTo(mapa);
  }
  if (orden.destino && orden.destino.lat && !marcadorDestino) {
    marcadorDestino = L.marker([orden.destino.lat, orden.destino.lng], { icon: iconoDestino, interactive: false }).addTo(mapa);
  }

  if (orden.origen && orden.origen.lat && orden.destino && orden.destino.lat) {
    actualizarRuta(orden.origen, orden.destino);
  }

  ajustarVista();
}

function ajustarVista() {
  const puntos = [marcadorChofer, marcadorOrigen, marcadorDestino].filter(Boolean).map(m => m.getLatLng());
  if (puntos.length && mapa) mapa.fitBounds(L.latLngBounds(puntos), { padding: [50, 50], maxZoom: 15 });
}

async function consultarEstado(manual = false) {
  const btnActualizar = document.getElementById('btnActualizar');
  let iconoSvg = null;

  if (manual && btnActualizar) {
    iconoSvg = btnActualizar.querySelector('svg');
    if (iconoSvg) {
      iconoSvg.style.transition = 'transform 1s linear';
      iconoSvg.style.transform = 'rotate(360deg)';
      iconoSvg.style.animation = 'spin 0.8s linear infinite';
    }
  }

  try {
    const res = await fetch(`api_rastreo.php?token=${encodeURIComponent(TOKEN)}`);
    if (res.status === 410) {
      detenerPolling();
      const liveBadge = document.getElementById('liveBadge');
      if (liveBadge) liveBadge.classList.add('detenido');
      const liveText = document.getElementById('liveText');
      if (liveText) liveText.textContent = 'Expirado';
      const alertBox = document.getElementById('alertBox');
      if (alertBox) {
        alertBox.className = 'alert mostrar err';
        document.getElementById('alertTitulo').textContent = 'Liga expirada';
        document.getElementById('alertTexto').textContent = 'Esta liga de rastreo ya no está disponible.';
      }
      setTimeout(() => {
        window.location.href = '/';
      }, 4000);
      return;
    }
    if (!res.ok) throw new Error('Respuesta no valida: ' + res.status);
    actualizarUI(await res.json());
  } catch (err) {
    console.error('Error al consultar rastreo:', err);
  } finally {
    if (manual && btnActualizar && iconoSvg) {
      setTimeout(() => {
        iconoSvg.style.animation = '';
        iconoSvg.style.transform = 'none';
      }, 500);
    }
  }
}

function detenerPolling() { if (intervalId) clearInterval(intervalId); }

if (!document.getElementById('spinner-style')) {
  const style = document.createElement('style');
  style.id = 'spinner-style';
  style.innerHTML = `@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`;
  document.head.appendChild(style);
}

iniciarMapa();
consultarEstado(false);
intervalId = setInterval(() => consultarEstado(false), INTERVALO_MS);
