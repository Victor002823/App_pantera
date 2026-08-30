        let map;
        let directionsService;
        let directionsRenderer;
        let addressInputs = [];
        let distance = 0;  // Distancia de la ruta

        function initMap() {
  map = new google.maps.Map(document.getElementById("map"), {
    center: { lat: 19.432608, lng: -99.133209 },
    zoom: 11,
    mapTypeId: 'terrain',
    disableDefaultUI: true,
    fullscreenControl: true,
    styles: [
      { elementType: "geometry", stylers: [{ color: "#F0F0F0" }] },
      { elementType: "labels.icon", stylers: [{ visibility: "off" }] },
      { elementType: "labels.text.fill", stylers: [{ color: "#616161" }] },
      { elementType: "labels.text.stroke", stylers: [{ color: "#f5f5f5" }] },
      { featureType: "administrative.land_parcel", stylers: [{ visibility: "off" }] },
      { featureType: "poi", stylers: [{ visibility: "off" }] },
      { featureType: "road", elementType: "geometry", stylers: [{ color: "#ffffff" }] },
      { featureType: "road.arterial", elementType: "labels.text.fill", stylers: [{ color: "#757575" }] },
      { featureType: "road.highway", elementType: "geometry", stylers: [{ color: "#dadada" }] },
      { featureType: "road.highway", elementType: "labels.text.fill", stylers: [{ color: "#616161" }] },
      { featureType: "landscape.natural", elementType: "geometry", stylers: [{ color: "#D4FCD4" }] },
      { featureType: "road.local", elementType: "labels.text.fill", stylers: [{ color: "#9e9e9e" }] },
      { featureType: "transit", stylers: [{ visibility: "off" }] },
      { featureType: "water", elementType: "geometry", stylers: [{ color: "#DBFBFF" }] }
    ]
  });

  directionsService = new google.maps.DirectionsService();
  directionsRenderer = new google.maps.DirectionsRenderer({
    polylineOptions: { strokeColor: "#000000", strokeOpacity: 0.9, strokeWeight: 6 }
  });
  directionsRenderer.setMap(map);

  // Inicializar servicio de autocompletado puro de Google (sin diseño predeterminado)
  autocompleteService = new google.maps.places.AutocompleteService();
  sessionToken = new google.maps.places.AutocompleteSessionToken();

  initInputs();
}

// Función genérica para conectar cualquier input al AutocompleteService con diseño propio
// Función genérica para conectar cualquier input al AutocompleteService con diseño Tailwind
function setupCustomAutocomplete(inputElement, onSelectCallback) {
  if (!inputElement) return;

  const parent = inputElement.parentElement;
  if (getComputedStyle(parent).position === 'static') {
    parent.style.position = 'relative';
  }

  const resultsList = document.createElement("ul");
  resultsList.className = "custom-autocomplete-results";
  resultsList.style.display = "none";
  parent.appendChild(resultsList);

  const cdmxBounds = new google.maps.LatLngBounds(
    new google.maps.LatLng(19.1904, -99.3642),
    new google.maps.LatLng(19.5928, -98.9405)
  );

  inputElement.addEventListener("input", function () {
    const query = inputElement.value.trim();

    if (!query) {
      resultsList.style.display = "none";
      resultsList.innerHTML = "";
      return;
    }

    autocompleteService.getPlacePredictions(
      {
        input: query,
        sessionToken: sessionToken,
        bounds: cdmxBounds,
        componentRestrictions: { country: "mx" }
      },
      (predictions, status) => {
        resultsList.innerHTML = "";
        if (status === google.maps.places.PlacesServiceStatus.OK && predictions) {
          resultsList.style.display = "block";

          predictions.forEach((prediction) => {
            const li = document.createElement("li");
            li.className = "custom-autocomplete-item";
            
            // Estructura visual limpia y profesional estilo Tailwind UI
            li.innerHTML = `
              <div class="icon-wrapper">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </div>
              <div class="text-container">
                <span class="main-text">${prediction.structured_formatting.main_text}</span>
                <span class="secondary-text">${prediction.structured_formatting.secondary_text || ''}</span>
              </div>
            `;

            li.addEventListener("click", () => {
              inputElement.value = prediction.description;
              resultsList.style.display = "none";
              resultsList.innerHTML = "";
              sessionToken = new google.maps.places.AutocompleteSessionToken();

              if (onSelectCallback) onSelectCallback(prediction);
            });

            resultsList.appendChild(li);
          });
        } else {
          resultsList.style.display = "none";
        }
      }
    );
  });

  document.addEventListener("click", (e) => {
    if (!parent.contains(e.target)) {
      resultsList.style.display = "none";
    }
  });



  // Ocultar lista si se hace clic fuera
  document.addEventListener("click", (e) => {
    if (!parent.contains(e.target)) {
      resultsList.style.display = "none";
    }
  });
}

