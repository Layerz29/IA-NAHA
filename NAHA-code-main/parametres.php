<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/bdd.php';
$bdd = getBD();

// vérifier si connecté
if (!isset($_SESSION['utilisateur'])) {
    header("Location: seconnecter.php");
    exit;
}

$id = $_SESSION['utilisateur']['id_utilisateur'];

// récupérer les infos utilisateur
$req = $bdd->prepare("SELECT * FROM users WHERE id=?");
$req->execute([$id]);
$user = $req->fetch(PDO::FETCH_ASSOC);

$email = $user['email'] ?? ""; // IMPORTANT : éviter les NULL
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramètres — NAHA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/accueil-style.css">
    <link rel="stylesheet" href="assets/css/parametres.css">
</head>

<body>

<?php include 'includes/header.php'; ?>

<main class="container" style="padding:40px 0;">
    <h1 style="font-weight:800; text-align:center; margin-bottom:20px;">
        Paramètres du compte
    </h1>

    <p style="text-align:center; color:#555; margin-bottom:40px;">
        Modifie votre informations personnelles, votre email ou votre mot de passe.
    </p>

    <div class="settings-grid">

        <!-- Informations personnelles -->
        <div class="card-settings">
            <h2>Informations personnelles</h2>

            <form action="actions/update_profil.php" method="POST" enctype="multipart/form-data">

                <label>Nom</label>
                <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>">

                <label>Prénom</label>
                <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>">

                <label>Photo de profil</label>
                <input type="file" name="avatar" accept="image/*">

                <button class="btn">Sauvegarder</button>
            </form>
        </div>

        <!-- Email -->
        <div class="card-settings">
            <h2>Adresse Email</h2>

            <form action="actions/update_email.php" method="POST">
                <label>Email actuel</label>
                <input type="email" value="<?= htmlspecialchars($email) ?>" disabled>

                <label>Nouvel email</label>
                <input type="email" name="new_email" required>

                <button class="btn">Modifier l’email</button>
            </form>
        </div>

        <!-- Mot de passe -->
        <div class="card-settings">
            <h2>Modifier le mot de passe</h2>

            <form action="actions/update_password.php" method="POST">
                <label>Mot de passe actuel</label>
                <input type="password" name="old_password" required>

                <label>Nouveau mot de passe</label>
                <input type="password" name="new_password" required>

                <label>Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" required>

                <button class="btn">Changer le mot de passe</button>
            </form>
        </div>

    </div>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
