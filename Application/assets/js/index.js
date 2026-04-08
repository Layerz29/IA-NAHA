 // ──────────────────────────────────────────────
  // PERSONNALISATION DE L'INTERFACE SELON L'ÉTAT DE CONNEXION
  // ──────────────────────────────────────────────
 
  // Récupération des données de session depuis le localStorage
  const _uid  = localStorage.getItem('naha_user_id');  // Identifiant de l'utilisateur connecté
  const _name = localStorage.getItem('naha_prenom');   // Prénom pour la personnalisation de l'accueil
 
  // Si un utilisateur est connecté, on adapte l'interface pour lui afficher ses raccourcis
  if (_uid) {
 
    // Remplacement du bouton "Se connecter" dans la navbar par un message de bienvenue + lien dashboard
    document.getElementById('nav-cta').innerHTML = `
      <span style="color:var(--text2);font-size:0.78rem;">Bonjour, ${_name || 'Testeur'} 👋</span>
      <a href="dashboard.html" class="btn-solid">Mon dashboard →</a>
    `;
 
    // Remplacement des boutons d'action dans la section hero (bannière principale)
    // L'utilisateur connecté n'a pas besoin de s'inscrire, il accède directement à son espace
    document.querySelector('.hero-actions').innerHTML = `
      <a href="dashboard.html" class="btn-primary-lg">Accéder à mon dashboard</a>
      <a href="onboarding.html" class="btn-ghost-lg">Nouveau plan</a>
    `;
 
    // Même remplacement dans la section CTA (appel à l'action) en bas de page
    document.querySelector('.cta-actions').innerHTML = `
      <a href="dashboard.html" class="btn-primary-lg">Accéder à mon dashboard</a>
      <a href="onboarding.html" class="btn-ghost-lg">Nouveau plan</a>
    `;
 
    // Mise à jour du lien "Connexion" dans le footer : redirige vers le dashboard si déjà connecté
    const footerLogin = document.querySelector('.footer-links a[href="login.html"]');
    if (footerLogin) { footerLogin.textContent = 'Dashboard'; footerLogin.href = 'dashboard.html'; }
  }
 
  // ──────────────────────────────────────────────
  // ANIMATION D'APPARITION AU SCROLL (Intersection Observer)
  // ──────────────────────────────────────────────
 
  // Crée un observateur qui déclenche l'animation des éléments au moment où ils entrent dans le viewport
  const observer = new IntersectionObserver(entries => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        // Délai en cascade (80ms entre chaque élément) pour un effet d'apparition progressif
        setTimeout(() => entry.target.classList.add('visible'), i * 80);
        // On arrête d'observer l'élément une fois animé pour économiser les ressources
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 }); // L'animation se déclenche dès que 10% de l'élément est visible
 
  // Applique l'observateur à tous les éléments marqués avec la classe CSS "reveal"
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
 