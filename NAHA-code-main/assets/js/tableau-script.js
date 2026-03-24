document.addEventListener('DOMContentLoaded', () => {
  console.log('TABLEAU JS OK');
    const dash      = window.NAHA_DASH || {};
    const weekLabels = dash.weekLabels || ['L','M','M','J','V','S','D'];
    const weekIn     = dash.weekIn     || [0,0,0,0,0,0,0];
    const weekOut    = dash.weekOut    || [0,0,0,0,0,0,0];
    const weekCardio = dash.weekCardio || [0,0,0,0,0,0,0];
    const macros = dash.macros || { prot: 25, glu: 50, lip: 25 }; // fallback au cas où



  // ===== topbar shadow + progress + back-to-top (même logique que l'accueil)
  const topbar = document.querySelector('.topbar');
  const progress = document.querySelector('.scroll-progress');
  const toTop = document.createElement('button');
  toTop.className = 'to-top';
  toTop.textContent = '↑';
  document.body.appendChild(toTop);

  const onScroll = () => {
    const y = window.scrollY || 0;
    topbar?.classList.toggle('scrolled', y > 8);
    toTop.classList.toggle('show', y > 400);
    if (progress) {
      const h = document.documentElement;
      const ratio = (100 * y / (h.scrollHeight - h.clientHeight)) || 0;
      progress.style.width = ratio + '%';
    }
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  // ===== Animate on scroll
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('is-inview');
        io.unobserve(e.target);
      }
    });
  }, { threshold: .18 });
  document.querySelectorAll('[data-animate]').forEach(el => io.observe(el));

  // ===== Counters
  const counters = document.querySelectorAll('[data-counter]');
  const cIO = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      animateCount(e.target, parseInt(e.target.dataset.counter, 10) || 0);
      cIO.unobserve(e.target);
    });
  }, { threshold: .7 });
  counters.forEach(el => cIO.observe(el));

  function animateCount(el, target) {
    const start = performance.now(), dur = 900, from = 0;
    const step = (t) => {
      const p = Math.min(1, (t - start) / dur);
      const val = Math.floor(from + (target - from) * (1 - Math.pow(1 - p, 3)));
      el.textContent = val.toLocaleString('fr-FR');
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }

  // ===== Charts (lazy)
  if (window.Chart) {
    const chartIO = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (!e.isIntersecting) return;
        const c = e.target, type = c.dataset.chart;
        if (type === 'bars-week') barsWeek(c);
        if (type === 'donut-macros') donutMacros(c);
        if (type === 'line-cardio') lineCardio(c);
        chartIO.unobserve(c);
      });
    }, { threshold: .2 });
    document.querySelectorAll('canvas[data-chart]').forEach(cv => chartIO.observe(cv));
  }

  // ===== Chart configs
  const grid = '#e9ecf3';
  const ticks = '#667085';
  const prot = getCSS('--prot') || '#6e8efb';
  const glu  = getCSS('--glu')  || '#60a5fa';
  const lip  = getCSS('--lip')  || '#faca63';
  const primary = getCSS('--primary') || '#4c5bf9';
  const green   = getCSS('--green')   || '#45c778';

  function baseOpts() {
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { enabled: true } },
      scales: {
        x: { grid: { color: grid }, ticks: { color: ticks } },
        y: { grid: { color: grid }, ticks: { color: ticks }, beginAtZero: true }
      }
    };
  }

  function barsWeek(canvas) {
    const ctx = canvas.getContext('2d');

    const maxY = Math.max(...weekIn, ...weekOut, 2000) + 300; // un peu de marge

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: weekLabels,
        datasets: [
          {
            label: 'Ingérées',
            data: weekIn,
            backgroundColor: primary,
            borderRadius: 8,
            barThickness: 16
          },
          {
            label: 'Dépensées',
            data: weekOut,
            backgroundColor: green,
            borderRadius: 8,
            barThickness: 16
          }
        ]
      },
      options: {
        ...baseOpts(),
        scales: {
          x: { ...baseOpts().scales.x },
          y: {
            ...baseOpts().scales.y,
            suggestedMax: maxY
          }
        }
      }
    });
  }



function donutMacros(canvas) {
  const p = macros.prot || 0;
  const g = macros.glu  || 0;
  const l = macros.lip  || 0;
// ===== Si aucune donnée => afficher un message à la place du donut
if ((p + g + l) === 0) {
    const wrap = canvas.parentElement;
    wrap.innerHTML = `
        <div style="
            text-align:center;
            padding: 55px 10px;
            font-size: 18px;
            font-weight: 600;
            color: #444;
            line-height: 1.5;
        ">
             Ton tableau est encore vide<br>
            <span style="font-weight:700;">Ajoute un aliment pour voir tes macros !</span>
        </div>

    `;
    return; // stoppe la création du donut
}

  new Chart(canvas.getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: ['Protéines', 'Glucides', 'Lipides'],
      datasets: [{
        data: [p, g, l], // ⚠️ ON UTILISE LES GRAMMES DIRECTS
        backgroundColor: [prot, glu, lip],
        borderWidth: 0,
        hoverOffset: 4,
        cutout: '60%' // donut propre
      }]
    },
    options: {
      plugins: {
        legend: { display: false },

        // 🔥 Affichage des grammes directement dans le donut
        datalabels: {
          color: "#111",
          font: { weight: "700", size: 12 },
          formatter: (val) => `${val.toFixed(0)} g`
        },

        // Tooltip = grammes + pourcentage
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const total = p + g + l || 1;
              const pct = (ctx.raw * 100 / total).toFixed(1);
              return `${ctx.label} : ${ctx.raw.toFixed(0)} g (${pct}%)`;
            }
          }
        }
      }
    },
    plugins: [ChartDataLabels]
  });
}



  function lineCardio(canvas) {
    const ctx = canvas.getContext('2d');
    const maxY = Math.max(...weekCardio, 30) + 10;

    new Chart(ctx, {
      type: 'line',
      data: {
        labels: weekLabels,
        datasets: [{
          data: weekCardio,
          borderColor: primary,
          backgroundColor: 'rgba(76,91,249,.12)',
          tension: .35,
          fill: true,
          pointRadius: 3,
          pointBackgroundColor: '#fff',
          borderWidth: 3
        }]
      },
      options: {
        ...baseOpts(),
        scales: {
          x: {
            grid: { color: 'transparent' },
            ticks: { color: ticks }
          },
          y: {
            beginAtZero: true,
            suggestedMax: maxY,
            grid: { color: grid },
            ticks: { color: ticks }
          }
        }
      }
    });
  }


  function getCSS(varName) {
    return getComputedStyle(document.documentElement).getPropertyValue(varName).trim();
  }
});

/* minimal styles pour le bouton top */
(function () {
  const css = `
  .to-top{position:fixed;right:16px;bottom:16px;border:1px solid #dfe3ea;background:#fff;border-radius:10px;
          width:42px;height:42px;font-weight:800;box-shadow:0 10px 20px rgba(0,0,0,.08);display:none;cursor:pointer}
  .to-top.show{display:block}`;
  const s = document.createElement('style'); s.textContent = css; document.head.appendChild(s);
})();