<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reportING(E_ALL);

//------------------------------------
//  Sécurité + connexion BDD
//------------------------------------
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/bdd.php';
$bdd = getBD();

if (!isset($_SESSION['utilisateur'])) {
    header('Location: seconnecter.php');
    exit;
}

// ID de l'utilisateur connecté c'est arthur qui a changé juste cette ligne
$idUser = (int)($_SESSION['utilisateur']['id'] ?? $_SESSION['utilisateur']['id_utilisateur']);

//------------------------------------
//  TRAITEMENT FORMULAIRE — CONSOMMATION
//------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_conso'])) {

    $id_produit = $_POST['id_produit'] ?? null;
    $quantite   = $_POST['quantite'] ?? null;

    if ($id_produit && $quantite > 0) {
        $sql = "INSERT INTO consommation (id_utilisateur, id_produit, quantite, date_conso)
                VALUES (:u, :p, :q, NOW())";

        $stmt = $bdd->prepare($sql);
        $stmt->execute([
            'u' => $idUser,
            'p' => $id_produit,
            'q' => $quantite
        ]);
    }
}


//------------------------------------
//  TRAITEMENT FORMULAIRE — ACTIVITÉ
//------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sport'])) {

    $id_sport = $_POST['id_sport'] ?? null;
    $duree    = $_POST['duree'] ?? null;

    if ($id_sport && $duree > 0) {
        $sql = "INSERT INTO activite (id_utilisateur, id_sport, duree_minutes, date_sport)
                VALUES (:u, :s, :d, NOW())";

        $stmt = $bdd->prepare($sql);
        $stmt->execute([
            'u' => $idUser,
            's' => $id_sport,
            'd' => $duree
        ]);
    }
}


//------------------------------------
//  RÉCUPÉRATION PRODUITS + SPORTS
//------------------------------------
$produits = $bdd->query("
    SELECT id_produit, nom_produit, energie_kcal 
    FROM produits 
    ORDER BY nom_produit ASC
")->fetchAll(PDO::FETCH_ASSOC);

$sports = $bdd->query("
    SELECT id_sport, nom_sport, MET, kcal_h_70kg 
    FROM sports 
    ORDER BY nom_sport ASC
")->fetchAll(PDO::FETCH_ASSOC);


//------------------------------------
//  DERNIÈRES CONSOMMATIONS + ACTIVITÉS
//------------------------------------
$stmt = $bdd->prepare("
    SELECT p.nom_produit, p.energie_kcal, c.quantite, c.date_conso 
    FROM consommation c
    JOIN produits p ON c.id_produit = p.id_produit
    WHERE c.id_utilisateur = :id
    ORDER BY c.date_conso DESC
    LIMIT 10
");
$stmt->execute(['id' => $idUser]);
$consos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $bdd->prepare("
    SELECT s.nom_sport, a.duree_minutes, a.date_sport
    FROM activite a
    JOIN sports s ON s.id_sport = a.id_sport
    WHERE a.id_utilisateur = :id
    ORDER BY a.date_sport DESC
    LIMIT 10
");
$stmt->execute(['id' => $idUser]);
$activites = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>NAHA — Consommation</title>
   <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/accueil-style.css">
  <link rel="stylesheet" href="assets/css/consommation-style.css?v=2">
</head>

<body>
<?php include 'includes/header.php'; ?>
<main class="page-cons">

  <section class="cons-hero">
    <div class="container">
      <h1 class="cons-title">Mon journal nutrition & sport</h1>
      <p class="cons-sub">Ajoute tes repas et séances. NAHA enregistre tout.</p>
    </div>
  </section>

  <section class="cons-main">
    <div class="container cons-container">
      <div class="cons-card">

        <h2>Journal rapide</h2>

        <!-- 🔎 BARRE DE RECHERCHE UNIQUE -->
        <div class="field">
          <label>Rechercher un aliment ou un sport :</label>
          <input type="text" id="search-global" placeholder="Tapez pour filtrer...">
        </div>

        <div class="cons-forms">

          <!-- FORMULAIRE PRODUITS -->
          <form method="post" class="subcard">
            <div class="subcard-header">
              <h3>Produit</h3>
              <span class="subtag">Calories ingérées</span>
            </div>

            <div class="field">
              <label>Produit :</label>
              <select name="id_produit" id="select-produit">
                <option value="">-- Choisir un aliment --</option>
                <?php foreach ($produits as $p): ?>
                  <option value="<?= $p['id_produit'] ?>">
                    <?= htmlspecialchars($p['nom_produit']) ?> (<?= $p['energie_kcal'] ?> kcal/100g)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <label>Quantité (g)</label>
              <input type="number" name="quantite" min="1" required>
            </div>

            <button type="submit" name="add_conso" class="btn big">Ajouter consommation</button>
          </form>


          <!-- FORMULAIRE SPORT -->
          <form method="post" class="subcard">
            <div class="subcard-header">
              <h3>Sport</h3>
              <span class="subtag subtag--blue">Calories dépensées</span>
            </div>

            <div class="field">
              <label>Sport :</label>
              <select name="id_sport" id="select-sport">
                <option value="">-- Choisir un sport --</option>
                <?php foreach ($sports as $s): ?>
                  <option value="<?= $s['id_sport'] ?>">
                    <?= htmlspecialchars($s['nom_sport']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <label>Durée (minutes)</label>
              <input type="number" name="duree" min="1" required>
            </div>

            <button type="submit" name="add_sport" class="btn big">Ajouter activité</button>
          </form>

        </div>

      </div>
    </div>
  </section>


  <!-- ----------------------------
        DERNIÈRES ENTRÉES
  ----------------------------- -->
  <section class="cons-steps">
    <div class="container">

      <h2 class="steps-title">Mes dernières entrées</h2>

      <div class="steps-grid">

        <!-- DERNIÈRES CONSOMMATIONS -->
        <div class="step-item">
          <h3>Consommations</h3>

          <?php if (empty($consos)): ?>
            <p>Aucune consommation enregistrée.</p>

          <?php else: ?>
            <ul>
              <?php foreach ($consos as $c): ?>
                <li>
                  <?= htmlspecialchars($c['nom_produit']) ?> — 
                  <?= $c['quantite'] ?> g — 
                  <?= round($c['energie_kcal'] * $c['quantite'] / 100) ?> kcal —
                  <?= $c['date_conso'] ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

        </div>

        <!-- DERNIÈRES ACTIVITÉS -->
        <div class="step-item">
          <h3>Activités</h3>

          <?php if (empty($activites)): ?>
            <p>Aucune activité enregistrée.</p>

          <?php else: ?>
            <ul>
              <?php foreach ($activites as $a): ?>
                <li>
                  <?= htmlspecialchars($a['nom_sport']) ?> — 
                  <?= $a['duree_minutes'] ?> min —
                  <?= $a['date_sport'] ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

        </div>

      </div>
    </div>
  </section>

</main>

<?php include 'includes/footer.php'; ?>

<!-- SCRIPT : BARRE DE RECHERCHE GLOBAL -->
<script>
document.getElementById("search-global").addEventListener("keyup", function() {

    let filter = this.value.toLowerCase();

    let selects = [
        document.getElementById("select-produit"),
        document.getElementById("select-sport")
    ];

    selects.forEach(select => {
        if (!select) return;
        let opts = select.options;

        for (let i = 0; i < opts.length; i++) {
            let txt = opts[i].text.toLowerCase();
            opts[i].style.display = txt.includes(filter) ? "" : "none";
        }
    });
});
</script>

</body>
</html>
