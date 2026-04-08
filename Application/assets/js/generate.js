// ──────────────────────────────────────────────
  // CONFIG — Détection de l'environnement et configuration de l'URL de l'API
  // ──────────────────────────────────────────────
  const _local = ['localhost','127.0.0.1'].includes(window.location.hostname);
  // Chemin de l'API selon l'environnement : développement local ou serveur de production
  const API_BASE = window.location.origin + (_local ? '/SD4/IA-NAHA/Application/api' : '/IA-NAHA/Application/api');
 
  // ──────────────────────────────────────────────
  // CIQUAL — Base de données nutritionnelle chargée dynamiquement depuis la BD via ciqual.php
  // Contient les aliments avec leurs valeurs nutritionnelles pour 100g
  // ──────────────────────────────────────────────
  let CIQUAL = [];
 
  // ──────────────────────────────────────────────
  // AUTH — Gestion du token d'authentification
  // ──────────────────────────────────────────────
  // Récupère le token JWT stocké dans le localStorage
  const getToken    = () => localStorage.getItem('naha_token') || '';
  // Génère les en-têtes HTTP avec le token pour sécuriser chaque requête API
  const authHeaders = (extra={}) => ({ 'Content-Type':'application/json', 'Authorization':'Bearer '+getToken(), 'X-Auth-Token': getToken(), ...extra });
 
  // ──────────────────────────────────────────────
  // STATE — Variables globales de l'application
  // ──────────────────────────────────────────────
  let currentPlan = null; // Plan nutritionnel généré par Gemini, en cours d'affichage
  let profil      = null; // Données du profil utilisateur (âge, poids, objectif, etc.)
  let activeJour  = 1;    // Numéro du jour actuellement affiché dans les onglets
 
  // Récupération de l'identifiant utilisateur depuis l'URL ou le stockage local
  const params  = new URLSearchParams(window.location.search);
  const USER_ID = params.get('user_id') || sessionStorage.getItem('naha_user_id') || localStorage.getItem('naha_user_id') || null;
 
  // ──────────────────────────────────────────────
  // CIQUAL FILTER — Filtrage des aliments selon le profil utilisateur
  // ──────────────────────────────────────────────
  // Retourne une liste d'aliments CIQUAL adaptée aux restrictions et objectifs du profil,
  // formatée en texte compact pour être injectée dans le prompt Gemini
  function ciqualForPrompt(restrictions, objectif) {
    const r   = (restrictions||'').toLowerCase();
    const obj = (objectif||'').toLowerCase();
 
    // Détection des régimes alimentaires spéciaux
    const isVegan   = r.includes('vegan');
    const isVege    = r.includes('végétarien') || obj.includes('vegetarien') || isVegan;
    const noLactose = r.includes('sans_lactose');
    const noNoix    = r.includes('sans_noix');
 
    // Groupes d'aliments à exclure selon les restrictions
    const viande  = ['viandes crues','viandes cuites','charcuteries et alternatives végétales','poissons crus','poissons cuits','mollusques et crustacés crus','mollusques et crustacés cuits','produits à base de poissons et produits de la mer'];
    const laitier = ['fromages et alternatives végétales','laits','produits laitiers frais et alternatives végétales','crèmes et spécialités à base de crème'];
 
    // Groupes prioritaires inclus dans le prompt pour couvrir les besoins nutritionnels essentiels
    const priority = ['viandes cuites','poissons cuits','oeufs','légumes','légumineuses','fruits','pâtes, riz et céréales','pains et assimilés','fromages et alternatives végétales','produits laitiers frais et alternatives végétales','fruits à coque et graines oléagineuses','ingrédients pour végétariens','huiles et graisses végétales','pommes de terre et autres tubercules','céréales de petit-déjeuner','produits à base de poissons et produits de la mer','charcuteries et alternatives végétales'];
 
    // Application des filtres selon les restrictions du profil
    let list = CIQUAL.filter(a => {
      if (isVegan   && [...viande,'oeufs'].includes(a.g)) return false; // Exclut viandes + œufs pour vegan
      if (isVege    && viande.includes(a.g))              return false; // Exclut viandes pour végétarien
      if (noLactose && laitier.includes(a.g))             return false; // Exclut produits laitiers
      if (noNoix    && a.g === 'fruits à coque et graines oléagineuses') return false; // Exclut les noix
      return priority.includes(a.g); // Ne garde que les groupes prioritaires
    });
 
    // Dédoublonnage : un seul représentant par nom de base (évite les doublons cru/cuit)
    const seen = new Set();
    list = list.filter(a => {
      const key = a.n.split(',')[0].split('(')[0].trim().toLowerCase().slice(0,30);
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    });
 
    // Limite à 80 aliments et formate en texte ultra-compact pour réduire la taille du prompt
    // Format : nom|calories|proteines|glucides|lipides
    return list.slice(0, 80)
            .map(a => a.n.slice(0,40) + '|' + a.c + '|' + a.p + '|' + a.gl + '|' + a.l)
            .join('\n');
  }
 
 
  // ──────────────────────────────────────────────
  // PROMPT BUILDER — Construction du prompt envoyé à Gemini
  // ──────────────────────────────────────────────
  // Génère un prompt structuré avec le profil utilisateur et la base CIQUAL filtrée
  // Le modèle doit retourner uniquement un JSON strict sans markdown ni texte autour
  function buildPrompt(p) {
    return `Tu es un expert en nutrition. Génère un plan alimentaire JSON strict.
 
PROFIL :
- Prénom: ${p.prenom} | Âge: ${p.age} ans | Sexe: ${p.sexe}
- Poids: ${p.poids} kg | Taille: ${p.taille} cm | Activité: ${p.activite}
- Objectifs: ${p.objectif || 'Santé générale'}
- Restrictions: ${p.restrictions || 'Aucune'}
- Allergies: ${p.allergies || 'Aucune'}
- Plan: ${p.duree} jour(s), ${p.repas} repas/jour
 
BASE CIQUAL ANSES — valeurs pour 100g — format: nom|kcal|proteines_g|glucides_g|lipides_g
Utilise UNIQUEMENT ces aliments. Pour chaque aliment, calcule les macros selon la quantité choisie.
${ciqualForPrompt(p.restrictions, p.objectif)}
 
INSTRUCTIONS :
1. Calcule le BMR (Harris-Benedict) puis ajuste : déficit -15% si perte de poids, surplus +10% si prise de masse
2. Choisis 3 à 6 aliments CIQUAL par repas et calcule les macros selon les grammes
3. Varie les aliments entre les jours
4. Donne 4 conseils personnalisés selon le profil
 
RÉPONDS UNIQUEMENT avec ce JSON brut, sans markdown :
{
  "bmr": 0,
  "calories_cibles": 0,
  "proteines_g": 0,
  "glucides_g": 0,
  "lipides_g": 0,
  "jours": [
    {
      "jour": 1,
      "repas": [
        {
          "type": "petit_dejeuner",
          "nom": "Nom du repas",
          "calories": 0,
          "proteines": 0,
          "glucides": 0,
          "lipides": 0,
          "fibres": 0,
          "aliments": [
            { "nom": "Nom exact aliment CIQUAL", "quantite_g": 0 }
          ]
        }
      ]
    }
  ],
  "conseils": ["conseil 1", "conseil 2", "conseil 3", "conseil 4"]
}
 
RÈGLES : commence par { , termine par } , aucun texte ni markdown autour.`;
  }
 
 
  // ──────────────────────────────────────────────
  // PROGRESS ANIMATION — Gestion de la barre de progression circulaire
  // ──────────────────────────────────────────────
  let progVal = 0;    // Valeur courante de la progression (0 à 100)
  let progTimer = null;
 
  // Met à jour visuellement le cercle SVG de progression et le pourcentage affiché
  function setProgress(val) {
    progVal = val;
    const circumference = 283; // Périmètre du cercle SVG (2π × rayon)
    const offset = circumference - (val / 100) * circumference;
    document.getElementById('loader-prog').style.strokeDashoffset = offset;
    document.getElementById('loader-pct').textContent = Math.round(val) + '%';
  }
 
  // Anime la progression de "from" à "to" en "duration" ms avec un easing ease-in-out
  function animateProgress(from, to, duration) {
    const start = performance.now();
    const diff = to - from;
    function tick(now) {
      const elapsed = now - start;
      const prog = Math.min(elapsed / duration, 1);
      // Fonction d'easing quadratique : accélération puis décélération
      const eased = prog < 0.5 ? 2*prog*prog : -1+(4-2*prog)*prog;
      setProgress(from + diff * eased);
      if (prog < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
 
  // Active visuellement une étape du loader (1 à 5) : done, active ou en attente
  function activateStep(n) {
    for (let i = 1; i <= 5; i++) {
      const el = document.getElementById('ls-'+i);
      if (i < n)      { el.className = 'l-step done'; }   // Étape terminée
      else if (i === n){ el.className = 'l-step active'; } // Étape en cours
      else            { el.className = 'l-step'; }         // Étape à venir
    }
  }
 
  // ──────────────────────────────────────────────
  // MAIN GENERATE — Orchestration principale de la génération du plan
  // ──────────────────────────────────────────────
 
  // Appel à Gemini via le proxy serveur (la clé API n'est jamais exposée côté client)
  async function callGemini(prompt) {
    const res = await fetch(`${API_BASE}/gemini.php`, {
      method: 'POST',
      headers: authHeaders(),
      body: JSON.stringify({ prompt }),
    });
    if (!res.ok) { const e = await res.json(); throw new Error(e?.error || 'Erreur serveur Gemini'); }
    const data = await res.json();
    console.log('[Gemini debug]', data._debug);
    if (data.error) throw new Error(data.error);
 
    // Nettoyage : supprime les balises markdown éventuellement ajoutées autour du JSON
    let text = data.text || '';
    text = text.replace(/^```json\s*/i,'').replace(/^```\s*/i,'').replace(/```\s*$/i,'').trim();
 
    // Extraction du JSON valide via regex (protection contre les textes parasites)
    const match = text.match(/\{[\s\S]*\}/);
    if (!match) throw new Error('JSON introuvable dans la réponse Gemini');
    try {
      return JSON.parse(match[0]);
    } catch (jsonErr) {
      throw new Error('Réponse Gemini invalide (JSON malformé) : ' + jsonErr.message);
    }
  }
 
  // Fonction principale : charge le profil, la base CIQUAL, génère le plan par chunks et affiche le résultat
  async function generate() {
    // Récupère le profil utilisateur depuis le sessionStorage (rempli lors de l'onboarding)
    const raw = sessionStorage.getItem('naha_profil');
    if (!raw) {
      // Profil de démo utilisé si aucune session n'est disponible
      profil = {
        prenom: localStorage.getItem('naha_prenom') || 'Utilisateur',
        age: '28', sexe: 'homme', poids: '75', taille: '178',
        activite: 'modere', objectif: 'sante', restrictions: '',
        allergies: '', duree: '3', repas: '3',
      };
    } else {
      try {
        profil = JSON.parse(raw);
      } catch {
        // Profil corrompu : avertit l'utilisateur et redirige vers l'onboarding
        showError('⚠ Profil invalide. Veuillez recommencer l\'onboarding.');
        setTimeout(() => window.location.href = 'onboarding.html', 2000);
        return;
      }
    }
 
    // Étape 1 — Initialisation et démarrage de l'animation
    activateStep(1); animateProgress(0, 15, 800);
    await sleep(800);
 
    // Étape 2 — Chargement de la base CIQUAL depuis la base de données
    activateStep(2); animateProgress(15, 35, 600);
    try {
      const ciqualRes = await fetch(`${API_BASE}/ciqual.php`);
      const ciqualData = await ciqualRes.json();
      CIQUAL = Array.isArray(ciqualData) ? ciqualData : [];
    } catch (e) {
      CIQUAL = []; // En cas d'échec, le prompt sera généré sans base CIQUAL
    }
    await sleep(600);
 
    // Étape 3 — Construction du prompt
    activateStep(3); animateProgress(35, 55, 500);
    await sleep(500);
 
    // Étape 4 — Appel à Gemini AI pour générer le plan nutritionnel
    activateStep(4); animateProgress(55, 85, 8000);
 
    try {
      const duree = parseInt(profil.duree) || 3;
      const CHUNK = 3; // Nombre maximum de jours par appel Gemini (évite la troncature du JSON)
      let allJours = [];
      let planMeta = null;
      let chunkCount = Math.ceil(duree / CHUNK);
 
      // Génération par blocs de 3 jours pour contourner la limite de tokens de l'API
      for (let i = 0; i < duree; i += CHUNK) {
        const chunkDuree = Math.min(CHUNK, duree - i);
        const chunkProfil = { ...profil, duree: chunkDuree };
        const result = await callGemini(buildPrompt(chunkProfil));
 
        // Le premier chunk contient les métadonnées globales du plan (BMR, macros cibles)
        if (i === 0) planMeta = result;
 
        // Renumérotation des jours pour conserver un ordre continu sur tout le plan
        result.jours.forEach(j => { j.jour = j.jour + i; });
        allJours = allJours.concat(result.jours);
 
        // Mise à jour progressive de la barre de chargement selon l'avancement des chunks
        const progDone = 55 + ((i/CHUNK + 1) / chunkCount) * 28;
        animateProgress(progDone - 5, progDone, 1000);
      }
 
      // Fusion des chunks : métadonnées du plan + tous les jours générés
      currentPlan = { ...planMeta, jours: allJours };
 
      // Étape 5 — Finalisation et affichage du résultat
      activateStep(5); animateProgress(85, 100, 600);
      await sleep(700);
 
      renderResult(currentPlan);
 
    } catch(e) {
      console.error(e);
      showError('⚠ ' + (e.message || 'Erreur inconnue'));
    }
  }
 
  // ──────────────────────────────────────────────
  // RENDER — Affichage du plan généré dans l'interface
  // ──────────────────────────────────────────────
  function renderResult(plan) {
    document.getElementById('screen-loader').style.display = 'none'; // Masque le loader
 
    // Affichage de la prédiction de sommeil ML si disponible en session
    const sleepHours = parseFloat(sessionStorage.getItem('naha_sleep_prediction'));
    if (sleepHours) {
      document.getElementById('sleep-hours-val').textContent = sleepHours;
      // Message d'accompagnement personnalisé selon la durée de sommeil prédite
      const hint = sleepHours < 6   ? 'En dessous des recommandations — pensez à réduire le stress et l\'écran le soir.'
                 : sleepHours < 7   ? 'Légèrement insuffisant — visez 7h+ pour une récupération optimale.'
                 : sleepHours <= 9  ? 'Idéal ! Votre profil indique une bonne qualité de sommeil.'
                 :                    'Temps élevé — peut indiquer un besoin de récupération accru.';
      document.getElementById('sleep-hint').textContent = hint;
      document.getElementById('sleep-card').style.display = 'flex';
    }
 
    // Sous-titre récapitulatif du plan généré
    document.getElementById('result-sub').textContent =
            `Plan ${plan.jours.length} jour(s) · ${profil.repas} repas/jour · Généré par Gemini AI`;
 
    // Affichage des macronutriments cibles sous forme de cartes colorées
    const mg = document.getElementById('macros-grid');
    mg.innerHTML = [
      { lbl:'Calories / jour', val: plan.calories_cibles, unit:'kcal', color:'#9ecf7a' },
      { lbl:'Protéines',       val: plan.proteines_g,     unit:'g',    color:'#4ade80' },
      { lbl:'Glucides',        val: plan.glucides_g,      unit:'g',    color:'#facc15' },
      { lbl:'Lipides',         val: plan.lipides_g,       unit:'g',    color:'#f97316' },
    ].map(m => `
    <div class="macro-card">
      <span class="macro-val" style="color:${m.color}">${m.val}</span>
      <span class="macro-unit">${m.unit}</span>
      <span class="macro-lbl">${m.lbl}</span>
    </div>
  `).join('');
 
    // Bloc d'information détaillé : BMR, calories cibles et récapitulatif du profil
    document.getElementById('bmr-info').innerHTML = `
    <strong>BMR calculé :</strong> ${plan.bmr} kcal/jour<br>
    <strong>Objectif calorique :</strong> ${plan.calories_cibles} kcal/jour<br>
    <strong>Protéines :</strong> ${plan.proteines_g}g · <strong>Glucides :</strong> ${plan.glucides_g}g · <strong>Lipides :</strong> ${plan.lipides_g}g<br>
    <strong>Profil :</strong> ${profil.prenom}, ${profil.age} ans, ${profil.poids}kg, ${profil.taille}cm
  `;
 
    // Rendu du graphique en camembert des macronutriments
    renderPie(plan.proteines_g, plan.glucides_g, plan.lipides_g);
 
    // Génération des onglets de navigation entre les jours du plan
    const tabsHeader = document.getElementById('day-tabs');
    tabsHeader.innerHTML = plan.jours.map(j => `
    <button class="day-tab ${j.jour === 1 ? 'active' : ''}"
      onclick="switchDay(${j.jour}, this)">Jour ${j.jour}</button>
  `).join('');
 
    // Affiche les repas du premier jour par défaut
    activeJour = 1;
    renderMeals(plan, 1);
 
    // Affichage des conseils personnalisés générés par Gemini
    document.getElementById('conseils-body').innerHTML =
            plan.conseils.map(c => `<div class="conseil">${c}</div>`).join('');
 
    document.getElementById('screen-result').classList.add('show');
  }
 
  // Bascule l'onglet actif et affiche les repas du jour sélectionné
  function switchDay(jour, btn) {
    document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    activeJour = jour;
    renderMeals(currentPlan, jour);
  }
 
  // Affiche la liste des repas pour un jour donné dans le conteneur dédié
  function renderMeals(plan, jour) {
    const jourData = plan.jours.find(j => j.jour === jour);
    if (!jourData) return;
    document.getElementById('meals-body').innerHTML = jourData.repas.map(r => `
    <div class="meal-card">
      <div class="meal-head">
        <div>
          <span class="meal-type-badge">${r.type.replace(/_/g,' ')}</span>
          <div class="meal-name">${r.nom}</div>
        </div>
        <span class="meal-kcal">${r.calories} kcal</span>
      </div>
      <div class="meal-macros">P: ${r.proteines}g · G: ${r.glucides}g · L: ${r.lipides}g · F: ${r.fibres}g</div>
      <div class="meal-aliments">
        ${r.aliments.map(a =>
            `<span class="aliment-tag">${a.nom} <span class="qty">${a.quantite_g}g</span></span>`
    ).join('')}
      </div>
    </div>
  `).join('');
  }
 
  // ──────────────────────────────────────────────
  // PIE CHART — Génération manuelle d'un camembert SVG des macronutriments
  // ──────────────────────────────────────────────
  // Calcule et dessine les arcs SVG proportionnels aux apports caloriques de chaque macro
  function renderPie(prot, gluc, lip) {
    // Conversion en kcal : protéines et glucides = 4 kcal/g, lipides = 9 kcal/g
    const total = prot*4 + gluc*4 + lip*9;
    if (!total) return; // Évite la division par zéro si les données sont vides
    const slices = [
      { pct: prot*4/total, color:'#4ade80', label:'Protéines' },
      { pct: gluc*4/total, color:'#facc15', label:'Glucides' },
      { pct: lip*9/total,  color:'#f97316', label:'Lipides' },
    ];
    let cumul = 0, paths = '';
    const cx = 55, cy = 55, r = 42; // Centre et rayon du cercle SVG
 
    // Construction des chemins SVG pour chaque portion du camembert
    slices.forEach(({ pct, color }) => {
      if (pct <= 0) return;
      const s = cumul * 2 * Math.PI - Math.PI/2; // Angle de départ (en radians)
      cumul += pct;
      const e = cumul * 2 * Math.PI - Math.PI/2; // Angle de fin
      const x1 = cx+r*Math.cos(s), y1 = cy+r*Math.sin(s);
      const x2 = cx+r*Math.cos(e), y2 = cy+r*Math.sin(e);
      // L'indicateur large-arc-flag (pct > 0.5 → 1) gère les arcs supérieurs à 180°
      paths += `<path d="M${cx},${cy} L${x1.toFixed(2)},${y1.toFixed(2)} A${r},${r} 0 ${pct>0.5?1:0},1 ${x2.toFixed(2)},${y2.toFixed(2)} Z" fill="${color}" opacity="0.85"/>`;
    });
    document.getElementById('pie-svg').innerHTML = paths;
 
    // Génération de la légende avec couleur et pourcentage de chaque macronutriment
    document.getElementById('pie-legend').innerHTML = slices.map(s =>
            `<div class="pie-leg-item"><div class="pie-leg-dot" style="background:${s.color}"></div>${s.label} ${Math.round(s.pct*100)}%</div>`
    ).join('');
  }
 
  // ──────────────────────────────────────────────
  // SAVE — Sauvegarde du plan en base de données
  // ──────────────────────────────────────────────
  async function savePlan() {
    if (!currentPlan) return; // Rien à sauvegarder si aucun plan n'a été généré
    const btn = document.getElementById('btn-save');
    btn.disabled = true;
    btn.textContent = 'Sauvegarde…';
 
    // Récupération de l'identifiant utilisateur depuis toutes les sources disponibles
    const uid = USER_ID
            || params.get('user_id')
            || sessionStorage.getItem('naha_user_id')
            || localStorage.getItem('naha_user_id');
 
    // Redirige vers le login si l'utilisateur n'est pas authentifié
    if (!uid) {
      showToast('✗ Non connecté — redirige vers login', 'error');
      setTimeout(() => window.location.href = 'login.html', 1500);
      btn.disabled = false;
      btn.textContent = '↓ Sauvegarder';
      return;
    }
 
    try {
      // Envoi du plan complet avec le profil et la prédiction de sommeil au serveur
      const res = await fetch(`${API_BASE}/save_plan.php`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify({
          profil,
          plan: currentPlan,
          temps_sommeil: parseFloat(sessionStorage.getItem('naha_sleep_prediction')) || null,
        }),
      });
      const data = await res.json();
      if (data.error) throw new Error(data.error);
 
      showToast('✓ Plan sauvegardé avec succès !', 'success');
      btn.textContent = '✓ Sauvegardé';
 
      // Redirige vers le tableau de bord après confirmation visuelle
      setTimeout(() => {
        window.location.href = `dashboard.html`;
      }, 1800);
 
    } catch(e) {
      showToast('✗ Erreur : ' + e.message);
      btn.disabled = false;
      btn.textContent = '↓ Sauvegarder';
    }
  }
 
  // ──────────────────────────────────────────────
  // REGENERATE — Relance une nouvelle génération de plan
  // ──────────────────────────────────────────────
  function regenerate() {
    currentPlan = null; // Réinitialise le plan courant
    document.getElementById('screen-result').classList.remove('show'); // Masque les résultats
    document.getElementById('screen-loader').style.display = 'flex';   // Affiche le loader
    setProgress(0);    // Remet la progression à zéro
    activateStep(1);   // Repart depuis l'étape 1
    generate();        // Relance le processus complet
  }
 
  // ──────────────────────────────────────────────
  // HELPERS — Fonctions utilitaires
  // ──────────────────────────────────────────────
 
  // Affiche un message d'erreur bloquant en masquant le loader
  function showError(msg) {
    document.getElementById('screen-loader').style.display = 'none';
    const box = document.getElementById('error-box');
    box.textContent = msg;
    box.classList.add('show');
  }
 
  // Affiche une notification toast temporaire (succès ou erreur) pendant 3.5 secondes
  function showToast(msg, type = '') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show' + (type ? ' '+type : '');
    setTimeout(() => t.classList.remove('show'), 3500);
  }
 
  // Pause asynchrone utilisée pour synchroniser les animations avec les étapes de chargement
  function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }
 
  // ── AUTH GUARD — Vérification de l'authentification avant tout ──
  // Si pas de token valide, sauvegarde l'URL cible et redirige vers la page de connexion
  if (!getToken()) {
    localStorage.setItem('naha_redirect', window.location.href);
    window.location.href = 'login.html';
  } else {
    generate(); // Token présent : lance la génération du plan
  }