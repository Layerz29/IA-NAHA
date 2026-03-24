<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/bdd.php';
$bdd = getBD();

if (!isset($_SESSION['utilisateur'])) {
    header('Location: seconnecter.php');
    exit;
}

$idUser = (int)($_SESSION['utilisateur']['id'] ?? $_SESSION['utilisateur']['id_utilisateur']);

try {
    $weekLabels  = [];
    $weekIn      = [];
    $weekOut     = [];
    $weekCardio  = [];

    for ($i = 6; $i >= 0; $i--) {
        $d = new DateTime("-$i days");
        $dateStr = $d->format('Y-m-d');

        $jours = [1=>'L','M','M','J','V','S','D'];
        $label = $jours[(int)$d->format('N')];

        $sqlInDay = "
            SELECT SUM(p.energie_kcal * c.quantite / 100) AS kcal_in
            FROM consommation c
            JOIN produits p ON p.id_produit = c.id_produit
            WHERE c.id_utilisateur = :u
              AND DATE(c.date_conso) = :d
        ";
        $stmtInDay = $bdd->prepare($sqlInDay);
        $stmtInDay->execute(['u' => $idUser, 'd' => $dateStr]);
        $kIn = (int)($stmtInDay->fetchColumn() ?: 0);

        $sqlOutDay = "
            SELECT SUM(s.kcal_h_70kg * (a.duree_minutes / 60)) AS kcal_out
            FROM activite a
            JOIN sports s ON s.id_sport = a.id_sport
            WHERE a.id_utilisateur = :u
              AND DATE(a.date_sport) = :d
        ";
        $stmtOutDay = $bdd->prepare($sqlOutDay);
        $stmtOutDay->execute(['u' => $idUser, 'd' => $dateStr]);
        $kOut = (int)($stmtOutDay->fetchColumn() ?: 0);

        $sqlCardioDay = "
            SELECT SUM(a.duree_minutes) AS minutes
            FROM activite a
            WHERE a.id_utilisateur = :u
              AND DATE(a.date_sport) = :d
        ";
        $stmtCardioDay = $bdd->prepare($sqlCardioDay);
        $stmtCardioDay->execute(['u' => $idUser, 'd' => $dateStr]);
        $mCardio = (int)($stmtCardioDay->fetchColumn() ?: 0);

        $weekLabels[] = $label;
        $weekIn[]     = $kIn;
        $weekOut[]    = $kOut;
        $weekCardio[] = $mCardio;
    }

    $totalIn      = array_sum($weekIn);
    $totalOut     = array_sum($weekOut);
    $soldeSem     = $totalIn - $totalOut;
    $totalMinutes = array_sum($weekCardio);

    $moyenneJour = $totalIn > 0 ? round($totalIn / 7) : 0;
    $nbSeances   = 0;

    foreach ($weekCardio as $min) {
        if ($min > 0) {
            $nbSeances++;
        }
    }

    $sqlGoal = "
        SELECT *
        FROM objectif_utilisateur
        WHERE id_utilisateur = :id
        ORDER BY date_maj DESC
        LIMIT 1
    ";
    $stmtGoal = $bdd->prepare($sqlGoal);
    $stmtGoal->execute(['id' => $idUser]);
    $goal = $stmtGoal->fetch(PDO::FETCH_ASSOC);

    $sqlIn = "
        SELECT SUM(p.energie_kcal * c.quantite / 100) AS kcal_in
        FROM consommation c
        JOIN produits p ON p.id_produit = c.id_produit
        WHERE c.id_utilisateur = :u
          AND DATE(c.date_conso) = CURDATE()
    ";
    $stmtIn = $bdd->prepare($sqlIn);
    $stmtIn->execute(['u' => $idUser]);
    $kcalIn = (int)($stmtIn->fetchColumn() ?: 0);

    $sqlOut = "
        SELECT SUM(s.kcal_h_70kg * (a.duree_minutes / 60)) AS kcal_out
        FROM activite a
        JOIN sports s ON s.id_sport = a.id_sport
        WHERE a.id_utilisateur = :u
          AND DATE(a.date_sport) = CURDATE()
    ";
    $stmtOut = $bdd->prepare($sqlOut);
    $stmtOut->execute(['u' => $idUser]);
    $kcalOut = (int)($stmtOut->fetchColumn() ?: 0);

    $solde = $kcalIn - $kcalOut;

    $sqlFoods = "
        SELECT p.nom_produit, c.quantite,
               ROUND(p.energie_kcal * c.quantite / 100, 0) AS kcal
        FROM consommation c
        JOIN produits p ON p.id_produit = c.id_produit
        WHERE c.id_utilisateur = :u
          AND DATE(c.date_conso) = CURDATE()
    ";
    $stmtFoods = $bdd->prepare($sqlFoods);
    $stmtFoods->execute(['u' => $idUser]);
    $foodsToday = $stmtFoods->fetchAll(PDO::FETCH_ASSOC);

    $sqlSportToday = "
        SELECT s.nom_sport,
               a.duree_minutes AS duree_min,
               s.kcal_h_70kg * (a.duree_minutes / 60) AS kcal
        FROM activite a
        JOIN sports s ON s.id_sport = a.id_sport
        WHERE a.id_utilisateur = :u
          AND DATE(a.date_sport) = CURDATE()
    ";
    $stmtSportToday = $bdd->prepare($sqlSportToday);
    $stmtSportToday->execute(['u' => $idUser]);
    $sportsToday = $stmtSportToday->fetchAll(PDO::FETCH_ASSOC);

    $sqlLast = "
        SELECT a.date_sport,
               a.duree_minutes AS duree_min,
               s.nom_sport,
               s.kcal_h_70kg * (a.duree_minutes / 60) AS kcal
        FROM activite a
        JOIN sports s ON s.id_sport = a.id_sport
        WHERE a.id_utilisateur = :u
        ORDER BY a.date_sport DESC
        LIMIT 5
    ";
    $stmtLast = $bdd->prepare($sqlLast);
    $stmtLast->execute(['u' => $idUser]);
    $lastActs = $stmtLast->fetchAll(PDO::FETCH_ASSOC);

    $sqlMacros = "
        SELECT
          SUM(p.proteines * c.quantite / 100) AS prot,
          SUM(p.glucides * c.quantite / 100)  AS glu,
          SUM(p.lipides  * c.quantite / 100)  AS lip
        FROM consommation c
        JOIN produits p ON p.id_produit = c.id_produit
        WHERE c.id_utilisateur = :u
          AND DATE(c.date_conso) = CURDATE()
    ";
    $stmtMacros = $bdd->prepare($sqlMacros);
    $stmtMacros->execute(['u' => $idUser]);
    $rowMacros = $stmtMacros->fetch(PDO::FETCH_ASSOC) ?: [];

    $macros = [
        'prot' => (float)($rowMacros['prot'] ?? 0),
        'glu'  => (float)($rowMacros['glu']  ?? 0),
        'lip'  => (float)($rowMacros['lip']  ?? 0),
    ];

} catch (PDOException $e) {
    die('Erreur SQL : ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NAHA — Tableau de bord</title>
   <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/accueil-style.css" />
  <link rel="stylesheet" href="assets/css/tableau-style.css" />
  

</head>
<body>

<?php include 'includes/header.php'; ?>


<main class="dash">

  <h1 class="dash-title">Mon suivi journalier</h1>
  <p class="dash-sub">Un coup d’œil sur tes progrès — nutrition, sport, équilibre</p>

  <section class="dash-hero">
    <div class="container">
      <div class="kpis kpis-3">

        <div class="kpi flip-card">
          <div class="flip-inner">
            <div class="flip-front">
              <div class="kpi__icon">🔥</div>
              <div class="kpi__title">Calories ingérées</div>
              <div class="kpi__num" data-counter="<?= $kcalIn ?>">0</div>
              <div class="kpi__delta is-up">Aujourd’hui</div>
            </div>
            <div class="flip-back">
              <h4>Aliments du jour</h4>
              <?php if (empty($foodsToday)): ?>
                <p>Aucune consommation aujourd’hui.</p>
              <?php else: ?>
                <ul class="flip-list">
                <?php foreach ($foodsToday as $f): ?>
                  <li><strong><?= htmlspecialchars($f['nom_produit']) ?></strong> — <?= $f['quantite'] ?> g (<?= $f['kcal'] ?> kcal)</li>
                <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="kpi flip-card">
          <div class="flip-inner">
            <div class="flip-front">
              <div class="kpi__icon">🏋️‍♂️</div>
              <div class="kpi__title">Calories dépensées</div>
              <div class="kpi__num" data-counter="<?= $kcalOut ?>">0</div>
              <div class="kpi__delta is-down">Aujourd’hui</div>
            </div>
            <div class="flip-back">
              <h4>Activités du jour</h4>
              <?php if (empty($sportsToday)): ?>
                <p>Aucune activité aujourd’hui.</p>
              <?php else: ?>
                <ul class="flip-list">
                <?php foreach ($sportsToday as $act): ?>
                  <li><strong><?= htmlspecialchars($act['nom_sport']) ?></strong> — <?= (int)$act['duree_min'] ?> min (<?= (int)$act['kcal'] ?> kcal)</li>
                <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="kpi flip-card">
          <div class="flip-inner">
            <div class="flip-front">
              <div class="kpi__icon">🧮</div>
              <div class="kpi__title">Solde calorique</div>
              <div class="kpi__num" data-counter="<?= $solde ?>">0</div>
              <div class="kpi__delta <?= $solde >= 0 ? 'is-up' : 'is-down' ?>">
                <?= $solde >= 0 ? '+' : '' ?><?= $solde ?> kcal
              </div>
            </div>
            <div class="flip-back">
              <h4>Détails</h4>
              <p>Ingérées : <?= $kcalIn ?> kcal</p>
              <p>Dépensées : <?= $kcalOut ?> kcal</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

<section class="dash-grid">
  <div class="container grid">

    <?php if (!empty($goal)): ?>
    <div class="card goal-card" data-animate="fade-up">
      <div class="goal-row">
        <div class="goal-left">
          <p class="goal-label">Mon objectif calorique</p>
          <h3 class="goal-type"><?= htmlspecialchars($goal['objectif_nom']) ?></h3>
          <p class="goal-text">Maintenance estimée : <strong><?= (int)$goal['maintenance'] ?> kcal / jour</strong></p>
          <p class="goal-text">Dernière mise à jour : <?= (new DateTime($goal['date_maj']))->format('d/m/Y') ?></p>
        </div>
        <div class="goal-right">
          <span class="goal-number"><?= (int)$goal['objectif_kcal'] ?></span>
          <span class="goal-unit">kcal / jour</span>
          <a href="calculateur.php" class="goal-link">Modifier dans le calculateur →</a>
        </div>
      </div>
    </div>
    <?php endif; ?>

<?php if (!empty($goal)):
    $objectif = (int)$goal['objectif_kcal'];
    if ($objectif > 0) {
        $progress = min(200, max(0, round(($kcalIn / $objectif) * 100)));
    } else {
        $progress = 0;
    }
?>
<div class="card" data-animate="fade-up">
    <div class="card__head">
        <h3>Objectif du jour</h3>
        <span class="badge">Nutrition</span>
    </div>
    <div class="card__body mini-goal">
        <p class="mini-goal-sub">Tu as atteint</p>
        <p class="mini-goal-percent"><?= $progress ?>%</p>
        <p class="mini-goal-sub">de ton objectif de <strong><?= $objectif ?> kcal</strong>.</p>
        <div class="mini-bar">
            <div class="mini-bar-fill" style="width: <?= $progress ?>%;"></div>
        </div>

        <?php if ($progress < 90): ?>
          <p class="mini-goal-hint">Il te reste encore un peu de marge ✨</p>
        <?php elseif ($progress <= 110): ?>
          <p class="mini-goal-hint">Tu es pile dans la zone, beau taf 💪</p>
        <?php else: ?>
          <p class="mini-goal-hint">Tu as dépassé ton objectif aujourd’hui 😉</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

    <div class="card" data-animate="fade-up">
      <div class="card__head">
        <h3>Calories / semaine</h3>
        <span class="badge">Derniers 7 jours</span>
      </div>
      <div class="card__body">
        <canvas data-chart="bars-week" height="220"></canvas>
      </div>
      <div class="chart-legend">
        <span><span class="dot legend-out"></span> Ingerées</span>
        <span><span class="dot legend-in"></span> Dépensées</span>
      </div>
    </div>

<div class="card" data-animate="fade-up">
    <div class="card__head">
        <h3>Répartition macros</h3>
        <span class="badge">Jour</span>
    </div>
    <div class="card__body donut-wrap">
        <canvas data-chart="donut-macros" height="220"></canvas>
        <ul class="legend">
          <li><span class="dot dot--prot"></span> Protéine</li>
          <li><span class="dot dot--glu"></span> Glucide</li>
          <li><span class="dot dot--lip"></span> Lipide</li>
        </ul>
    </div>
</div>

    <div class="card" data-animate="fade-up">
      <div class="card__head">
        <h3>Bilan de la semaine</h3>
        <span class="badge">7 jours</span>
      </div>

      <div class="card__body bilan-week">
        <ul class="bilan-list">
          <li><span class="bilan-emoji">🔥</span><div><div class="bilan-title">Calories ingérées</div><div class="bilan-num"><?= number_format($totalIn, 0, ',', ' ') ?> kcal</div></div></li>
          <li><span class="bilan-emoji">🏋️‍♂️</span><div><div class="bilan-title">Calories dépensées</div><div class="bilan-num"><?= number_format($totalOut, 0, ',', ' ') ?> kcal</div></div></li>
          <li><span class="bilan-emoji">🧮</span><div><div class="bilan-title">Solde total</div><div class="bilan-num <?= $soldeSem >= 0 ? 'green' : 'red' ?>"><?= $soldeSem >= 0 ? '+' : '' ?><?= number_format($soldeSem, 0, ',', ' ') ?> kcal</div></div></li>
          <li><span class="bilan-emoji">⏱️</span><div><div class="bilan-title">Minutes de sport</div><div class="bilan-num"><?= $totalMinutes ?> min</div></div></li>
          <li><span class="bilan-emoji">📊</span><div><div class="bilan-title">Moyenne / jour</div><div class="bilan-num"><?= number_format($moyenneJour, 0, ',', ' ') ?> kcal</div></div></li>
          <li><span class="bilan-emoji">💪</span><div><div class="bilan-title">Séances</div><div class="bilan-num"><?= $nbSeances ?></div></div></li>
        </ul>
      </div>
    </div>

    <div class="card" data-animate="fade-up">
      <div class="card__head">
        <h3>Dernières activités</h3>
        <span class="badge">Auto</span>
      </div>
      <ul class="list">
        <?php if (empty($lastActs)): ?>
        <li>
          <div class="list__left">
            <span class="list__icon">ℹ️</span>
            <div>
              <div class="list__title">Aucune activité enregistrée</div>
              <div class="list__sub">Utilise le calculateur pour ajouter un sport ✨</div>
            </div>
          </div>
        </li>
        <?php else: ?>
          <?php foreach ($lastActs as $act): ?>
          <li>
            <div class="list__left">
              <span class="list__icon">🏃‍♂️</span>
              <div>
                <div class="list__title"><?= htmlspecialchars($act['nom_sport'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="list__sub"><?= (int)$act['duree_min'] ?> min • <?= (int)round($act['kcal']) ?> kcal</div>
              </div>
            </div>
            <?php
              $dt = strtotime($act['date_sport']);
              $heure = date('H:i', $dt);
              $affiche = ($heure === '00:00') ? date('d/m', $dt) : date('d/m H:i', $dt);
            ?>
            <time><?= $affiche ?></time>
          </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </div>

  </div>
</section>

  <section class="dash-cta" data-animate="fade-up">
    <div class="container cta__inner">
      <div>
        <h3>Garde le flow 🔥</h3>
        <p>Utilise le calculateur, fixe tes objectifs et suis tes progrès chaque semaine.</p>
      </div>
      <a class="btn big" href="consommation.php">Ajouter une conso</a>
    </div>
  </section>

</main>

<?php include 'includes/footer.php'; ?>


<script>
  window.NAHA_DASH = {
    weekLabels: <?= json_encode($weekLabels ?? []) ?>,
    weekIn:     <?= json_encode($weekIn ?? []) ?>,
    weekOut:    <?= json_encode($weekOut ?? []) ?>,
    weekCardio: <?= json_encode($weekCardio ?? []) ?>,
    macros: {
      prot: <?= json_encode($macros['prot'] ?? 0) ?>,
      glu:  <?= json_encode($macros['glu']  ?? 0) ?>,
      lip:  <?= json_encode($macros['lip']  ?? 0) ?>
    }
  };
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script defer src="assets/js/tableau-script.js"></script>

</body>
</html>
