 // ──────────────────────────────────────────────
  // CONFIG — Détection de l'environnement et URL de l'API
  // ──────────────────────────────────────────────
  const _local = ['localhost','127.0.0.1'].includes(window.location.hostname);
  // Chemin de l'API adapté selon l'environnement : développement local ou production
  const API = window.location.origin + (_local ? '/SD4/IA-NAHA/Application/api' : '/IA-NAHA/Application/api');
 
  // ──────────────────────────────────────────────
  // NAVIGATION — Basculement entre les onglets Connexion / Inscription
  // ──────────────────────────────────────────────
  function switchTab(tab, btn) {
      // Désactive tous les onglets et panneaux avant d'activer celui sélectionné
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('panel-' + tab).classList.add('active');
  }
 
  // ──────────────────────────────────────────────
  // UX — Affichage / masquage du mot de passe
  // ──────────────────────────────────────────────
  function toggleEye(id, btn) {
      const input = document.getElementById(id);
      // Bascule entre "password" (masqué) et "text" (visible)
      input.type = input.type === 'password' ? 'text' : 'password';
      // Met à jour l'icône du bouton selon l'état actuel
      btn.textContent = input.type === 'password' ? '👁' : '🙈';
  }
 
  // ──────────────────────────────────────────────
  // SÉCURITÉ — Indicateur visuel de robustesse du mot de passe
  // ──────────────────────────────────────────────
  function checkStrength(val) {
      const segs = ['s1','s2','s3','s4']; // 4 segments colorés représentant la force
      // Réinitialise tous les segments avant le recalcul
      segs.forEach(s => {
          document.getElementById(s).className = 'strength-seg';
      });
      if (!val) return; // Rien à afficher si le champ est vide
 
      // Calcul du score selon les critères de sécurité (1 point par critère rempli)
      let score = 0;
      if (val.length >= 8) score++;          // Longueur minimale
      if (/[A-Z]/.test(val)) score++;        // Au moins une majuscule
      if (/[0-9]/.test(val)) score++;        // Au moins un chiffre
      if (/[^A-Za-z0-9]/.test(val)) score++; // Au moins un caractère spécial
 
      // Classe CSS appliquée selon le niveau : faible (1), moyen (2), fort (3-4)
      const cls = score <= 1 ? 'weak' : score <= 2 ? 'medium' : 'strong';
      // Colorie autant de segments que le score obtenu
      for (let i = 0; i < score; i++) {
          document.getElementById(segs[i]).classList.add(cls);
      }
  }
 
  // ──────────────────────────────────────────────
  // UI HELPERS — Alertes et état de chargement des boutons
  // ──────────────────────────────────────────────
 
  // Affiche un message d'alerte (erreur ou succès) pendant 5 secondes puis le masque
  function showAlert(id, msg, type = 'error') {
      const el = document.getElementById(id);
      el.textContent = msg;
      el.className = `alert alert-${type} show`;
      setTimeout(() => el.classList.remove('show'), 5000);
  }
 
  // Active ou désactive l'état de chargement d'un bouton (désactivation + spinner)
  function setLoading(btn, spinner, txt, label, loading) {
      document.getElementById(btn).disabled = loading;
      document.getElementById(spinner).style.display = loading ? 'block' : 'none';
      document.getElementById(txt).textContent = loading ? '' : label; // Vide le texte pendant le chargement
  }
 
  // ──────────────────────────────────────────────
  // CONNEXION — Authentification d'un utilisateur existant
  // ──────────────────────────────────────────────
  async function doLogin() {
      const email    = document.getElementById('login-email').value.trim();
      const password = document.getElementById('login-password').value;
 
      // Validation basique : les deux champs doivent être remplis
      if (!email || !password) {
          showAlert('login-error', 'Remplis tous les champs.');
          return;
      }
 
      setLoading('btn-login', 'login-spinner', 'login-txt', 'Se connecter', true);
 
      try {
          const res = await fetch(`${API}/login.php`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ email, password }),
          });
          // Lecture en texte brut d'abord pour mieux gérer les erreurs serveur non-JSON
          const text = await res.text();
          let data;
          try { data = JSON.parse(text); }
          catch(e) { throw new Error(text.substring(0, 300)); } // Affiche les 300 premiers caractères de l'erreur
 
          if (data.error) throw new Error(data.error);
 
          // Sauvegarde des données de session en localStorage pour persistance entre les pages
          localStorage.setItem('naha_user_id', data.user_id);
          localStorage.setItem('naha_prenom', data.prenom);
          localStorage.setItem('naha_token', data.token);
          localStorage.setItem('naha_email', email);
 
          showAlert('login-success', '✓ Connexion réussie ! Redirection…', 'success');
          // Délai court pour permettre à l'utilisateur de voir le message de succès
          setTimeout(() => {
              window.location.href = `dashboard.html`;
          }, 1200);
 
      } catch(e) {
          showAlert('login-error', e.message || 'Erreur de connexion.');
      } finally {
          // Réactive toujours le bouton, que la connexion ait réussi ou échoué
          setLoading('btn-login', 'login-spinner', 'login-txt', 'Se connecter', false);
      }
  }
 
  // ──────────────────────────────────────────────
  // INSCRIPTION — Création d'un nouveau compte utilisateur
  // ──────────────────────────────────────────────
  async function doRegister() {
      const prenom   = document.getElementById('reg-prenom').value.trim();
      const nom      = document.getElementById('reg-nom').value.trim();
      const email    = document.getElementById('reg-email').value.trim();
      const password = document.getElementById('reg-password').value;
      const confirm  = document.getElementById('reg-confirm').value;
      // Vérifie que la case CGU a été cochée (classe "checked" ajoutée par le JS du toggle)
      const terms    = document.getElementById('terms-box').classList.contains('checked');
 
      // Validations côté client avant d'appeler l'API
      if (!prenom || !nom || !email || !password) {
          showAlert('register-error', 'Remplis tous les champs.');
          return;
      }
      if (password !== confirm) {
          showAlert('register-error', 'Les mots de passe ne correspondent pas.');
          return;
      }
      if (password.length < 8) {
          showAlert('register-error', 'Le mot de passe doit faire au moins 8 caractères.');
          return;
      }
      if (!terms) {
          showAlert('register-error', 'Accepte les conditions d\'utilisation.');
          return;
      }
 
      setLoading('btn-register', 'register-spinner', 'register-txt', 'Créer mon compte', true);
 
      try {
          const res = await fetch(`${API}/register.php`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ prenom, nom, email, password }),
          });
          // Lecture en texte brut pour une meilleure gestion des erreurs serveur
          const text = await res.text();
          let data;
          try { data = JSON.parse(text); }
          catch(e) { throw new Error(text.substring(0, 300)); }
 
          if (data.error) throw new Error(data.error);
 
          showAlert('register-success', '✓ Compte créé ! Redirection vers le formulaire…', 'success');
          // Délai pour afficher le message puis stocker la session et rediriger vers l'onboarding
          setTimeout(() => {
              localStorage.setItem('naha_user_id', data.user_id);
              localStorage.setItem('naha_token', data.token);
              // Utilise les données retournées par l'API, ou les valeurs saisies en fallback
              localStorage.setItem('naha_prenom', data.prenom || prenom);
              localStorage.setItem('naha_email', data.email || email);
              window.location.href = `onboarding.html`; // Redirige vers le formulaire de profil
          }, 1500);
 
      } catch(e) {
          showAlert('register-error', e.message || 'Erreur lors de l\'inscription.');
      } finally {
          // Réactive toujours le bouton en fin de traitement
          setLoading('btn-register', 'register-spinner', 'register-txt', 'Créer mon compte', false);
      }
  }
 
  // ──────────────────────────────────────────────
  // ACCESSIBILITÉ — Soumission du formulaire via la touche Entrée
  // ──────────────────────────────────────────────
  // Détecte la touche Entrée et déclenche la bonne action selon l'onglet actif
  document.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
          const active = document.querySelector('.panel.active');
          if (active.id === 'panel-login') doLogin();
          else doRegister();
      }
  });
 