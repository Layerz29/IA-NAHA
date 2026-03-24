<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Connexion</title>
    <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700;800&display=swap" rel="stylesheet">
  
    <link rel="stylesheet" href="assets/css/accueil-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/auth-style.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- 👉 Inclusion du header -->
<?php include 'includes/header.php'; ?>

<main class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Connexion</h1>

        <!-- Zone erreur AJAX -->
        <div id="login-error" style="
            color:#e11d48;
            font-weight:600;
            margin-bottom:10px;
            display:none;
        "></div>

        <form class="auth-form" novalidate>

            <label for="mail">Adresse e-mail</label>
            <input type="email" name="mail" id="mail" required />

            <label for="mdp">Mot de passe</label>
            <input type="password" name="mdp" id="mdp" required />

            <button type="submit" class="btn btn-primary btn-full">Se connecter</button>
        </form>

        <p class="auth-alt">
            Pas encore de compte ? <a href="sinscrire.php">Inscrivez-vous</a>
        </p>
    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const form = document.querySelector(".auth-form");
    const mail = document.querySelector("#mail");
    const mdp  = document.querySelector("#mdp");
    const errorDiv = document.querySelector("#login-error");
    const submitBtn = form.querySelector("button");

    // Vérification email + mdp
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    // Animation shake
    function shakeForm() {
        form.classList.add("shake");
        setTimeout(() => form.classList.remove("shake"), 400);
    }

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        errorDiv.style.display = "none";
        errorDiv.textContent = "";

        const emailVal = mail.value.trim();
        const passVal  = mdp.value.trim();

        // Vérif email
        if (!isValidEmail(emailVal)) {
            errorDiv.textContent = "Veuillez entrer une adresse e-mail valide.";
            errorDiv.style.display = "block";
            shakeForm();
            return;
        }

        // Vérif mdp
        if (passVal.length < 1) {
            errorDiv.textContent = "Le mot de passe doit contenir au moins 1 caractère.";
            errorDiv.style.display = "block";
            shakeForm();
            return;
        }

        // Envoi AJAX
        const data = new FormData();
        data.append("mail", emailVal);
        data.append("pswrd", passVal);

        const res = await fetch("actions/login_api.php", {
            method: "POST",
            body: data
        });

        const json = await res.json();

        if (!json.success) {
            errorDiv.textContent = json.msg;
            errorDiv.style.display = "block";
            shakeForm();
            return;
        }

        // Succès → redirection
        window.location.href = json.redirect;
    });
});
</script>

<style>
/* Animation SHAKE */
.shake {
    animation: shake 0.35s;
}
@keyframes shake {
    0% { transform: translateX(0px); }
    20% { transform: translateX(-6px); }
    40% { transform: translateX(6px); }
    60% { transform: translateX(-4px); }
    80% { transform: translateX(4px); }
    100% { transform: translateX(0px); }
}
</style>


<?php include 'includes/footer.php'; ?>

</body>
</html>