function initInputs() {
  const input1 = document.getElementById("input1");
  const input2 = document.getElementById("input2");
  const input3 = document.getElementById("input3");
  const input4 = document.getElementById("input4");

  const origen = document.getElementById("copia2") || document.getElementById("origen");
  const destino = document.getElementById("copia3") || document.getElementById("destino");

  // Configurar cada input con el diseño personalizado
  setupCustomAutocomplete(input1);
  setupCustomAutocomplete(input4);

  setupCustomAutocomplete(input2, (prediction) => {
    if (origen) origen.value = prediction.description;
  });
  input2?.addEventListener("input", () => { if (origen) origen.value = input2.value; });

  setupCustomAutocomplete(input3, (prediction) => {
    if (destino) destino.value = prediction.description;
  });
  input3?.addEventListener("input", () => { if (destino) destino.value = input3.value; });
}

// Agregar nueva dirección dinámica con el nuevo diseño
function addAddress() {
  const container = document.getElementById("extra-addresses");
  if (!container) return;

  const newAddressItem = document.createElement("div");
  newAddressItem.classList.add("input-group");
  newAddressItem.style.position = "relative"; // Vital para posicionar las sugerencias
  
  newAddressItem.innerHTML = `
    <input class="address-input" type="text" placeholder="Ingresa una dirección" autocomplete="off">
    <span class="remove-button" onclick="removeAddress(this)"><i class="fa fa-trash"></i></span>
  `;

  container.appendChild(newAddressItem);
  const newInput = newAddressItem.querySelector('.address-input');
  addressInputs.push(newInput);

  // Aplicar el autocompletado personalizado al nuevo input generado
  setupCustomAutocomplete(newInput);
}

function removeAddress(element) {
  const inputGroup = element.closest('.input-group') || element.parentElement;
  const input = inputGroup.querySelector('input');

  const index = addressInputs.indexOf(input);
  if (index > -1) addressInputs.splice(index, 1);

  inputGroup.remove();
}


function clearInput(inputId) {
  const input = document.getElementById(inputId);
  if (input) input.value = '';
}

