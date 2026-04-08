// ─────────────────────────────────────────────
// 🔐 Authentification et chargement du profil utilisateur
// ─────────────────────────────────────────────
 
// Détection de l'environnement (local ou production) pour configurer l'URL de l'API
const _local = ['localhost','127.0.0.1'].includes(window.location.hostname);
const API = window.location.origin + (_local ? '/SD4/IA-NAHA/Application/api' : '/IA-NAHA/Application/api');
 
// Fonction auto-exécutée asynchrone : charge les infos de l'utilisateur au démarrage de la page
(async function loadUser() {
  // Tentative de récupération du prénom et de l'email depuis le localStorage ou sessionStorage
  let name  = localStorage.getItem('naha_prenom') || sessionStorage.getItem('naha_prenom') || '';
  let email = localStorage.getItem('naha_email')  || '';
 
  // Si le prénom n'est pas en cache local, on interroge l'API avec le token d'authentification
  if (!name) {
    try {
      const token = localStorage.getItem('naha_token') || sessionStorage.getItem('naha_token');
      if (token) {
        const res  = await fetch(`${API}/get_user.php`, { headers: { 'Authorization': `Bearer ${token}` } });
        const data = await res.json();
        if (data.prenom) {
          name  = data.prenom;
          email = data.email || '';
          // Mise en cache des données pour éviter un appel API lors des prochains chargements
          localStorage.setItem('naha_prenom', name);
          localStorage.setItem('naha_email',  email);
        }
      }
    } catch(e) {} // Silencieux : l'interface reste fonctionnelle même sans données utilisateur
  }
 
  // Mise à jour de l'interface avec le prénom et l'initiale dans l'avatar
  if (name)  { document.getElementById('user-name').textContent  = name; document.getElementById('user-avatar').textContent = name[0].toUpperCase(); }
  if (email) { document.getElementById('user-email').textContent = email; }
})();
 
// Déconnexion : vide tout le stockage local et redirige vers la page de connexion
function logout() {
  localStorage.clear(); sessionStorage.clear();
  window.location.href = 'login.html';
}
 
// ─────────────────────────────────────────────
// 🎨 Configuration globale des couleurs et polices
// ─────────────────────────────────────────────
 
// Palette de couleurs centralisée : chaque couleur a sa version pleine et sa version transparente (L = Light)
const C = {
  purple:'#4f46e5', purpleL:'rgba(79,70,229,.15)',
  green: '#10b981', greenL: 'rgba(16,185,129,.15)',
  amber: '#f59e0b', amberL: 'rgba(245,158,11,.15)',
  pink:  '#ec4899', pinkL:  'rgba(236,72,153,.15)',
  teal:  '#06b6d4', tealL:  'rgba(6,182,212,.15)',
  red:   '#ef4444', redL:   'rgba(239,68,68,.15)',
  sky:   '#0ea5e9', lime:'#84cc16', slate:'#94a3b8',
};
 
// Configuration de la police par défaut pour les labels et titres des graphiques
const FONT = { family:"'Montserrat', sans-serif", weight:'700' };
 
// Application des paramètres globaux à tous les graphiques Chart.js de la page
Chart.defaults.font = { family:"'Montserrat', sans-serif", size:11 };
Chart.defaults.color = '#6b7280'; // Couleur grise pour les textes des axes
 
// ─────────────────────────────────────────────
// 📊 Données des visualisations et notebooks
// ─────────────────────────────────────────────
 
// Durée moyenne de sommeil (en heures) par niveau de stress (de 1 = faible à 10 = très élevé)
// On observe une tendance à la baisse : plus le stress est élevé, moins on dort
const stressData = [7.62, 7.46, 7.54, 7.56, 7.34, 6.98, 6.70, 6.36, 6.35, 6.27];
 
// Objet regroupant tous les jeux de données des graphiques de l'analyse
const DS = {
 
  // Répartition de l'échantillon : sportifs conservés vs sédentaires exclus de l'étude
  sedSport: { labels:['Sportifs retenus', 'Sédentaires exclus'], data:[2011, 989], colors:[C.green, C.slate] },
  
  // Corrélations erronées (données brutes non filtrées) : exemple de biais d'analyse
  // Ces valeurs trop élevées sont le signe d'une corrélation artificielle liée aux sédentaires
  corrFake: {
    labels: ['Qualité du sommeil', 'Niveau de stress', 'Fréquence cardiaque', 'Activité physique'],
    data: [0.88, -0.81, -0.51, 0.01],
    colors: [C.red, C.red, C.amber, C.slate]
  },
  
  // Corrélations réelles (après filtrage sur les sportifs uniquement)
  // Le stress reste le seul facteur réellement corrélé avec la durée du sommeil
  corrReal: { 
    labels: ['Niveau de stress', 'Âge', 'Hydratation', 'IMC', 'Pas / jour', 'Pression artérielle'], 
    data: [-0.413, -0.027, -0.037, -0.019, -0.011, 0.032], 
    colors: [C.red, C.slate, C.slate, C.slate, C.slate, C.slate] 
  },
 
  // Durée de sommeil moyenne selon le niveau de stress (1 à 10)
  // Couleur dynamique : vert si > 7.5h, amber si > 7h, orange si > 6.5h, rouge sinon
  stressSleep: {
    labels: ['Niveau 1', 'Niveau 2', 'Niveau 3', 'Niveau 4', 'Niveau 5', 'Niveau 6', 'Niveau 7', 'Niveau 8', 'Niveau 9', 'Niveau 10'],
    data: stressData,
    colors: stressData.map(v => v > 7.5 ? C.greenL : v > 7.0 ? C.amberL : v > 6.5 ? 'rgba(245,158,11,.35)' : C.redL),
    borders: stressData.map(v => v > 7.5 ? C.green : v > 7.0 ? C.amber : v > 6.5 ? '#f97316' : C.red)
  },
 
  // Relation entre la pression artérielle systolique (mmHg) et la durée du sommeil
  // Les valeurs sont quasi-plates → pas de corrélation significative
  bpSleep: {
    labels: [110, 115, 120, 125, 130, 135, 140],
    data: [7.08, 7.10, 7.11, 7.09, 7.12, 7.10, 7.13]
  },
  
  // Importance des variables dans le modèle de prédiction (Random Forest ou similaire)
  // Le stress domine largement les autres variables explicatives
  featureImp: {
    labels: ['Stress', 'Âge', 'IMC', 'Durée séance', 'Pas / jour', 'Hydratation', 'Genre', 'Intensité', 'Type activité', 'Statut tabac'],
    data:   [0.15, 0.05, 0.04, 0.03, 0.03, 0.02, 0.02, 0.01, 0.01, 0.01],
    colors: [C.red, C.slate, C.slate, C.slate, C.slate, C.slate, C.slate, C.slate, C.slate, C.slate]
  }
};
 
