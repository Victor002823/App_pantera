/* --- NO MODIFIQUÉ LA LÓGICA DE LOS GRÁFICOS --- */
let filtroGlobal = 'week';
let startDateGlobal = new Date();
let endDateGlobal = new Date();

function generarDatos(n, min=100, max=1000){
  return Array.from({ length: n }, () => Math.floor(Math.random()*(max-min)+min));
}

function generarEtiquetas(filtro){
  if(filtro==='week') return ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
  if(filtro==='month') return ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  if(filtro==='year'){
    const anios = [];
    const currentYear = new Date().getFullYear();
    for(let i=0;i<5;i++){ anios.push(currentYear-4+i); }
    return anios;
  }
  return [];
}

function generarTooltipLabel(context){
  const index = context.dataIndex;
  const value = context.dataset.data[index];
  if(filtroGlobal==='week'){
    const fecha = new Date(startDateGlobal);
    fecha.setDate(startDateGlobal.getDate()+index);
    return context.dataset.label + ': $' + value + ' (' + fecha.toLocaleDateString('es-ES') + ')';
  } else if(filtroGlobal==='month'){
    const meses = generarEtiquetas('month');
    return context.dataset.label + ': $' + value + ' (' + meses[index] + ' ' + new Date().getFullYear() + ')';
  } else if(filtroGlobal==='year'){
    const anios = generarEtiquetas('year');
    return context.dataset.label + ': $' + value + ' (' + anios[index] + ')';
  }
  return context.dataset.label + ': $' + value;
}

const salesCtx = document.getElementById('salesChart').getContext('2d');
const cotCtx = document.getElementById('cotizacionesChart').getContext('2d');

let salesChart = new Chart(salesCtx,{
  type:'line',
  data:{ labels:[], datasets:[{label:'Ventas', data:[], borderColor:'#4e79a7', backgroundColor:'rgba(78,121,167,0.15)', fill:true, tension:0.4, pointRadius:5, pointBackgroundColor:'#4e79a7'}]},
  options:{ responsive:true,
    plugins:{
      legend:{ display:false },
      tooltip:{ mode:'index', intersect:false, callbacks:{ label:generarTooltipLabel } }
    },
    scales:{ 
      y:{ beginAtZero:true, grid:{ color:'rgba(0,0,0,0.05)' } },
      x:{ grid:{ display:false } }
    },
    animation:{ duration:1000, easing:'easeOutQuart' }
  }
});

let cotChart = new Chart(cotCtx,{
  type:'line',
  data:{ labels:[], datasets:[{label:'Cotizaciones', data:[], borderColor:'#f28e2b', backgroundColor:'rgba(242,142,43,0.15)', fill:true, tension:0.4, pointRadius:5, pointBackgroundColor:'#f28e2b'}]},
  options:{ responsive:true,
    plugins:{
      legend:{ display:false },
      tooltip:{ mode:'index', intersect:false, callbacks:{ label:generarTooltipLabel } }
    },
    scales:{ 
      y:{ beginAtZero:false, grid:{ color:'rgba(0,0,0,0.05)' } },
      x:{ grid:{ display:false } }
    },
    animation:{ duration:1000, easing:'easeOutQuart' }
  }
});

function actualizarGraficos(filtro='week'){
  filtroGlobal = filtro;
  const labels = generarEtiquetas(filtro);
  const n = labels.length;
  const sales = generarDatos(n,100,1000);
  const cot = generarDatos(n,2800,3200);

  salesChart.data.labels = labels;
  salesChart.data.datasets[0].data = sales;
  salesChart.update();

  cotChart.data.labels = labels;
  cotChart.data.datasets[0].data = cot;
  cotChart.update();

  document.getElementById('sales-max').textContent='Ventas máximas: $'+Math.max(...sales);
  document.getElementById('sales-min').textContent='Ventas mínimas: $'+Math.min(...sales);
  document.getElementById('cot-max').textContent='Cotización máxima: $'+Math.max(...cot);
  document.getElementById('cot-min').textContent='Cotización mínima: $'+Math.min(...cot);

  /* Opcional: actualizar totales de la tarjeta automáticamente (descomenta si quieres) */
  // document.getElementById('total-ventas-display').textContent = '$' + sales.reduce((a,b)=>a+b,0);
  // document.getElementById('total-cot-display').textContent = '$' + cot.reduce((a,b)=>a+b,0);
}

document.getElementById('filter-period').addEventListener('change',function(){
  actualizarGraficos(this.value);
});

document.getElementById('apply-range').addEventListener('click',function(){
  actualizarGraficos(document.getElementById('filter-period').value);
});

// Inicializar
actualizarGraficos('week');