function toggleInputs() {
  const group1 = document.getElementById("input1-group");
  const group4 = document.getElementById("input4-group");
  if (group1) group1.classList.toggle("hidden");
  if (group4) group4.classList.toggle("hidden");
}

        function calculateRoute() {
    // Seleccionamos los inputs visibles (sin el "hidden" si lo manejas con toggle)
    const inputs = Array.from(document.querySelectorAll(".address-input"))
        .filter(inp => inp.value.trim() !== "");

    // Origen → siempre el primero
    const origin = document.getElementById("input1").value || inputs[0].value;

    // Destino → siempre el input con la clase .end (input4)
    const destination = document.querySelector(".address-input.end").value;

    // Waypoints → todos los que estén entre medio, excepto origen y destino
    const waypoints = inputs
        .filter(inp => inp.id !== "input1" && !inp.classList.contains("end"))
        .map(inp => ({
            location: inp.value,
            stopover: true
        }));

    if (!origin || !destination) {
        alert("Por favor, ingresa una dirección de origen y una dirección final.");
        return;
    }

    const request = {
  origin: origin,
  destination: destination,
  waypoints: waypoints,
  travelMode: google.maps.TravelMode.DRIVING,
  avoidTolls: (servicio === "local")  // 🚦 true si local, false si foráneo
};

    directionsService.route(request, function (response, status) {
        if (status === google.maps.DirectionsStatus.OK) {
            directionsRenderer.setDirections(response);

            const route = response.routes[0].legs.reduce((acc, leg) => {
                acc.distance += leg.distance.value;
                acc.duration += leg.duration.value;
                return acc;
            }, { distance: 0, duration: 0 });

            distance = route.distance / 1000;  // km
            distance += 9;  // Agregar 9 km extras

            document.getElementById("distance").innerHTML = `<i class="fa-solid fa-truck"></i> Total: ${Math.round(distance)} km`;
            document.getElementById("duration").innerHTML = `<i class="fa-solid fa-clock"></i> Tiempo: ${Math.round(route.duration / 60)} min`;

            // Actualiza tus cálculos personalizados
            updateResults();
        } else {
            alert("No se pudo calcular la ruta. Verifica las direcciones.");
        }
    });
}


let mode = "";           // flete o mudanza
let servicio = "local";  // local o foráneo (por defecto local)
let camionetaCosts = {700: 0, 1500: 0, 3500: 0};
// ---- FUNCIONES DE CONTROL ----

// cambia entre flete / mudanza
function setModeFromSelect() {
  const select = document.getElementById("tipo-servicio");
  mode = select.value.toLowerCase(); // "flete" o "mudanza"
  console.log("Modo actual:", mode);
  updateResults();
}

// cambia entre local / foráneo
function activarModo(modo) {
  servicio = modo.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
  console.log("Servicio seleccionado:", servicio);
  updateResults();
}

// ---- LÓGICA PRINCIPAL ----
function updateResults() {
  if (servicio === "local") {
    updateResultsLocal();
  } else if (servicio === "metropolitano") {
    updateResultsMetropolitano();
  } else if (servicio === "foraneo") {
    updateResultsForaneo();
  } else {
    console.warn("⚠️ Servicio desconocido:", servicio);
  }
}

// ---- LÓGICA LOCAL ----
function updateResultsLocal() {
  console.log("✅ Ejecutando lógica LOCAL");

  mostrarTarjetas();

  if (mode === 'flete') {
    camionetaCosts[700] = (12 * distance + 300);
    camionetaCosts[1500] = (18 * distance + 450);
  } else { // mudanza
    camionetaCosts[700] = (15 * distance + 350);
    camionetaCosts[1500] = (21 * distance + 650);
  }

  camionetaCosts[3500] = ((distance / 6) * 28 + 2500) * 2 + 500;
  mostrarResultados();
}

// ---- LÓGICA METROPOLITANO ----
function updateResultsMetropolitano() {
  console.log("✅ Ejecutando lógica METROPOLITANO");

  mostrarTarjetas();

  if (mode === 'flete') {
    camionetaCosts[700] = (15 * distance + 350);
    camionetaCosts[1500] = (18 * distance + 450);
  } else { // mudanza
    camionetaCosts[700] = (18 * distance + 400);
    camionetaCosts[1500] = (25 * distance + 850);
  }

  camionetaCosts[3500] = ((distance / 6) * 28 + 2500) * 2 + 500;
  mostrarResultados();
}

// ---- LÓGICA FORÁNEO ----
function updateResultsForaneo() {
  console.log("✅ Ejecutando lógica FORÁNEO");

  mostrarTarjetas();

  if (mode === 'flete') {
    camionetaCosts[700] = ((distance / 10) * 25 + 500) * 3 + 500;
    camionetaCosts[1500] = ((distance / 8) * 28 + 800) * 3 + 700;
  } else { // mudanza
    camionetaCosts[700] = ((distance / 10) * 25 + 700) * 3 + 500;
    camionetaCosts[1500] = ((distance / 8) * 25 + 1200) * 3 + 700;
  }

  camionetaCosts[3500] = ((distance / 6) * 27 + 2500) * 3 + 500;
  mostrarResultados();
}

