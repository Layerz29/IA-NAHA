// ──────────────────────────────────────────────
  // CONFIG — Détection de l'environnement et variables globales
  // ──────────────────────────────────────────────
  const _local = ['localhost','127.0.0.1'].includes(window.location.hostname);
  // URL de l'API adaptée selon l'environnement (local ou production)
  const API      = window.location.origin + (_local ? '/SD4/IA-NAHA/Application/api' : '/IA-NAHA/Application/api');
  const TOTAL    = 13;  // Nombre total de questions dans le formulaire d'onboarding
  let current    = 0;   // Index de la question actuellement affichée (commence à 0)
 
  // Récupération de l'identifiant utilisateur depuis le stockage local ou de session
  const USER_ID   = localStorage.getItem('naha_user_id') || sessionStorage.getItem('naha_user_id') || null;
  const getToken  = () => localStorage.getItem('naha_token') || '';
  // Génère les en-têtes HTTP avec le token d'authentification pour les requêtes sécurisées
  const authHeaders = (extra={}) => ({ 'Content-Type':'application/json', 'Authorization':'Bearer '+getToken(), 'X-Auth-Token': getToken(), ...extra });
 
  // ──────────────────────────────────────────────
  // DOTS — Initialisation des indicateurs de progression
  // ──────────────────────────────────────────────
  // Génère dynamiquement les points de navigation (un par question)
  // Le premier point est actif par défaut au démarrage
  function initDots() {
      const wrap = document.getElementById('step-dots');
      wrap.innerHTML = '';
      for (let i = 0; i < TOTAL; i++) {
          const d = document.createElement('div');
          d.className = 'dot' + (i === 0 ? ' active' : '');
          wrap.appendChild(d);
      }
  }
  initDots();
  document.getElementById('nav-total').textContent = TOTAL; // Affiche le nombre total d'étapes
 
  // ──────────────────────────────────────────────
  // UI — Mise à jour de la barre de progression et des indicateurs
  // ──────────────────────────────────────────────
  function updateUI() {
      // Calcul du pourcentage d'avancement (question courante + 1 car current est base 0)
      const pct = Math.round(((current + 1) / TOTAL) * 100);
      document.getElementById('progress-fill').style.width = pct + '%';
      document.getElementById('nav-current').textContent = current + 1;
      document.getElementById('bottom-pct').textContent = pct + '%';
 
      // Met à jour l'état visuel de chaque point : done (passé), active (courant), ou neutre
      document.querySelectorAll('.dot').forEach((d, i) => {
          d.className = 'dot' + (i < current ? ' done' : i === current ? ' active' : '');
      });
  }
 
  // ──────────────────────────────────────────────
  // LAYOUT — Ajustement dynamique de la hauteur du conteneur de questions
  // ──────────────────────────────────────────────
  // Évite les problèmes de scroll sur les questions dont le contenu est plus grand que l'écran
  function updateWrapHeight() {
      const active = document.querySelector('.question.active');
      const wrap   = document.getElementById('questions-wrap');
      const main   = document.querySelector('.main');
      if (active && wrap) {
          // Adapte la hauteur minimale du conteneur à la hauteur réelle de la question active
          wrap.style.minHeight = active.scrollHeight + 'px';
      }
      if (main) main.scrollTop = 0; // Remonte en haut à chaque changement de question
  }
 
  // ──────────────────────────────────────────────
  // NAVIGATION — Passage à la question suivante / précédente
  // ──────────────────────────────────────────────
 
  // Passe à la question suivante après validation, avec une animation de sortie vers le haut
  function next() {
      if (!validate(current)) return; // Bloque la navigation si la question n'est pas valide
 
      const qs = document.querySelectorAll('.question');
      qs[current].classList.add('exit-up'); // Animation CSS de sortie
 
      setTimeout(() => {
          qs[current].classList.remove('active', 'exit-up');
          if (current < TOTAL - 1) {
              current++;
              qs[current].classList.add('active');
              updateUI();
              focusQuestion(current); // Place le curseur sur le premier champ de la question
              updateWrapHeight();
          }
      }, 300); // Délai calé sur la durée de l'animation CSS
  }
 
  // Revient à la question précédente sans validation (navigation libre en arrière)
  function prev() {
      if (current === 0) return; // Déjà à la première question, rien à faire
      const qs = document.querySelectorAll('.question');
      qs[current].classList.remove('active');
      current--;
      qs[current].classList.add('active');
      updateUI();
      focusQuestion(current);
      updateWrapHeight();
  }
 
  // Place automatiquement le focus sur le premier champ input de la question affichée
  function focusQuestion(idx) {
      setTimeout(() => {
          const inp = document.querySelector(`.question[data-q="${idx}"] input`);
          if (inp) inp.focus();
      }, 400); // Délai pour attendre la fin de l'animation d'entrée
  }
 
  // ──────────────────────────────────────────────
  // VALIDATION — Vérification des réponses avant de passer à la suite
  // ──────────────────────────────────────────────
 
  // Affiche ou efface le message d'erreur et colore la bordure du champ concerné
  function setError(id, msg) {
      const el  = document.getElementById('err-' + id);
      const inp = document.getElementById('q-' + id);
      if (el)  el.textContent = msg;
      if (inp) inp.style.borderColor = msg ? '#ef4444' : ''; // Rouge si erreur, normal sinon
  }
 
  // Valide la réponse à la question d'index idx selon des règles métier spécifiques
  function validate(idx) {
      switch(idx) {
          case 0: {
              // Prénom : champ obligatoire non vide
              const v = document.getElementById('q-prenom').value.trim();
              return !!v;
          }
          case 1: {
              // Âge : nombre entier entre 10 et 110 ans
              const v = +document.getElementById('q-age').value;
              if (!v)      { setError('age', 'Veuillez saisir votre âge.'); return false; }
              if (v < 10)  { setError('age', 'Âge minimum : 10 ans.'); return false; }
              if (v > 110) { setError('age', 'Âge maximum : 110 ans.'); return false; }
              setError('age', ''); return true;
          }
          case 2: return !!document.querySelector('.choice[data-group="sexe"].selected'); // Sexe : choix obligatoire
          case 3: {
              // Poids : entre 30 et 250 kg
              const v = +document.getElementById('q-poids').value;
              if (!v)      { setError('poids', 'Veuillez saisir votre poids.'); return false; }
              if (v < 30)  { setError('poids', 'Poids minimum : 30 kg.'); return false; }
              if (v > 250) { setError('poids', 'Poids maximum : 250 kg.'); return false; }
              setError('poids', ''); return true;
          }
          case 4: {
              // Taille : entre 100 et 250 cm
              const v = +document.getElementById('q-taille').value;
              if (!v)      { setError('taille', 'Veuillez saisir votre taille.'); return false; }
              if (v < 100) { setError('taille', 'Taille minimum : 100 cm.'); return false; }
              if (v > 250) { setError('taille', 'Taille maximum : 250 cm.'); return false; }
              setError('taille', ''); return true;
          }
          case 5: return !!document.querySelector('.choice[data-group="activite"].selected'); // Niveau d'activité : choix obligatoire
          case 6: return true; // Niveau de stress : toujours valide (counter initialisé à 5 par défaut)
          case 7: return !!document.querySelector('.choice[data-group="activity_type"].selected'); // Type de sport : choix obligatoire
          case 8: {
              // Variables ML : durée de séance, pas quotidiens et hydratation — tous obligatoires avec plages définies
              const dur = +document.getElementById('q-duration').value;
              const stp = +document.getElementById('q-steps').value;
              const hyd = +document.getElementById('q-hydration').value;
              let ok = true;
              if (!dur || dur < 5)   { setError('duration', 'Minimum 5 minutes par séance.'); ok = false; }
              else if (dur > 240)    { setError('duration', 'Maximum 4h (240 min) par séance.'); ok = false; }
              else                   { setError('duration', ''); }
              if (!stp || stp < 500)  { setError('steps', 'Minimum 500 pas par jour.'); ok = false; }
              else if (stp > 40000)   { setError('steps', 'Maximum 40 000 pas par jour.'); ok = false; }
              else                    { setError('steps', ''); }
              if (!hyd || hyd < 0.5) { setError('hydration', 'Minimum 0,5 L par jour.'); ok = false; }
              else if (hyd > 5)      { setError('hydration', 'Maximum 5 L par jour — au-delà c\'est dangereux.'); ok = false; }
              else                   { setError('hydration', ''); }
              return ok;
          }
          case 9:  return !!document.querySelector('.choice[data-group="smoking_status"].selected'); // Statut tabac : choix obligatoire
          case 10: return true; // Objectifs : champ optionnel, toujours valide
          case 11: return true; // Restrictions alimentaires : champ optionnel, toujours valide
          case 12: return !!(
              // Dernière question : durée du plan ET nombre de repas doivent être sélectionnés
              document.querySelector('.choice[data-group="duree"].selected') &&
              document.querySelector('.choice[data-group="repas"].selected')
          );
          default: return true;
      }
  }
 
  // ──────────────────────────────────────────────
  // CHOIX — Sélection d'une option dans les boutons de choix
  // ──────────────────────────────────────────────
  // Désélectionne tous les choix du même groupe, puis sélectionne celui cliqué
  function selectChoice(el) {
      const group = el.dataset.group;
      document.querySelectorAll(`.choice[data-group="${group}"]`)
          .forEach(c => c.classList.remove('selected'));
      el.classList.add('selected');
  }
 
  // ──────────────────────────────────────────────
  // COLLECTE — Récupération de toutes les réponses du formulaire
  // ──────────────────────────────────────────────
  // Retourne un objet structuré avec toutes les données saisies, prêt à être envoyé à l'API
  function collectData() {
      // Helper : récupère la valeur sélectionnée d'un groupe de choix
      // Si multi=true, retourne toutes les valeurs sélectionnées séparées par des virgules
      const getSelected = (group, multi = false) => {
          const els = [...document.querySelectorAll(
              multi ? `.mchip[data-group="${group}"].selected`
                  : `.choice[data-group="${group}"].selected`
          )];
          if (multi) return els.map(e => e.dataset.val).join(', ');
          return els[0]?.dataset.val || '';
      };
 
      return {
          prenom:           document.getElementById('q-prenom').value.trim(),
          age:              document.getElementById('q-age').value,
          sexe:             getSelected('sexe'),
          poids:            document.getElementById('q-poids').value,
          taille:           document.getElementById('q-taille').value,
          activite:         getSelected('activite'),
          // Variables utilisées par le modèle ML de prédiction du sommeil
          stress_level:     document.getElementById('q-stress').value,
          activity_type:    getSelected('activity_type'),
          duration_minutes: document.getElementById('q-duration').value,
          daily_steps:      document.getElementById('q-steps').value,
          hydration_level:  document.getElementById('q-hydration').value,
          smoking_status:   getSelected('smoking_status'),
          // Préférences nutritionnelles (sélection multiple possible)
          objectif:         getSelected('objectifs', true),
          restrictions:     getSelected('restrictions', true),
          allergies:        document.getElementById('q-allergies').value.trim(),
          duree:            getSelected('duree'),
          repas:            getSelected('repas'),
      };
  }
 
  // ──────────────────────────────────────────────
  // COUNTER — Contrôle du niveau de stress par boutons +/-
  // ──────────────────────────────────────────────
  // Incrémente ou décrémente le compteur de stress (limité entre 1 et 10)
  function changeCounter(key, delta) {
      const inp = document.getElementById('q-' + key);
      const lbl = document.getElementById(key + '-val');
      let val = parseInt(inp.value) + delta;
      val = Math.max(1, Math.min(10, val)); // Borne la valeur entre 1 et 10
      inp.value = val;
      lbl.textContent = val; // Met à jour l'affichage visuel du compteur
  }
 
  // ──────────────────────────────────────────────
  // SOUMISSION — Envoi du profil et redirection vers la génération du plan
  // ──────────────────────────────────────────────
  async function submitProfile() {
      if (!validate(12)) return; // Vérifie la dernière question avant soumission
 
      document.getElementById('loader').classList.add('show'); // Affiche le loader
 
      const data = collectData(); // Collecte toutes les réponses du formulaire
 
      try {
          // Sauvegarde du profil en base de données uniquement si l'utilisateur est connecté
          if (USER_ID) {
              await fetch(`${API}/save_profile.php`, {
                  method: 'POST',
                  headers: authHeaders(),
                  body: JSON.stringify(data),
              });
          }
 
          // Appel au modèle ML pour prédire la durée de sommeil selon le profil
          // Exécuté en try/catch séparé pour ne pas bloquer la suite si le service est indisponible
          try {
              const sleepRes = await fetch(`${API}/predict_sleep.php`, {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify(data),
              });
              const sleepData = await sleepRes.json();
              if (sleepData.success) {
                  // Stocke la prédiction en sessionStorage pour l'afficher dans le dashboard
                  sessionStorage.setItem('naha_sleep_prediction', sleepData.sleep_hours);
              }
          } catch(e) {
              console.warn('Prédiction sommeil indisponible :', e.message); // Non bloquant
          }
 
          // Sauvegarde du profil et de l'identifiant en sessionStorage pour la page de génération
          sessionStorage.setItem('naha_profil', JSON.stringify(data));
          sessionStorage.setItem('naha_user_id', USER_ID || '');
 
          // Redirige vers la page de génération du plan nutritionnel
          window.location.href = 'generate.html';
 
      } catch(e) {
          document.getElementById('loader').classList.remove('show');
          alert('Erreur : ' + e.message);
      }
  }
 
  // ──────────────────────────────────────────────
  // CLAVIER — Raccourcis clavier pour une navigation rapide
  // ──────────────────────────────────────────────
  document.addEventListener('keydown', e => {
      // Entrée : soumet le profil sur la dernière question, sinon passe à la suivante
      if (e.key === 'Enter') {
          if (current === TOTAL - 1) submitProfile();
          else next();
      }
      // Retour arrière : revient à la question précédente (sauf si un champ texte est actif)
      if (e.key === 'Backspace' && e.target.tagName !== 'INPUT') prev();
 
      // Raccourcis lettres/chiffres pour sélectionner rapidement une option de choix
      // Ex : 'h' pour homme, 'f' pour femme, '1' pour sédentaire, etc.
      const keyMap = { 'h':'homme','f':'femme','a':'autre', '1':'sedentaire','2':'leger','3':'modere','4':'actif','5':'tres_actif' };
      if (keyMap[e.key.toLowerCase()]) {
          const el = document.querySelector(`.choice[data-val="${keyMap[e.key.toLowerCase()]}"]`);
          // N'applique le raccourci que si la question contenant ce choix est actuellement active
          if (el && document.querySelector(`.question[data-q="${current}"].active`)) {
              const group = el.dataset.group;
              document.querySelectorAll(`.choice[data-group="${group}"]`).forEach(c => c.classList.remove('selected'));
              el.classList.add('selected');
          }
      }
  });
 
  // ──────────────────────────────────────────────
  // RÉCAPITULATIF — Affichage d'un résumé des réponses avant soumission
  // ──────────────────────────────────────────────
 
  // Dictionnaire des libellés affichés dans l'écran de récapitulatif
  const RECAP_LABELS = {
      prenom:'Prénom', age:'Âge', sexe:'Sexe', poids:'Poids', taille:'Taille',
      activite:'Niveau d\'activité', stress_level:'Stress', activity_type:'Sport principal',
      duration_minutes:'Durée séance', daily_steps:'Pas / jour', hydration_level:'Hydratation',
      smoking_status:'Tabac', objectif:'Objectifs', restrictions:'Restrictions',
      allergies:'Allergies', duree:'Durée du plan', repas:'Repas / jour',
  };
 
  // Dictionnaire des unités affichées après chaque valeur dans le récapitulatif
  const RECAP_UNITS = {
      age:' ans', poids:' kg', taille:' cm', duration_minutes:' min',
      daily_steps:' pas', hydration_level:' L/j', duree:' jour(s)', repas:' repas/j', stress_level:'/10',
  };
 
  // Affiche l'écran de récapitulatif avec toutes les réponses formatées
  // Ne s'affiche que si la dernière question est valide
  function showRecap() {
      if (!validate(12)) return;
      const data = collectData();
      const content = document.getElementById('recap-content');
      // Génère une carte par champ renseigné, en filtrant les valeurs vides
      content.innerHTML = Object.entries(data)
          .filter(([, v]) => v !== '' && v !== null && v !== undefined)
          .map(([k, v]) => `
              <div style="background:var(--bg-soft);border:1px solid var(--border);border-radius:14px;padding:.9rem 1.1rem;">
                  <div style="font-size:.65rem;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);margin-bottom:.3rem;font-weight:700">${RECAP_LABELS[k] || k}</div>
                  <div style="font-size:.95rem;font-weight:800;color:var(--ink)">${v}${RECAP_UNITS[k] || ''}</div>
              </div>
          `).join('');
      document.getElementById('recap-screen').style.display = 'flex';
  }
 
  // Masque l'écran de récapitulatif pour revenir au formulaire
  function hideRecap() {
      document.getElementById('recap-screen').style.display = 'none';
  }
 
  // ──────────────────────────────────────────────
  // DÉMARRAGE — Focus initial et ajustement de la hauteur au chargement
  // ──────────────────────────────────────────────
  focusQuestion(0);    // Met le focus sur le premier champ dès l'ouverture de la page
  updateWrapHeight();  // Ajuste la hauteur du conteneur pour la première question