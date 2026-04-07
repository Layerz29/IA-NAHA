// Auth
const _local = ['localhost','127.0.0.1'].includes(window.location.hostname);
const API = window.location.origin + (_local ? '/IA-NAHA/Application/api' : '/IA-NAHA/Application/api');
(async function loadUser() {
  let name  = localStorage.getItem('naha_prenom') || sessionStorage.getItem('naha_prenom') || '';
  let email = localStorage.getItem('naha_email')  || '';

  if (!name) {
    try {
      const token = localStorage.getItem('naha_token') || sessionStorage.getItem('naha_token');
      if (token) {
        const res  = await fetch(`${API}/get_user.php`, { headers: { 'Authorization': `Bearer ${token}` } });
        const data = await res.json();
        if (data.prenom) {
          name  = data.prenom;
          email = data.email || '';
          localStorage.setItem('naha_prenom', name);
          localStorage.setItem('naha_email',  email);
        }
      }
    } catch(e) {}
  }

  if (name)  { document.getElementById('user-name').textContent  = name; document.getElementById('user-avatar').textContent = name[0].toUpperCase(); }
  if (email) { document.getElementById('user-email').textContent = email; }
})();
function logout() {
  localStorage.clear(); sessionStorage.clear();
  window.location.href = 'login.html';
}

// Configuration globale
const C = {
  purple:'#4f46e5', purpleL:'rgba(79,70,229,.15)',
  green: '#10b981', greenL: 'rgba(16,185,129,.15)',
  amber: '#f59e0b', amberL: 'rgba(245,158,11,.15)',
  pink:  '#ec4899', pinkL:  'rgba(236,72,153,.15)',
  teal:  '#06b6d4', tealL:  'rgba(6,182,212,.15)',
  red:   '#ef4444', redL:   'rgba(239,68,68,.15)',
  sky:   '#0ea5e9', lime:'#84cc16', slate:'#94a3b8',
};
const FONT = { family:"'Montserrat', sans-serif", weight:'700' };
Chart.defaults.font = { family:"'Montserrat', sans-serif", size:11 };
Chart.defaults.color = '#6b7280';

// Données DataViz et Notebooks
const stressData = [7.62, 7.46, 7.54, 7.56, 7.34, 6.98, 6.70, 6.36, 6.35, 6.27];

const DS = {
  sedSport: { labels:['Sportifs retenus', 'Sédentaires exclus'], data:[2011, 989], colors:[C.green, C.slate] },
  
  corrFake: {
    labels: ['Qualité du sommeil', 'Niveau de stress', 'Fréquence cardiaque', 'Activité physique'],
    data: [0.88, -0.81, -0.51, 0.01],
    colors: [C.red, C.red, C.amber, C.slate]
  },
  
  corrReal: { 
    labels: ['Niveau de stress', 'Âge', 'Hydratation', 'IMC', 'Pas / jour', 'Pression artérielle'], 
    data: [-0.413, -0.027, -0.037, -0.019, -0.011, 0.032], 
    colors: [C.red, C.slate, C.slate, C.slate, C.slate, C.slate] 
  },

  stressSleep: {
    labels: ['Niveau 1', 'Niveau 2', 'Niveau 3', 'Niveau 4', 'Niveau 5', 'Niveau 6', 'Niveau 7', 'Niveau 8', 'Niveau 9', 'Niveau 10'],
    data: stressData,
    colors: stressData.map(v => v > 7.5 ? C.greenL : v > 7.0 ? C.amberL : v > 6.5 ? 'rgba(245,158,11,.35)' : C.redL),
    borders: stressData.map(v => v > 7.5 ? C.green : v > 7.0 ? C.amber : v > 6.5 ? '#f97316' : C.red)
  },

  bpSleep: {
    labels: [110, 115, 120, 125, 130, 135, 140],
    data: [7.08, 7.10, 7.11, 7.09, 7.12, 7.10, 7.13]
  },
  
  featureImp: {
    labels: ['Stress', 'Âge', 'IMC', 'Durée séance', 'Pas / jour', 'Hydratation', 'Genre', 'Intensité', 'Type activité', 'Statut tabac'],
    data:   [0.15, 0.05, 0.04, 0.03, 0.03, 0.02, 0.02, 0.01, 0.01, 0.01],
    colors: [C.red, C.slate, C.slate, C.slate, C.slate, C.slate, C.slate, C.slate, C.slate, C.slate]
  }
};