// ---- UTILIDADES ----
function mostrarTarjetas() {
  if (distance > 0) {
    const tarjetas = document.getElementById("tarjetas");
    tarjetas.style.display = "grid";
    document.getElementById("main-buttons").style.display = "flex";
    document.getElementById("nextButtonContainer").style.display = "block";
    document.getElementById("map").scrollIntoView({ behavior: "smooth", block: "start" });

    setTimeout(() => {
      const cards = tarjetas.querySelectorAll(".camioneta-card");
      cards.forEach((card, index) => {
        setTimeout(() => card.classList.add("show"), index * 200);
      });
    }, 600);
  } else {
    document.getElementById("tarjetas").style.display = "none";
  }
}

function mostrarResultados() {
  document.getElementById("block1-results").innerText =
    `Costo: $${camionetaCosts[700].toFixed(2)} MXN`;
  document.getElementById("block2-results").innerText =
    `Costo: $${camionetaCosts[1500].toFixed(2)} MXN`;
  document.getElementById("block3-results").innerText =
    `Costo: $${camionetaCosts[3500].toFixed(2)} MXN`;
  updateTotal();
}

function showModePopover() {
  // Evitar duplicados
  if(document.getElementById("popover-eleccion")) return;

  const mainButtons = document.querySelector(".main-buttons");
  const popover = document.createElement("div");
  popover.id = "popover-eleccion";
  popover.className = "popover-custom";
  popover.innerText = "Debes elegir entre Flete o Mudanza";
  
  // Insertar el popover dentro del contenedor
  mainButtons.style.position = "relative";
  mainButtons.appendChild(popover);

  // Ocultar automáticamente después de 5 segundos
  setTimeout(() => {
    if(popover) popover.remove();
  }, 5000);
}
// Seleccionar camioneta
function selectCamioneta(card) {

  const input = card.querySelector('input[name="camioneta"]');

  if (!input) return;

  // Alternar estado
  input.checked = !input.checked;

  // Alternar estilo visual
  card.classList.toggle('selected', input.checked);

  updateTotal();
}

// 🚀 Nueva función: marcar una camioneta por defecto
function seleccionarPorDefecto(valor){
  const input = document.querySelector(`input[name="camioneta"][value="${valor}"]`);
  if(input){
    const card = input.closest(".camioneta-card");
    selectCamioneta(card);
  }
}

// Al cargar la página, seleccionamos la predeterminada
document.addEventListener("DOMContentLoaded", () => {

  document.querySelectorAll('input[name="camioneta"]:checked')
    .forEach(input => {
      input.closest('.camioneta-card').classList.add('selected');
    });

  updateTotal();
});

        // Función para asegurar que solo un interruptor esté activado
        function toggleSwitch(activatedId, otherId, block) {
    const activated = document.getElementById(activatedId);
    const other = document.getElementById(otherId);

    if (block === 'block1') {
        if (activated.checked) {
            other.checked = false;
        }
    } else if (block === 'block2') {
        if (activated.checked) {
            other.checked = false;
        }
    }

    // 👉 Scroll si el interruptor fue activado
    if (activated.checked) {
        const contenedor = document.getElementById(`intensidad-${block}`); // Usa un ID diferente por bloque
        if (contenedor) {
            contenedor.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });
        }
    }

    updateResults();
}
const intervalos = new Map();

    function cambiarValor(cambio, boton) {
  const container = boton.parentElement;
  const input = container.querySelector('.valor-numerico');
  let valorActual = parseInt(input.value);

  if (isNaN(valorActual)) {
    valorActual = 0;
  }

  // Evita restar por debajo de 0
  if (cambio < 0 && valorActual <= 0) return;

  input.value = valorActual + cambio;

  // ✅ Llamamos a la función para recalcular el costo cada vez que cambia
  updateCargadoresCost();
}

    function startChanging(cambio, boton) {
      cambiarValor(cambio, boton);
      const intervalId = setInterval(() => cambiarValor(cambio, boton), 100);
      intervalos.set(boton, intervalId);
    }

    function stopChanging(boton) {
      const intervalId = intervalos.get(boton);
      if (intervalId) {
        clearInterval(intervalId);
        intervalos.delete(boton);
      }
    }
