<?php
/* -----------------------------------------------------------
   Vérification session + connexion à la base
------------------------------------------------------------ */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/bdd.php';
$bdd = getBD();

/* -----------------------------------------------------------
   Vérification : utilisateur connecté ?
------------------------------------------------------------ */
if (!isset($_SESSION['utilisateur'])) {
    header("Location: seconnecter.php");
    exit;
}

$id = $_SESSION['utilisateur']['id_utilisateur'];

/* -----------------------------------------------------------
   Récupérer les informations de l’utilisateur
------------------------------------------------------------ */
$req = $bdd->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur=?");
$req->execute([$id]);
$user = $req->fetch(PDO::FETCH_ASSOC);

/* -----------------------------------------------------------
   Récupérer le dernier objectif de l'utilisateur
------------------------------------------------------------ */
$req2 = $bdd->prepare("
    SELECT * FROM objectif_utilisateur 
    WHERE id_utilisateur=? 
    ORDER BY date_maj DESC LIMIT 1
");
$req2->execute([$id]);
$goal = $req2->fetch(PDO::FETCH_ASSOC);

/* -----------------------------------------------------------
   Si aucun objectif trouvé → valeurs par défaut
------------------------------------------------------------ */
$age    = $goal['age']    ?? "Non défini";
$poids  = $goal['poids']  ?? 0;
$taille = $goal['taille'] ?? 0;

/* -----------------------------------------------------------
   Calcul de l’IMC si données disponibles
------------------------------------------------------------ */
$imc = 0;
if ($poids > 0 && $taille > 0) {
    $imc = round($poids / (($taille/100)**2), 1);
}

/* -----------------------------------------------------------
   Historique du poids pour le graphique
------------------------------------------------------------ */
$req3 = $bdd->prepare("
    SELECT poids, date_maj 
    FROM objectif_utilisateur 
    WHERE id_utilisateur=?
    ORDER BY date_maj ASC
");
$req3->execute([$id]);
$poidsData = $req3->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mon Profil — NAHA</title>

    <!-- Feuille de style principale du site -->
    <link rel="stylesheet" href="assets/css/accueil-style.css">

    <!-- Police utilisée par tout le site -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700;800&display=swap" rel="stylesheet">
</head>

<body>

<!-- Inclusion du header -->
<?php include 'includes/header.php'; ?>

<main class="container" style="padding:40px 0;">

    <!-- Titre principal -->
    <h1 style="text-align:center; font-weight:800; margin-bottom:30px;">Mon Profil</h1>

    <!-- Deux cartes principales : infos + IMC -->
    <div style="display:flex; gap:2rem; flex-wrap:wrap; justify-content:center;">

    <!-- ❖ Carte Avatar -->
<div style="
    background:white;
    padding:2rem;
    border-radius:20px;
    width:350px;
    text-align:center;
    box-shadow:0 8px 18px rgba(0,0,0,0.1);
">

    <!-- Image avatar -->
    <img src="<?= $user['avatar'] ?? 'assets/img/default-avatar.jpg' ?>" 
         style="
            width:160px;
            height:160px;
            object-fit:cover;
            border-radius:15px;
            margin-bottom:15px;
            box-shadow:0 6px 15px rgba(0,0,0,0.15);
         ">

    <!-- Nom + prénom -->
    <h3 style="margin:10px 0 0; font-weight:700;">
        <?= htmlspecialchars($user['prenom']." ".$user['nom']) ?>
    </h3>

    <!-- Statut connecté -->
    <p style="margin-top:6px; color:#16a34a; font-weight:600;">
        ● Connecté
    </p>

    <!-- Bouton changer photo -->
    <button class="btn-purple"
        onclick="document.getElementById('modal').style.display='flex'">
    Modifier la photo
    </button>

</div>


        <!-- ❖ Carte : Informations personnelles -->
        <div style="
            background:white;
            padding:2rem;
            border-radius:20px;
            width:350px;
            box-shadow:0 8px 18px rgba(0,0,0,0.1);
        ">
            <h3>Informations personnelles</h3>
            <br>
            <p><strong>Nom :</strong> <?= htmlspecialchars($user['nom']) ?></p>
            <p><strong>Prénom :</strong> <?= htmlspecialchars($user['prenom']) ?></p>
            <p><strong>Âge :</strong> <?= $age ?> ans</p>
            <p><strong>Poids :</strong> <?= $poids ?> kg</p>
            <p><strong>Taille :</strong> <?= $taille ?> cm</p>
            <br>
            <!-- Bouton pour ouvrir le modal -->
            <button class="btn-purple"
             onclick="document.getElementById('modal').style.display='flex'">
             Modifier
            </button>

        </div>

        <!-- ❖ Carte : IMC -->
        <div style="
            background:white;
            padding:2rem;
            border-radius:20px;
            width:350px;
            box-shadow:0 8px 18px rgba(0,0,0,0.1);
            text-align:center;
        ">
            <h2>Indice de Masse Corporelle</h2>

            <p style="font-size:42px; margin:10px 0; font-weight:800;">
                <?= $imc ?>
            </p>

            <p>
                <?php
                if ($imc == 0) echo "Insuffisance pondérale";
                elseif ($imc < 18.5) echo "Insuffisance pondérale";
                elseif ($imc < 25) echo "Poids normal";
                elseif ($imc < 30) echo "Surpoids";
                else echo "Obésité";
                ?>
            </p>
        </div>

    </div>

    <!-- ❖ Carte : Graphique du poids -->
    <div style="
        margin-top:40px;
        background:white;
        padding:2rem;
        border-radius:20px;
        box-shadow:0 8px 18px rgba(0,0,0,0.1);
    ">
        <h2 style="margin-bottom:20px;">Évolution du poids</h2>
        <canvas id="poidsChart" height="110"></canvas>
    </div>

</main>

<!-- -----------------------------------------------------------
     MODAL DE MODIFICATION DU PROFIL
------------------------------------------------------------ -->
<div id="modal" style="
    position:fixed; top:0; left:0; width:100%; height:100%;
    background:rgba(0,0,0,0.55); display:none;
    justify-content:center; align-items:center;
">
    <div style="
        background:white; width:380px; padding:2rem;
        border-radius:20px; box-shadow:0 12px 30px rgba(0,0,0,0.25);
    ">
        <h2>Modifier mes informations</h2>

        <!-- Formulaire pour mettre à jour les données -->
        <form action="actions/update_profil.php" method="POST" enctype="multipart/form-data"
      style="
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem 1.5rem;
        margin-top: 1rem;
      ">

    <div>
        <label>Nom</label>
        <input type="text" name="nom" class="field" 
               value="<?= $user['nom'] ?>" style="width:100%;">
    </div>

    <div>
        <label>Prénom</label>
        <input type="text" name="prenom" class="field" 
               value="<?= $user['prenom'] ?>" style="width:100%;">
    </div>

    <div>
        <label>Âge</label>
        <input type="number" name="age" class="field" 
               value="<?= ($age === 'Non défini' ? '' : $age) ?>" style="width:100%;">
    </div>

    <div>
        <label>Poids (kg)</label>
        <input type="number" name="poids" class="field" 
               value="<?= $poids ?>" style="width:100%;">
    </div>

    <div>
        <label>Taille (cm)</label>
        <input type="number" name="taille" class="field" 
               value="<?= $taille ?>" style="width:100%;">
    </div>

    <div>
        <label>Photo </label>
        <input type="file" name="avatar" accept="image/*" style="width:100%;">
    </div>

    <div style="grid-column: span 2; text-align:center; margin-top:0.5rem;">
        <button type="submit" class="btn btn-primary" 
                style="padding:10px 20px; font-size:1rem;">
            Sauvegarder
        </button>
    </div>

</form>


        <!-- Bouton de fermeture -->
        <button onclick="document.getElementById('modal').style.display='none'"
                style="margin-top:10px; background:#aaa; color:white;" 
                class="btn">
            Fermer
        </button>
    </div>
</div>

<!-- -----------------------------------------------------------
     SCRIPT : CHART.JS pour le graphique du poids
------------------------------------------------------------ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const poidsData = <?= json_encode(array_column($poidsData,'poids')) ?>;
const dates = <?= json_encode(array_column($poidsData,'date_maj')) ?>;

new Chart(document.getElementById('poidsChart'), {
    type: 'line',
    data: {
        labels: dates,
        datasets: [{
            label: 'Poids (kg)',
            data: poidsData,
            tension: 0.3,
            borderWidth: 3,
            borderColor: '#4f46e5',
            backgroundColor: "rgba(99,102,241,0.2)"
        }]
    }
});
</script>

</body>
<?php include 'includes/footer.php'; ?>

</html>
