<?php
session_start();
require_once '../config/bdd.php';
$bdd = getBD();

if (!isset($_SESSION['utilisateur'])) {
    header("Location: seconnecter.php");
    exit;
}

$id = $_SESSION['utilisateur']['id_utilisateur'];
$new_email = trim($_POST['new_email']);

// vérifier format email
if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
    die("Email invalide.");
}

// vérifier si existe déjà
$check = $bdd->prepare("SELECT * FROM utilisateurs WHERE mail=?");
$check->execute([$new_email]);

if ($check->rowCount() > 0) {
    die("Cet email est déjà utilisé.");
}

// update email
$update = $bdd->prepare("UPDATE utilisateurs SET mail=? WHERE id_utilisateur=?");
$update->execute([$new_email, $id]);

header("Location: parametres.php?success=email");
exit;
