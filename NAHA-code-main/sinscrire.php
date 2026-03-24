
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>



<?php
// sinscrire.php
$nom    = $_GET['nom']   ?? '';
$prenom = $_GET['prenom']   ?? '';
$adr    = $_GET['adresse'] ?? '';
$num    = $_GET['numero'] ?? '';
$mail   = $_GET['mail']?? '';
$err    = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>NAHA — Inscription</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800&display=swap" rel="stylesheet">

  <!-- Style global NAHA (le même que sur accueil.php) -->
  <link rel="stylesheet" href="assets/css/accueil-style.css?v=<?php echo time(); ?>">

  <!-- Style en plus pour le formulaire (si tu veux) -->
  <link rel="stylesheet" href="assets/css/auth-style.css?v=<?php echo time(); ?>">
</head>

<body>

<header class="topbar">
  <div class="container topbar__inner">
    <a class="brand" href="#top">
      <span class="brand__logo">🍃</span>
      <span class="brand__text">NAHA</span>
    </a>

    <nav class="menu">
      <a class="pill" href="accueil.php">Accueil</a>
      <a class="pill" href="tableau.php">Tableau de bord</a>
      <a class="pill" href="calculateur.php">Calculateur</a>
      <a class="pill" href="projet.php">Le Projet</a>
      <a class="pill" href="consommation.php">Consommation</a>
      <a class="pill" href="contact.php">Contact</a>
    </nav>

    <div class="auth">
      <a class="link" href="seconnecter.php">Se connecter</a>
      <a class="btn is-active" href="sinscrire.php">S'inscrire</a>
    </div>

</header>

  <!-- CONTENU PRINCIPAL -->
  <main class="page">
    <section class="hero">
      <div class="hero-text">
        <h1>Rejoins la team NAHA</h1>
        <p>Inscris-toi pour suivre ta progression et atteindre tes objectifs.</p>
      </div>

      <div class="auth-card">
        <h2>Créer un compte</h2>

        <?php if (!empty($err)): ?>
          <p class="error-msg"><?= htmlspecialchars($err) ?></p>
        <?php endif; ?>

        <form action="actions/inscription.php" method="post" class="auth-form" id="inscription-form">

          <div class="field">
            <label for="nom">Nom</label>
            <input id="nom" type="text" name="nom" value="<?= htmlspecialchars($nom) ?>" required>
          </div>

          <div class="field">
            <label for="prenom">Prénom</label>
            <input id="prenom" type="text" name="prenom" value="<?= htmlspecialchars($prenom) ?>" required>
          </div>

          <div class="field">
            <label for="adr">Adresse</label>
            <input id="adr" type="text" name="adresse" value="<?= htmlspecialchars($adr) ?>">
          </div>

          <div class="field">
            <label for="num">Téléphone</label>
            <input id="num" type="text" name="numero" value="<?= htmlspecialchars($num) ?>">
          </div>

          <div class="field">
            <label for="mail">Email</label>
            <input id="mail" type="email" name="mail" value="<?= htmlspecialchars($mail) ?>" required>
          </div>

          <div class="field">
            <label for="mdp1">Mot de passe</label>
            <input id="mdp1" type="password" name="mdp1" required>
          </div>

          <div class="field">
            <label for="mdp1">Confirmer le mot de passe</label>
            <input id="mdp2" type="password" name="mdp2" required>
          </div>

          <button type="submit" class="btn btn-primary btn-full">S'inscrire</button>

          <p class="switch-auth">
            Déjà un compte ?
            <a href="seconnecter.php">Se connecter</a>
          </p>
        </form>
      </div>
    </section>
  </main>

  <!-- FOOTER SIMPLE -->
<footer class="footer">
  <div class="container footer__inner">
    <div class="footer__left">
      <p class="mini-quote">“Le futur c’est loin, j’attends pas assis”.</p>
    </div>
    <div class="footer__right">
      <div class="legal">© 2025 NAHA — Données : Open Food Facts & Compendium MET</div>
    </div>
  </div>
</footer>
<script>
document.addEventListener("DOMContentLoaded", () => {

    const form = document.querySelector("#inscription-form");


    const regexName = /^[A-Za-zÀ-ÖØ-öø-ÿ\s'-]+$/;
    const regexAdresse = /^[A-Za-z0-9À-ÖØ-öø-ÿ\s',.-]+$/;
    const regexTel = /^[0-9]{10}$/;

    // Inputs
    const nom = document.querySelector("#nom");
    const prenom = document.querySelector("#prenom");
    const adresse = document.querySelector("#adr");
    const numero = document.querySelector("#num");
    const mail = document.querySelector("#mail");
    const mdp1 = document.querySelector("#mdp1");
    const mdp2 = document.querySelector("#mdp2");

    // Affiche une erreur visuelle
    function setError(input, msg) {
        input.classList.add("input-error");

        if (!input.nextElementSibling || !input.nextElementSibling.classList.contains("input-msg")) {
            const p = document.createElement("p");
            p.className = "input-msg";
            p.style.color = "#e11d48";
            p.style.fontSize = "0.8rem";
            p.style.margin = "0";
            p.textContent = msg;
            input.insertAdjacentElement("afterend", p);
        } else {
            input.nextElementSibling.textContent = msg;
        }
    }

    // Efface l'erreur
    function clearError(input) {
        input.classList.remove("input-error");
        if (input.nextElementSibling && input.nextElementSibling.classList.contains("input-msg")) {
            input.nextElementSibling.remove();
        }
    }

    // Vérif LIVE
    nom.addEventListener("input", () => {
        if (!regexName.test(nom.value.trim())) setError(nom, "Le nom ne doit contenir que des lettres.");
        else clearError(nom);
    });

    prenom.addEventListener("input", () => {
        if (!regexName.test(prenom.value.trim())) setError(prenom, "Le prénom ne doit contenir que des lettres.");
        else clearError(prenom);
    });

    adresse.addEventListener("input", () => {
        if (!regexAdresse.test(adresse.value.trim())) setError(adresse, "Adresse invalide.");
        else clearError(adresse);
    });

    numero.addEventListener("input", () => {
        if (!regexTel.test(numero.value.trim())) setError(numero, "Le numéro doit faire 10 chiffres.");
        else clearError(numero);
    });

    mail.addEventListener("input", () => {
        if (!mail.value.includes("@") || !mail.value.includes(".")) {
            setError(mail, "Email invalide.");
        } else clearError(mail);
    });

    mdp1.addEventListener("input", () => {
        if (mdp1.value.length < 4) setError(mdp1, "Minimum 4 caractères.");
        else clearError(mdp1);
    });

    mdp2.addEventListener("input", () => {
        if (mdp2.value !== mdp1.value) setError(mdp2, "Les mots de passe ne correspondent pas.");
        else clearError(mdp2);
    });

    // Validation finale
    form.addEventListener("submit", (e) => {
        const invalid =
            nom.classList.contains("input-error") ||
            prenom.classList.contains("input-error") ||
            adresse.classList.contains("input-error") ||
            numero.classList.contains("input-error") ||
            mail.classList.contains("input-error") ||
            mdp1.classList.contains("input-error") ||
            mdp2.classList.contains("input-error");

        if (invalid) {
            e.preventDefault();
            alert("Merci de corriger les erreurs avant de continuer.");
        }
    });

});
</script>



</body>
</html>
