const _uid  = localStorage.getItem('naha_user_id');
  const _name = localStorage.getItem('naha_prenom');

  if (_uid) {
    document.getElementById('nav-cta').innerHTML = `
      <span style="color:var(--text2);font-size:0.78rem;">Bonjour, ${_name || 'toi'} 👋</span>
      <a href="dashboard.html" class="btn-solid">Mon dashboard →</a>
    `;
    document.querySelector('.hero-actions').innerHTML = `
      <a href="dashboard.html" class="btn-primary-lg">Accéder à mon dashboard</a>
      <a href="onboarding.html" class="btn-ghost-lg">Nouveau plan</a>
    `;
    document.querySelector('.cta-actions').innerHTML = `
      <a href="dashboard.html" class="btn-primary-lg">Accéder à mon dashboard</a>
      <a href="onboarding.html" class="btn-ghost-lg">Nouveau plan</a>
    `;
    const footerLogin = document.querySelector('.footer-links a[href="login.html"]');
    if (footerLogin) { footerLogin.textContent = 'Dashboard'; footerLogin.href = 'dashboard.html'; }
  }

  const observer = new IntersectionObserver(entries => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => entry.target.classList.add('visible'), i * 80);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));