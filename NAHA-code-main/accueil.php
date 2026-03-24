<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>

  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NAHA — Accueil</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700;800&display=swap" rel="stylesheet">

  <!-- Styles -->
  <link rel="stylesheet" href="assets/css/accueil-style.css" />
</head>

<body>

<!-- 👉 Inclusion du header -->
<?php include 'includes/header.php'; ?>


<main id="top">
  <!-- HERO -->
  <section class="hero" data-parallax>
    <div class="container hero__inner" data-animate="fade-up">
      <h1 class="hero__title">NAHA</h1>
      <p class="hero__quote">
        “Les grands accomplissements sont réussis non par la force, mais par la persévérance.”<br>
        <span>Samuel Johnson</span>
      </p>

      <div class="hero__cta">
        <?php if (isset($_SESSION['utilisateur'])): ?>
          <a class="btn big" href="tableau.php">Accéder au tableau de bord</a>
          <a class="btn ghost" href="#features">Découvrir</a>
        <?php else: ?>
          <a class="btn big" href="sinscrire.php">Commencer</a>
          <a class="btn ghost" href="seconnecter.php">Se connecter</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="scroll-progress"></div>
  </section>

  <div class="divider"></div>

  <!-- NOTRE MISSION -->
  <section class="mission" data-animate="fade-up" id="projet">
    <div class="container">
      <h2 class="title">Notre mission</h2>

      <div class="mission__row" data-animate="fade-right">
        <div class="mission__text">
          <p>
            Chez <strong>NAHA</strong>, on veut aider chaque sportif à mieux se comprendre, à progresser jour après jour et à garder la motivation.<br>
            Nos outils statistiques sont faits pour t’accompagner, simplement, dans ton évolution.
          </p>
          <ul class="ticks">
            <li>Suivi clair de tes séances</li>
            <li>Visualisations utiles et simples</li>
            <li>Conseils actionnables chaque semaine</li>
          </ul>
        </div>

        <div class="mission__art" data-animate="fade-left">
          <div class="chart-card">
            <canvas id="chart-perf" height="160" data-lazy-chart="bar"></canvas>
          </div>
          <div class="chart-row">
            <div class="chart-card small">
              <canvas id="chart-gauge" height="110" data-lazy-chart="gauge"></canvas>
            </div>
            <div class="chart-card small">
              <canvas id="chart-mini" height="110" data-lazy-chart="mini"></canvas>
            </div>
          </div>
        </div>
      </div>

      <div class="mission__row mission__row--alt" data-animate="fade-up">
        <div class="mission__art">
          <div class="chart-card soft">
            <canvas id="chart-green" height="130" data-lazy-chart="area"></canvas>
          </div>
        </div>

        <div class="mission__quote">
          <p>“Chaque calorie compte, chaque effort te rapproche de ta meilleure version.<br>
            La donnée, c’est notre langage — la persévérance, notre philosophie.”</p>
        </div>
      </div>
    </div>
  </section>

  <div class="divider"></div>

  <!-- KPIs -->
  <section class="kpis" id="dash" data-animate="fade-up">
    <div class="container kpis__grid">
      <div class="kpi">
        <div class="kpi__num" data-counter="1250">0</div>
        <div class="kpi__label">Séances suivies</div>
      </div>
      <div class="kpi">
        <div class="kpi__num" data-counter="87">0</div>
        <div class="kpi__label">Programmes actifs</div>
      </div>
      <div class="kpi">
        <div class="kpi__num" data-counter="98">0</div>
        <div class="kpi__label">Satisfaction (%)</div>
      </div>
    </div>
  </section>

  <div class="divider"></div>

  <!-- FEATURES -->
  <section class="features" id="features" data-animate="fade-up">
    <div class="container">
      <h3 class="features__title">
        Découvrez notre IA performante et<br>des entraînements personnalisés
      </h3>

      <div class="features__grid">
        <a class="cta" href="IA.php">IA</a>
        <a class="cta cta--right" href="contact.php">Contactez nous !</a>
        <a class="cta" href="tableau.php">Tableau de bord</a>
        <a class="cta cta--right" href="#train">Entraînement</a>
      </div>
    </div>
  </section>

  <div class="divider"></div>

  <!-- Newsletter -->
  <section class="newsletter" id="contact" data-animate="fade-up">
    <div class="container newsletter__card">
      <h3>Reste dans le flow 📈</h3>
      <p>Reçois 1 tip data & perf par semaine. Pas de spam, juré.</p>

      <form id="newsletter-form" class="newsletter-form" method="POST" action="actions/newsletter.php">
          <input type="email" name="email" class="newsletter-input" placeholder="tonemail@exemple.com" required>
          <button type="submit" class="btn">S’abonner</button>
      </form>

      <?php if (!empty($_SESSION['newsletter_msg'])): ?>
          <p class="newsletter-feedback">
              <?= htmlspecialchars($_SESSION['newsletter_msg']) ?>
          </p>
          <?php unset($_SESSION['newsletter_msg']); ?>
      <?php endif; ?>

    </div>
  </section>

  <div class="divider"></div>

  <!-- TEAM -->
  <section class="team" id="team" data-animate="fade-up">
    <div class="container">
      <h2 class="title title--spaced">DÉCOUVREZ&nbsp;&nbsp;NOTRE ÉQUIPE</h2>
      <div class="team__list">
        <span data-animate="pop">Haitham</span>
        <span data-animate="pop">Ahmed</span>
        <span data-animate="pop">Noah</span>
        <span data-animate="pop">Arthur</span>
        <span data-animate="pop">Yann</span>
      </div>
    </div>
  </section>

  <button class="to-top" aria-label="Remonter">↑</button>

</main>

<?php include 'includes/footer.php'; ?>


<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/accueil-script.js"></script>

</body>
</html>
