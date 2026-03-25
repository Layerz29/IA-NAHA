<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/bdd.php';

$bdd = getBD();

// Empêcher l’accès en GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: seconnecter.php?err=" . urlencode("Accès non autorisé."));
    exit;
}

// Nettoyage
$mail = filter_input(INPUT_POST, 'mail', FILTER_SANITIZE_EMAIL);
$mdp  = $_POST['mdp'] ?? '';

if (empty($mail) || empty($mdp)) {
    header("Location: seconnecter.php?err=" . urlencode("Veuillez remplir tous les champs."));
    exit;
}

// Chercher l'utilisateur (table BD = `users`)
$sql = "SELECT id, nom, prenom, email, password 
        FROM users WHERE email = :mail LIMIT 1";
$stmt = $bdd->prepare($sql);
$stmt->execute(['mail' => $mail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: seconnecter.php?err=" . urlencode("Adresse e-mail inconnue."));
    exit;
}

// Vérifier le mot de passe
if (!password_verify($mdp, $user['password'])) {
    header("Location: seconnecter.php?err=" . urlencode("Mot de passe incorrect."));
    exit;
}

// Sécurisation de la session
session_regenerate_id(true);

// Stocker les infos utilisateur
$_SESSION['utilisateur'] = [
    'id'     => (int)$user['id'],
    'id_utilisateur' => (int)$user['id'],
    'nom'    => $user['nom'],
    'prenom' => $user['prenom'],
    'mail'   => $user['email']
];

header("Location: accueil.php");
exit;
?>