// Variable global para costo de cargadores


// Mostrar/ocultar bloque cargadores
// updateCargadoresCost: calcula el costo total de cargadores segun inputs y slider
function calcularCostoPisos(pisos, intensidad) {

    if (pisos <= 2) return 0;

    let costo = 0;

    let base, incremento;

    switch (intensidad) {
        case 0: // Fácil
            base = 25;
            incremento = 10;
            break;

        case 1: // Medio
            base = 50;
            incremento = 15;
            break;

        case 2: // Difícil
            base = 75;
            incremento = 25;
            break;
    }

    for (let piso = 3; piso <= pisos; piso++) {
        costo += base + ((piso - 3) * incremento);
    }

    return costo;
}

function updateCargadoresCost() {
  // Obtienes los valores
  const num = parseInt(document.getElementById('numCargadores')?.value) || 0;
  const pisosSubir = parseInt(document.getElementById('pisosSubir')?.value) || 0;
  const pisosBajar = parseInt(document.getElementById('pisosBajar')?.value) || 0;
  const intensidad = parseInt(document.getElementById('intensidad')?.value, 10) || 0;

  // Actualizas el input "totales" con el número de cargadores
  document.getElementById('cargadoresinput').value = num;

  // costo por piso según intensidad
  let costoPorPiso = intensidad === 1 ? 50 : (intensidad === 2 ? 75 : 25);

  

  if (num > 0) {
    const costoCargador = 300;
    const costoAdicionalSubir = calcularCostoPisos(pisosSubir, intensidad);
    const costoAdicionalBajar = calcularCostoPisos(pisosBajar, intensidad);
    const costoTotalAdicional = (costoAdicionalSubir + costoAdicionalBajar) * num;
    cargadoresCost = (costoCargador * num) + costoTotalAdicional;

    const el = document.getElementById('cargadoresCostResult');
    if (el) el.innerHTML = `Costo : $${cargadoresCost.toFixed(2)} MXN`;
  } else {
    const el = document.getElementById('cargadoresCostResult');
    if (el) el.innerHTML = `Por favor, ingresa todos los valores correctamente.`;
  }
const inputCostoTotal = document.getElementById('maniobrainput');
  if (inputCostoTotal) inputCostoTotal.value = cargadoresCost.toFixed(2);
    
  updateTotal(); // actualiza resumen si existe
}

// toggleCargadores: muestra/oculta las opciones
function toggleCargadores() {
  const options = document.getElementById('cargadoresOptions');
  if (!options) return;
  options.style.display = document.getElementById('cargadoresSwitch').checked ? 'block' : 'none';
}

// Slider: color y texto dinámico
(function(){
  const slider = document.getElementById('intensidad');
  const resultado = document.getElementById('resultado');
  if (!slider) return;
  const niveles = ['Fácil','Medio','Difícil'];
  const colores = ['green','#CF7C04','#8A1500'];

  function actualizarBarra() {
    const v = parseInt(slider.value, 10);
    if (resultado) resultado.textContent = `Intensidad seleccionada: ${niveles[v]}`;
    const color = colores[v];
    const pct = (v / 2) * 100;
    slider.style.background = `linear-gradient(to right, ${color} 0%, ${color} ${pct}%, #ddd ${pct}%, #ddd 100%)`;
    slider.style.setProperty('--thumb-color', color);
  }
  
  slider.addEventListener('input', function() {
    actualizarBarra();
    updateCargadoresCost();
  });

  // inicializa visual del slider
  actualizarBarra();
})();


// Slider dinámico (colores según nivel)

// Mostrar el popup



 