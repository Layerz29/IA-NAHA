
<?php
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');
require_once '../config/bdd.php';

$bdd = getBD();

$mail = trim($_POST['mail'] ?? '');
$pass = $_POST['pswrd'] ?? '';

// Champs vides ?
if ($mail === '' || $pass === '') {
    echo json_encode(["success" => false, "msg" => "Email ou mot de passe manquant."]);
    exit;
}

// Recherche utilisateur (table BD = `users`)
$stmt = $bdd->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$mail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "msg" => "Compte introuvable."]);
    exit;
}

// Vérifier mot de passe (colonne = password)
if (!password_verify($pass, $user['password'])) {
    echo json_encode(["success" => false, "msg" => "Mot de passe incorrect."]);
    exit;
}

// On charge la session
$_SESSION['utilisateur'] = [
    // Convention interne du code : on expose les deux clés
    "id_utilisateur" => (int)$user['id'],
    "id" => (int)$user['id'],
    "nom" => $user['nom'],
    "prenom" => $user['prenom'],
    "mail" => $user['email']
];



// Réponse JSON → redirection accueil
echo json_encode([
    "success" => true,
    "msg" => "Connexion réussie !",
    "redirect" => "accueil.php"
]);
exit;
