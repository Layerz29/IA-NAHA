// ─────────────────────────────────────────────
// 🔧 Configuration de l'API selon l'environnement (local ou production)
// ─────────────────────────────────────────────
    const _local = ['localhost','127.0.0.1'].includes(window.location.hostname);
    // Si on est en local, on utilise le chemin de développement, sinon le chemin de production
    const API     = window.location.origin + (_local ? '/SD4/IA-NAHA/Application/api' : '/IA-NAHA/Application/api');
 
// ─────────────────────────────────────────────
// 🔗 Récupération des paramètres de l'URL
// ─────────────────────────────────────────────
    const params  = new URLSearchParams(window.location.search);
 
    // Priorité à l'URL, puis localStorage, puis sessionStorage
    // Permet de maintenir la session utilisateur à travers les différentes pages
    const _uid = params.get('user_id') || localStorage.getItem('naha_user_id') || sessionStorage.getItem('naha_user_id') || null;
    const USER_ID = _uid;
 
    // Si user_id est présent dans l'URL : on le sauvegarde dans localStorage, puis on le retire de l'URL
    // Cela évite que l'identifiant reste visible dans la barre d'adresse
    if (params.get('user_id')) {
        localStorage.setItem('naha_user_id', params.get('user_id'));
        params.delete('user_id');
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        history.replaceState(null, '', newUrl); // Met à jour l'URL sans recharger la page
    }
 
    // Récupère le token d'authentification depuis le localStorage
    const getToken = () => localStorage.getItem('naha_token') || '';
 
    // Génère les en-têtes HTTP avec le token d'authentification (Bearer + X-Auth-Token)
    const authHeaders = (extra={}) => ({ 'Content-Type':'application/json', 'Authorization':'Bearer '+getToken(), 'X-Auth-Token': getToken(), ...extra });
 
    // Variables globales de l'application
    let allPlans = [];    // Tableau contenant tous les plans nutritionnels de l'utilisateur
    let userData = {};    // Données du profil utilisateur
    let _chartMacros = null; // Référence au graphique en donut des macronutriments
    let _chartCals   = null; // Référence au graphique en barres des calories
 
    // ── INITIALISATION ──
    // Point d'entrée principal : charge les données utilisateur et les plans au démarrage
    async function init() {
        // Si aucun utilisateur connecté, afficher un message et rediriger vers la connexion
        if (!USER_ID) {
            document.getElementById('user-name').textContent = 'Non connecté';
            document.getElementById('recent-grid').innerHTML = `
      <div class="empty-state" style="grid-column:1/-1">
        <span class="empty-icon">🔐</span>
        <div class="empty-title">Non connecté</div>
        <p class="empty-sub">Connectez-vous pour accéder à vos plans.</p>
        <a href="login.html" class="btn-primary" style="display:inline-flex;margin:0 auto">Se connecter</a>
      </div>`;
            // Sauvegarder l'URL cible pour rediriger après la connexion
            localStorage.setItem('naha_redirect', window.location.href);
            return;
        }
        // Chargement en parallèle des données utilisateur et des plans pour optimiser les performances
        await Promise.all([loadUser(), loadPlans()]);
        renderOverview(); // Affiche la vue d'ensemble une fois les données chargées
    }
 
    // ── CHARGEMENT DU PROFIL UTILISATEUR ──
    // Récupère les informations de l'utilisateur depuis l'API et met à jour l'interface
    async function loadUser() {
        try {
            const res  = await fetch(`${API}/get_user.php`, { headers: authHeaders() });
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            userData = data;
            // Récupère le prénom depuis l'API ou le localStorage en fallback
            const prenom = data.prenom || localStorage.getItem('naha_prenom') || '?';
            // Affiche l'initiale du prénom dans l'avatar
            document.getElementById('user-avatar').textContent = prenom[0].toUpperCase();
            document.getElementById('user-name').textContent   = `${prenom} ${data.nom||''}`.trim();
            document.getElementById('user-email').textContent  = data.email || '';
        } catch {
            // En cas d'erreur, on affiche au moins le prénom depuis le localStorage
            const p = localStorage.getItem('naha_prenom') || '?';
            document.getElementById('user-avatar').textContent = p[0].toUpperCase();
            document.getElementById('user-name').textContent   = p;
        }
    }
 
    // ── CHARGEMENT DES PLANS NUTRITIONNELS ──
    // Récupère la liste de tous les plans de l'utilisateur depuis l'API
    async function loadPlans() {
        try {
            const res  = await fetch(`${API}/get_plans.php`, { headers: authHeaders() });
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            // Accepte soit un tableau direct, soit un objet contenant une propriété "plans"
            allPlans = Array.isArray(data) ? data : (data.plans || []);
            // Met à jour le badge de comptage des plans dans la navigation
            document.getElementById('plans-badge').textContent = allPlans.length;
        } catch { allPlans = []; } // En cas d'erreur, on repart avec un tableau vide
    }
 
    // ── VUE D'ENSEMBLE ──
    // Affiche le tableau de bord principal avec les KPIs, la bannière sommeil et les graphiques
    function renderOverview() {
        const last = allPlans[0] || null; // Plan le plus récent
 
        // ── Bannière sommeil ──
        // Affiche un conseil personnalisé selon le nombre d'heures de sommeil prédit
        const sleep = parseFloat(last?.temps_sommeil);
        if (sleep) {
            document.getElementById('sleep-banner-val').textContent  = sleep;
            document.getElementById('sleep-banner-hint').textContent =
                sleep < 6   ? 'En dessous des recommandations, réduisez le stress et les écrans le soir.' :
                sleep < 7   ? 'Légèrement insuffisant, visez 7h+ pour une récupération optimale.' :
                sleep <= 9  ? 'Idéal ! Votre profil indique une bonne qualité de sommeil.' :
                              'Temps élevé,peut indiquer un besoin de récupération accru.';
            document.getElementById('sleep-banner').style.display = 'flex';
        }
 
        // ── KPIs principaux ──
        document.getElementById('kpi-plans').textContent = allPlans.length;
        document.getElementById('kpi-cal').textContent   = last?.calories_cibles || '–';
        document.getElementById('kpi-prot').textContent  = last ? last.proteines_g+'g' : '–';
        // Calcule le total de jours sur l'ensemble des plans
        const totalJ = allPlans.reduce((s,p) => s + (parseInt(p.duree_jours)||0), 0);
        document.getElementById('kpi-jours').textContent = totalJ || '–';
 
        // ── Grille des plans récents ──
        // Affiche les 3 plans les plus récents, ou un état vide si aucun plan
        const grid = document.getElementById('recent-grid');
        const recent = allPlans.slice(0,3);
        grid.innerHTML = recent.length ? recent.map(planCardHTML).join('') : emptyHTML();
 
        // Affiche les graphiques seulement si des plans existent
        if (allPlans.length) renderCharts();
    }
 
    // ── GRAPHIQUES ──
    // Génère le donut des macronutriments et le graphique en barres de l'historique des calories
    function renderCharts() {
        document.getElementById('charts-section').style.display = '';
 
        const last = allPlans[0]; // Plan le plus récent pour le donut
        const prot = parseFloat(last.proteines_g) || 0;
        const gluc = parseFloat(last.glucides_g)  || 0;
        const lipi = parseFloat(last.lipides_g)   || 0;
        // Conversion en kilocalories : protéines et glucides = 4 kcal/g, lipides = 9 kcal/g
        const kcalP = prot * 4, kcalG = gluc * 4, kcalL = lipi * 9;
 
        // Palette de couleurs verte cohérente avec le thème de l'application
        const COLORS = { prot:'#7ecb5a', gluc:'#4a9e2a', lipi:'#2d6618' };
 
        // ── Donut des macronutriments ──
        // Détruit le graphique existant avant d'en créer un nouveau pour éviter les conflits
        if (_chartMacros) _chartMacros.destroy();
        _chartMacros = new Chart(document.getElementById('chart-macros'), {
            type: 'doughnut',
            data: {
                labels: ['Protéines', 'Glucides', 'Lipides'],
                datasets: [{ data: [kcalP, kcalG, kcalL],
                    backgroundColor: [COLORS.prot, COLORS.gluc, COLORS.lipi],
                    borderColor: '#060d05', borderWidth: 3, hoverOffset: 6 }]
            },
            options: {
                cutout: '68%', plugins: { legend: { display: false }, tooltip: {
                    callbacks: { label: ctx => ` ${ctx.label} : ${ctx.parsed} kcal` }
                }},
                animation: { animateRotate: true, duration: 700 }
            }
        });
 
        // ── Légende manuelle du donut ──
        // Affiche le pourcentage de chaque macronutriment par rapport au total calorique
        const total = kcalP + kcalG + kcalL || 1; // Évite la division par zéro
        document.getElementById('chart-macros-legend').innerHTML = [
            ['Protéines', kcalP, COLORS.prot],
            ['Glucides',  kcalG, COLORS.gluc],
            ['Lipides',   kcalL, COLORS.lipi],
        ].map(([lbl, val, col]) => `
            <span class="chart-legend-item">
                <span class="chart-legend-dot" style="background:${col}"></span>
                ${lbl} <span style="color:var(--muted)">· ${Math.round(val/total*100)}%</span>
            </span>`).join('');
 
        // ── Graphique en barres de l'historique des calories ──
        // Trie les plans du plus ancien au plus récent pour un affichage chronologique
        const sorted = [...allPlans].reverse();
        const labels = sorted.map((p, i) => {
            // Formate la date en JJ/MM, ou utilise l'index comme fallback
            const d = p.created_at ? new Date(p.created_at).toLocaleDateString('fr-FR',{day:'2-digit',month:'2-digit'}) : `#${i+1}`;
            return d;
        });
        const calData = sorted.map(p => parseFloat(p.calories_cibles) || 0);
 
        // Détruit le graphique existant avant d'en créer un nouveau
        if (_chartCals) _chartCals.destroy();
        _chartCals = new Chart(document.getElementById('chart-cals'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{ label: 'kcal / jour', data: calData,
                    backgroundColor: 'rgba(126,203,90,0.25)',
                    borderColor: '#7ecb5a', borderWidth: 1.5,
                    borderRadius: 6, borderSkipped: false }]
            },
            options: {
                plugins: { legend: { display: false }, tooltip: {
                    callbacks: { label: ctx => ` ${ctx.parsed.y} kcal/j` }
                }},
                scales: {
                    x: { ticks: { color:'#4a6b42', font:{size:10} }, grid: { color:'rgba(126,203,90,0.06)' } },
                    y: { ticks: { color:'#4a6b42', font:{size:10} }, grid: { color:'rgba(126,203,90,0.06)' }, beginAtZero: false }
                },
                animation: { duration: 700 }
            }
        });
    }
 
    // ── LISTE DE TOUS LES PLANS ──
    // Affiche l'ensemble des plans dans la vue "Mes plans"
    function renderAllPlans() {
        document.getElementById('all-grid').innerHTML =
            allPlans.length ? allPlans.map(planCardHTML).join('') : emptyHTML();
    }
 
    // ── CARTE D'UN PLAN ──
    // Génère le HTML d'une carte de plan nutritionnel (utilisée dans la grille)
    function planCardHTML(p) {
        const date  = p.created_at ? new Date(p.created_at).toLocaleDateString('fr-FR') : '';
        // Utilise le premier objectif comme titre, formaté proprement
        const title = p.objectif ? cap(p.objectif.split(',')[0]) : 'Plan nutritionnel';
        return `
    <div class="plan-card" onclick="openPlan(${p.id})">
      <div class="plan-card-top">
        <span class="plan-tag">${p.duree_jours||1}j · ${p.repas_par_jour||3} repas</span>
        <span class="plan-date">${date}</span>
      </div>
      <div class="plan-title">${title}</div>
      <div class="plan-meta">${p.activite||''} · ${p.restrictions||'Aucune restriction'}</div>
      <div class="plan-macros">
        <span class="plan-pill">🔥 <span>${p.calories_cibles||'?'}</span> kcal</span>
        <span class="plan-pill">💪 <span>${p.proteines_g||'?'}</span>g</span>
        <span class="plan-pill">🌾 <span>${p.glucides_g||'?'}</span>g</span>
        ${p.temps_sommeil ? `<span class="plan-pill sleep-pill">🌙 <span>${p.temps_sommeil}</span>h</span>` : ''}
      </div>
      <div class="plan-footer">
        <button class="plan-btn" onclick="event.stopPropagation();openPlan(${p.id})">Voir</button>
        <button class="plan-btn danger" onclick="event.stopPropagation();deletePlan(${p.id})">Supprimer</button>
      </div>
    </div>`;
    }
 
    // ── OUVERTURE D'UN PLAN ──
    // Charge et affiche le détail d'un plan spécifique via son identifiant
    async function openPlan(planId) {
        showView('plan-detail'); // Bascule vers la vue détail
        // Affiche un loader pendant le chargement
        document.getElementById('detail-content').innerHTML =
            '<div class="loader-wrap"><div class="spin"></div>Chargement du plan…</div>';
        try {
            const res  = await fetch(`${API}/get_plans.php?plan_id=${planId}`, { headers: authHeaders() });
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            renderDetail(data); // Affiche les données du plan
        } catch(e) {
            // Affiche un message d'erreur en rouge si le chargement échoue
            document.getElementById('detail-content').innerHTML =
                `<p style="color:#f87171;padding:2rem">⚠ ${e.message}</p>`;
        }
    }
 
    // ── AFFICHAGE DU DÉTAIL D'UN PLAN ──
    // Construit et affiche la vue complète d'un plan avec ses repas par jour et ses conseils
    function renderDetail(p) {
        const date  = p.created_at ? new Date(p.created_at).toLocaleDateString('fr-FR') : '';
        const title = p.objectif ? cap(p.objectif.split(',')[0]) : 'Plan nutritionnel';
 
        // ── Reconstruction des jours depuis les données de l'API ──
        let jours = [];
        if (p.meals && p.meals.length) {
            // Cas 1 : données structurées via le tableau "meals" → regroupement par jour
            const byJ = {};
            p.meals.forEach(m => { if (!byJ[m.jour]) byJ[m.jour]=[]; byJ[m.jour].push(m); });
            jours = Object.keys(byJ).sort((a,b)=>+a-+b).map(j => ({
                jour: +j,
                repas: byJ[j].map(m => ({
                    type: m.type_repas, nom: m.nom,
                    calories: m.calories, proteines: m.proteines,
                    glucides: m.glucides, lipides: m.lipides, fibres: m.fibres,
                    aliments: tryParse(m.detail) || [], // Parse le JSON des aliments
                }))
            }));
        } else if (p.plan_texte) {
            // Cas 2 : données stockées sous forme de JSON brut dans "plan_texte"
            try { jours = JSON.parse(p.plan_texte).jours || []; } catch {}
        }
 
        // Récupère les conseils personnalisés depuis le plan_texte si disponibles
        let conseils = [];
        if (p.plan_texte) { try { conseils = JSON.parse(p.plan_texte).conseils || []; } catch {} }
 
        // Génère les onglets de navigation entre les jours du plan
        const tabsHTML = jours.map((j,i) =>
            `<button class="d-tab ${i===0?'active':''}" onclick="switchDay(${j.jour},this,event)">Jour ${j.jour}</button>`
        ).join('');
 
        // Injecte tout le HTML de la vue détail dans le conteneur
        document.getElementById('detail-content').innerHTML = `
    <div class="detail-header">
      <div>
        <div class="detail-title"><em>${title}</em></div>
        <div class="detail-meta">Créé le ${date} · ${p.duree_jours||1}j · ${p.repas_par_jour||3} repas/jour · BMR ${p.bmr||'?'} kcal</div>
      </div>
      <a href="onboarding.html" class="btn-primary">↺ Nouveau plan</a>
    </div>
    <div class="macros-row">
      <div class="m-card"><span class="m-val">${p.calories_cibles}</span><span class="m-unit">kcal</span><span class="m-lbl">Calories / jour</span></div>
      <div class="m-card"><span class="m-val">${p.proteines_g}</span><span class="m-unit">g</span><span class="m-lbl">Protéines</span></div>
      <div class="m-card"><span class="m-val">${p.glucides_g}</span><span class="m-unit">g</span><span class="m-lbl">Glucides</span></div>
      <div class="m-card"><span class="m-val">${p.lipides_g}</span><span class="m-unit">g</span><span class="m-lbl">Lipides</span></div>
      ${p.temps_sommeil ? `<div class="m-card m-card-sleep"><span class="m-val" style="color:#818cf8">🌙 ${p.temps_sommeil}</span><span class="m-unit">h</span><span class="m-lbl">Sommeil prédit</span></div>` : ''}
    </div>
    <div class="tabs-wrap">
      <div class="tabs-head" id="d-tabs">${tabsHTML}</div>
      <div class="tabs-body" id="d-meals">${mealsHTML(jours[0]?.repas||[])}</div>
    </div>
    ${conseils.length ? `<div class="conseils-wrap">
      <div class="sec-title" style="margin-bottom:1rem">Conseils personnalisés</div>
      ${conseils.map(c=>`<div class="conseil">${c}</div>`).join('')}
    </div>` : ''}
  `;
 
        // Stocke les jours dans une variable globale pour permettre la navigation entre les onglets
        window._detailJours = jours;
    }
 
    // ── CHANGEMENT DE JOUR ──
    // Bascule l'affichage des repas vers le jour sélectionné via les onglets
    function switchDay(jour, btn) {
        // Retire la classe active de tous les onglets, puis l'applique au bouton cliqué
        document.querySelectorAll('.d-tab').forEach(t=>t.classList.remove('active'));
        btn.classList.add('active');
        // Trouve et affiche les repas du jour correspondant
        const j = (window._detailJours||[]).find(j=>j.jour===jour);
        document.getElementById('d-meals').innerHTML = mealsHTML(j?.repas||[]);
    }
 
    // ── HTML DES REPAS D'UN JOUR ──
    // Génère le HTML de tous les repas d'une journée donnée
    function mealsHTML(repas) {
        if (!repas.length) return '<p style="color:var(--muted);font-size:0.8rem">Aucun repas.</p>';
        return repas.map(r => `
    <div class="meal-card">
      <div class="meal-head">
        <div>
          <span class="meal-badge">${(r.type||'').replace(/_/g,' ')}</span>
          <div class="meal-name">${r.nom||''}</div>
        </div>
        <span class="meal-kcal">${r.calories||0} kcal</span>
      </div>
      <div class="meal-mac">P:${r.proteines||0}g · G:${r.glucides||0}g · L:${r.lipides||0}g · F:${r.fibres||0}g</div>
      <div class="tags">
        ${(Array.isArray(r.aliments)?r.aliments:[]).map(a=>`<span class="tag">${a.nom} <span class="qty">${a.quantite_g}g</span></span>`).join('')}
      </div>
    </div>`).join('');
    }
 
    // ── SUPPRESSION D'UN PLAN ──
    // Demande confirmation puis supprime le plan via l'API et met à jour l'interface
    async function deletePlan(planId) {
        if (!confirm('Supprimer ce plan ?')) return; // Confirmation utilisateur obligatoire
        try {
            const res  = await fetch(`${API}/delete_plan.php`, {
                method:'POST', headers: authHeaders(),
                body: JSON.stringify({ plan_id: planId }),
            });
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            // Met à jour le tableau local sans recharger la page
            allPlans = allPlans.filter(p => p.id !== planId);
            document.getElementById('plans-badge').textContent = allPlans.length;
            renderAllPlans(); renderOverview(); // Rafraîchit les deux vues concernées
            showToast('Plan supprimé.','success');
        } catch(e) { showToast('Erreur : '+e.message,'error'); }
    }
 
    // ── PROFIL UTILISATEUR ──
    // Affiche les informations du profil de l'utilisateur dans la vue dédiée
    function renderProfile() {
        const u = userData;
        // Si les données utilisateur ne sont pas disponibles, afficher un message d'erreur
        if (!u?.email) {
            document.getElementById('profile-content').innerHTML =
                '<p style="color:var(--muted);font-size:0.8rem">Impossible de charger le profil.</p>';
            return;
        }
        // Construit un tableau de paires [label, valeur] en filtrant les valeurs nulles/vides
        const rows = [
            ['Prénom', u.prenom], ['Nom', u.nom], ['Email', u.email],
            ['Âge', u.age ? u.age+' ans' : null],
            ['Poids', u.poids ? u.poids+' kg' : null],
            ['Taille', u.taille ? u.taille+' cm' : null],
            ['Activité', u.activite], ['Objectif', u.objectif],
            ['Restrictions', u.restrictions],
        ].filter(r => r[1]); // Ne garde que les champs renseignés
 
        document.getElementById('profile-content').innerHTML = `
    ${rows.map(r=>`<div class="profile-row"><span class="profile-lbl">${r[0]}</span><span class="profile-val">${r[1]}</span></div>`).join('')}
    <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--border)">
      <a href="onboarding.html" class="btn-primary" style="display:inline-flex">
        Mettre à jour mon profil
      </a>
    </div>`;
    }
 
    // ── NAVIGATION ENTRE LES VUES ──
    // Dictionnaire associant chaque identifiant de vue à son titre affiché dans la topbar
    const TITLES = {
        'overview':    "Vue d'<em>ensemble</em>",
        'plans':       "Mes <em>plans</em>",
        'plan-detail': "Détail du <em>plan</em>",
        'profile':     "Mon <em>profil</em>",
    };
 
    // Affiche la vue demandée et masque toutes les autres
    function showView(name) {
        // Désactive toutes les vues et tous les éléments de navigation
        document.querySelectorAll('.view').forEach(v=>v.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
        // Active la vue et l'item de navigation correspondants
        const v = document.getElementById('view-'+name);
        if (v) v.classList.add('active');
        const nav = document.querySelector(`[onclick="showView('${name}');return false"]`);
        if (nav) nav.classList.add('active');
        // Met à jour le titre de la topbar
        document.getElementById('topbar-title').innerHTML = TITLES[name] || name;
        // Charge le contenu spécifique à chaque vue lors de l'ouverture
        if (name==='plans')   renderAllPlans();
        if (name==='profile') renderProfile();
    }
 
    // ── DÉCONNEXION ──
    // Efface toutes les données de session et redirige vers la page de connexion
    function logout() {
        ['naha_user_id','naha_token','naha_prenom'].forEach(k=>localStorage.removeItem(k));
        sessionStorage.removeItem('naha_profil'); // Supprime également le profil en session
        window.location.href = 'login.html';
    }
 
    // ── FONCTIONS UTILITAIRES ──
 
    // Parse un JSON de manière sécurisée, retourne null en cas d'erreur
    function tryParse(s) { try { return JSON.parse(s); } catch { return null; } }
 
    // Met en majuscule la première lettre et remplace les underscores par des espaces
    function cap(s) { return s ? s.charAt(0).toUpperCase()+s.slice(1).replace(/_/g,' ') : ''; }
 
    // Génère le HTML de l'état vide (aucun plan créé)
    function emptyHTML() {
        return `<div class="empty-state">
    <span class="empty-icon">🌱</span>
    <div class="empty-title">Aucun plan encore</div>
    <p class="empty-sub">Générez votre premier plan nutritionnel personnalisé.</p>
    <a href="onboarding.html" class="btn-primary" style="display:inline-flex;margin:0 auto">✦ Créer mon premier plan</a>
  </div>`;
    }
 
    // Affiche une notification toast temporaire (succès ou erreur) pendant 3 secondes
    function showToast(msg, type='') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = 'toast show'+(type?' '+type:'');
        setTimeout(()=>t.classList.remove('show'), 3000); // Masque le toast après 3s
    }
 
    // ── LANCEMENT DE L'APPLICATION ──
    init();