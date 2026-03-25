<?php
session_start();
require_once '../config/bdd.php';
$bdd = getBD();

header("Content-Type: application/json");

function respond($success, $msg, $redirect = "") {
    echo json_encode([
        "success" => $success,
        "msg" => $msg,
        "redirect" => $redirect
    ]);
    exit;
}

$nom    = trim($_POST["nom"] ?? "");
$prenom = trim($_POST["prenom"] ?? "");
$adresse = trim($_POST["adresse"] ?? "");
$numero  = trim($_POST["numero"] ?? "");
$mail   = trim($_POST["mail"] ?? "");
$mdp1   = trim($_POST["mdp1"] ?? "");
$mdp2   = trim($_POST["mdp2"] ?? "");

// Nom / prénom → lettres only
if (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]{2,30}$/", $nom)) {
    respond(false, "Nom invalide.");
}

if (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]{2,30}$/", $prenom)) {
    respond(false, "Prénom invalide.");
}

// Téléphone FR
if (!empty($numero) && !preg_match("/^0[0-9]{9}$/", $numero)) {
    respond(false, "Numéro invalide.");
}

/* Vérifs basiques */
if (!$nom || !$prenom || !$mail || !$mdp1) {
    respond(false, "Merci de remplir les champs obligatoires.");
}

if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    respond(false, "Email invalide.");
}

if ($mdp1 !== $mdp2) {
    respond(false, "Les mots de passe ne correspondent pas.");
}

/* Vérifier si email existe */
$sql = "SELECT id FROM users WHERE email = ?";
$stmt = $bdd->prepare($sql);
$stmt->execute([$mail]);

if ($stmt->fetch()) {
    respond(false, "Un compte existe déjà avec cet e-mail.");
}

/* Inscription */
$hash = password_hash($mdp1, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (nom, prenom, email, password)
        VALUES (?, ?, ?, ?)";

$stmt = $bdd->prepare($sql);
$stmt->execute([$nom, $prenom, $mail, $hash]);

respond(true, "Inscription réussie !", "seconnecter.php");