// ─────────────────────────────────────────────
// 📈 Initialisation des graphiques Chart.js
// ─────────────────────────────────────────────
 
// Graphique 1 : Donut — Répartition sportifs / sédentaires dans l'échantillon
new Chart(document.getElementById('chart-sed-sport'), {
  type:'doughnut', 
  data:{ labels: DS.sedSport.labels, datasets:[{ data: DS.sedSport.data, backgroundColor: DS.sedSport.colors, borderWidth:2, borderColor:'#fff', hoverOffset:8 }] },
  options:{ responsive:true, cutout:'65%', plugins:{ legend:{ position:'bottom', labels:{font:FONT, boxWidth:12, padding:15} } } }
});
 
// Graphique 2 : Barres horizontales — Corrélations AVANT filtrage (valeurs biaisées)
new Chart(document.getElementById('chart-corr-fake'), {
  type:'bar', 
  data:{ labels: DS.corrFake.labels, datasets:[{ data: DS.corrFake.data, backgroundColor: DS.corrFake.colors.map(c=>c+'cc'), borderWidth:1.5, borderRadius:4 }] },
  options:{ indexAxis:'y', responsive:true, plugins:{ legend:{display:false} }, scales:{ x:{ min:-1, max:1, grid:{color:'#f1f5f9'} }, y:{grid:{display:false}, ticks:{font:FONT}} } }
});
 
// Graphique 3 : Barres horizontales — Corrélations APRÈS filtrage (valeurs réelles)
new Chart(document.getElementById('chart-corr-real'), {
  type:'bar', 
  data:{ labels: DS.corrReal.labels, datasets:[{ data: DS.corrReal.data, backgroundColor: DS.corrReal.colors.map(c=>c+'cc'), borderWidth:1.5, borderRadius:4 }] },
  options:{ indexAxis:'y', responsive:true, plugins:{ legend:{display:false} }, scales:{ x:{ min:-0.5, max:0.1, grid:{color:'#f1f5f9'} }, y:{grid:{display:false}, ticks:{font:FONT}} } }
});
 
// Graphique 4 : Barres verticales — Durée de sommeil par niveau de stress (1 à 10)
// Les couleurs changent dynamiquement selon la qualité du sommeil
new Chart(document.getElementById('chart-stress-sleep'), {
  type:'bar', 
  data:{ labels: DS.stressSleep.labels, datasets:[{ label:'Sommeil (h)', data:DS.stressSleep.data, backgroundColor:DS.stressSleep.colors, borderColor:DS.stressSleep.borders, borderWidth:2, borderRadius:6 }] },
  options:{ responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ min:6.0, max:8.2, grid:{color:'#f1f5f9'}, ticks:{font:FONT, callback:v=>v.toFixed(1)+'h'} }, x:{grid:{display:false}, ticks:{font:FONT}} } }
});
 
// Graphique 5 : Courbe — Relation pression artérielle systolique / durée du sommeil
// La courbe plate confirme l'absence de corrélation significative
new Chart(document.getElementById('chart-bp-sleep'), {
  type:'line',
  data:{ labels: DS.bpSleep.labels, datasets:[{ label:'Sommeil (h)', data:DS.bpSleep.data, borderColor:C.purple, backgroundColor:C.purpleL, fill:true, tension:0.1, pointRadius:4 }] },
  options:{ responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ min:6.5, max:7.5, grid:{color:'#f1f5f9'}, ticks:{font:FONT, callback:v=>v.toFixed(1)+'h'} }, x:{title:{display:true, text:'Pression Systolique (mmHg)', font:FONT}, grid:{display:false}, ticks:{font:FONT}} } }
});
 
// Graphique 6 : Barres horizontales — Importance des variables dans le modèle prédictif
// Confirme que le stress est de loin la variable la plus influente sur la durée du sommeil
new Chart(document.getElementById('chart-feature-imp'), {
  type:'bar', 
  data:{ labels: DS.featureImp.labels, datasets:[{ data: DS.featureImp.data, backgroundColor: DS.featureImp.colors.map(c=>c+'cc'), borderWidth:1.5, borderRadius:4 }] },
  options:{ indexAxis:'y', responsive:true, plugins:{ legend:{display:false} }, scales:{ x:{ max:0.2, grid:{color:'#f1f5f9'} }, y:{grid:{display:false}, ticks:{font:FONT}} } }
});