// Initialisations
new Chart(document.getElementById('chart-sed-sport'), {
  type:'doughnut', 
  data:{ labels: DS.sedSport.labels, datasets:[{ data: DS.sedSport.data, backgroundColor: DS.sedSport.colors, borderWidth:2, borderColor:'#fff', hoverOffset:8 }] },
  options:{ responsive:true, cutout:'65%', plugins:{ legend:{ position:'bottom', labels:{font:FONT, boxWidth:12, padding:15} } } }
});

new Chart(document.getElementById('chart-corr-fake'), {
  type:'bar', 
  data:{ labels: DS.corrFake.labels, datasets:[{ data: DS.corrFake.data, backgroundColor: DS.corrFake.colors.map(c=>c+'cc'), borderWidth:1.5, borderRadius:4 }] },
  options:{ indexAxis:'y', responsive:true, plugins:{ legend:{display:false} }, scales:{ x:{ min:-1, max:1, grid:{color:'#f1f5f9'} }, y:{grid:{display:false}, ticks:{font:FONT}} } }
});

new Chart(document.getElementById('chart-corr-real'), {
  type:'bar', 
  data:{ labels: DS.corrReal.labels, datasets:[{ data: DS.corrReal.data, backgroundColor: DS.corrReal.colors.map(c=>c+'cc'), borderWidth:1.5, borderRadius:4 }] },
  options:{ indexAxis:'y', responsive:true, plugins:{ legend:{display:false} }, scales:{ x:{ min:-0.5, max:0.1, grid:{color:'#f1f5f9'} }, y:{grid:{display:false}, ticks:{font:FONT}} } }
});

new Chart(document.getElementById('chart-stress-sleep'), {
  type:'bar', 
  data:{ labels: DS.stressSleep.labels, datasets:[{ label:'Sommeil (h)', data:DS.stressSleep.data, backgroundColor:DS.stressSleep.colors, borderColor:DS.stressSleep.borders, borderWidth:2, borderRadius:6 }] },
  options:{ responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ min:6.0, max:8.2, grid:{color:'#f1f5f9'}, ticks:{font:FONT, callback:v=>v.toFixed(1)+'h'} }, x:{grid:{display:false}, ticks:{font:FONT}} } }
});

new Chart(document.getElementById('chart-bp-sleep'), {
  type:'line',
  data:{ labels: DS.bpSleep.labels, datasets:[{ label:'Sommeil (h)', data:DS.bpSleep.data, borderColor:C.purple, backgroundColor:C.purpleL, fill:true, tension:0.1, pointRadius:4 }] },
  options:{ responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ min:6.5, max:7.5, grid:{color:'#f1f5f9'}, ticks:{font:FONT, callback:v=>v.toFixed(1)+'h'} }, x:{title:{display:true, text:'Pression Systolique (mmHg)', font:FONT}, grid:{display:false}, ticks:{font:FONT}} } }
});

new Chart(document.getElementById('chart-feature-imp'), {
  type:'bar', 
  data:{ labels: DS.featureImp.labels, datasets:[{ data: DS.featureImp.data, backgroundColor: DS.featureImp.colors.map(c=>c+'cc'), borderWidth:1.5, borderRadius:4 }] },
  options:{ indexAxis:'y', responsive:true, plugins:{ legend:{display:false} }, scales:{ x:{ max:0.2, grid:{color:'#f1f5f9'} }, y:{grid:{display:false}, ticks:{font:FONT}} } }
});