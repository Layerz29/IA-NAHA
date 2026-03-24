
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

// Recherche utilisateur
$stmt = $bdd->prepare("SELECT * FROM utilisateurs WHERE mail = ?");
$stmt->execute([$mail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "msg" => "Compte introuvable."]);
    exit;
}

// Vérifier mot de passe (colonne = pswrd)
if (!password_verify($pass, $user['pswrd'])) {
    echo json_encode(["success" => false, "msg" => "Mot de passe incorrect."]);
    exit;
}

// On charge la session
$_SESSION['utilisateur'] = [
    "id_utilisateur" => $user['id_utilisateur'],
    "nom" => $user['nom'],
    "prenom" => $user['prenom'],
    "mail" => $user['mail']
];



// Réponse JSON → redirection accueil
echo json_encode([
    "success" => true,
    "msg" => "Connexion réussie !",
    "redirect" => "accueil.php"
]);
exit;
