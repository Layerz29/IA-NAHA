document.addEventListener('DOMContentLoaded', () => {
  // ===== Smooth scroll for anchors
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const id = a.getAttribute('href');
      if (id.length > 1) {
        const el = document.querySelector(id);
        if (el) {
          e.preventDefault();
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  });

  // ===== Topbar shadow + back-to-top + progress
  const topbar = document.querySelector('.topbar');
  const toTop = document.createElement('button');
  toTop.className = 'to-top';
  toTop.setAttribute('aria-label', 'Remonter');
  toTop.textContent = '↑';
  document.body.appendChild(toTop);

  const progress = document.querySelector('.scroll-progress');

  const onScroll = () => {
    const y = window.scrollY || document.documentElement.scrollTop;
    if (topbar) topbar.classList.toggle('scrolled', y > 8);
    toTop.classList.toggle('show', y > 400);

    if (progress) {
      const h = document.documentElement;
      const max = h.scrollHeight - h.clientHeight;
      const ratio = Math.max(0, Math.min(1, y / (max || 1)));
      progress.style.width = (ratio * 100).toFixed(2) + '%';
    }
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  // ===== Parallax on hero inner
  const heroInner = document.querySelector('.hero[data-parallax] .hero__inner');
  if (heroInner) {
    window.addEventListener('scroll', () => {
      const speed = 0.25; // soft
      const offset = (window.scrollY || 0) * speed;
      heroInner.style.transform = `translate3d(0, ${offset}px, 0)`;
    }, { passive: true });
  }

  // ===== IntersectionObserver for animations
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('is-inview');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.18 });

  document.querySelectorAll('[data-animate]').forEach(el => io.observe(el));

  // ===== Lazy-init charts when visible
  const lazyCharts = document.querySelectorAll('[data-lazy-chart]');
  if (lazyCharts.length) {
    const chartIO = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          initChart(e.target);
          chartIO.unobserve(e.target);
        }
      });
    }, { threshold: 0.2 });
    lazyCharts.forEach(c => chartIO.observe(c));
  }

  // ===== Minimal charts (Chart.js)
  function initChart(canvas) {
    if (!window.Chart) return;
    const ctx = canvas.getContext('2d');
    const type = canvas.dataset.lazyChart;

    const blue = '#4c5bf9';
    const blueSoft = 'rgba(76,91,249,.18)';
    const green = '#45c778';
    const grid = '#e9ecf3';

    const baseOpts = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { enabled: true } },
      scales: {
        x: { grid: { color: grid }, ticks: { color: '#667085' } },
        y: { grid: { color: grid }, ticks: { color: '#667085' }, beginAtZero: true }
      }
    };

    if (type === 'bar') {
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'],
          datasets: [{ data: [32,45,41,50,62,48,54], backgroundColor: blue, borderRadius: 8, barThickness: 18 }]
        },
        options: { ...baseOpts, scales: { ...baseOpts.scales, y: { ...baseOpts.scales.y, suggestedMax: 70 } }, animation: { duration: 700 } }
      });
    }

    if (type === 'gauge') {
      new Chart(ctx, {
        type: 'doughnut',
        data: { datasets: [{ data: [72, 28], backgroundColor: [blue, blueSoft], borderWidth: 0, cutout: '70%' }] },
        options: { rotation: -90, circumference: 180, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
      });
    }

    if (type === 'mini') {
      new Chart(ctx, {
        type: 'bar',
        data: { labels: ['A','B','C','D','E'], datasets: [{ data: [5,8,6,9,7], backgroundColor: blue, borderRadius: 6, barThickness: 10 }] },
        options: { ...baseOpts, scales: { x: { display:false }, y: { display:false, beginAtZero:true, suggestedMax: 10 } } }
      });
    }

    if (type === 'area') {
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: Array.from({length: 12}, (_,i)=>i+1),
          datasets: [{ data: [12,14,13,16,18,17,21,22,20,23,25,27], borderColor: green, backgroundColor: 'rgba(69,199,120,.15)', tension:.35, fill:true, pointRadius:0, borderWidth:3 }]
        },
        options: { ...baseOpts, scales: { x: { display:false }, y: { display:false } } }
      });
    }
  }

  // ===== Animated counters
  const counters = document.querySelectorAll('[data-counter]');
  if (counters.length) {
    const cIO = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          animateCount(e.target, parseInt(e.target.dataset.counter, 10) || 0);
          cIO.unobserve(e.target);
        }
      });
    }, { threshold: 0.6 });
    counters.forEach(el => cIO.observe(el));
  }
  function animateCount(el, target) {
    const dur = 900; const start = performance.now();
    const from = 0;
    const step = (t) => {
      const p = Math.min(1, (t - start) / dur);
      const val = Math.floor(from + (target - from) * (1 - Math.pow(1 - p, 3))); // ease-out cubic
      el.textContent = val.toLocaleString('fr-FR');
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }

  // ===== Newsletter AJAX
  const newsForm = document.getElementById('newsletter-form');
  const newsMsg  = document.getElementById('newsletter-msg');
  if (newsForm) {
    newsForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      newsMsg.textContent = 'Envoi…';
      const fd = new FormData(newsForm);
      try {
        const res = await fetch(newsForm.action || 'api/newsletter.php', { method: 'POST', body: fd });
        const data = await res.json().catch(() => ({}));
        if (res.ok) {
          newsMsg.textContent = data.message || 'Merci ! Tu es bien inscrit.';
          newsForm.reset();
        } else {
          newsMsg.textContent = data.error || 'Oups, réessaie plus tard.';
        }
      } catch {
        newsMsg.textContent = 'Connexion impossible.';
      }
    });
  }
});