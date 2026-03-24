<?php
session_start();
require_once '../config/bdd.php';
$bdd = getBD();

if (!isset($_SESSION['utilisateur'])) {
    header("Location: seconnecter.php");
    exit;
}

$id = $_SESSION['utilisateur']['id_utilisateur'];

$old = $_POST['old_password'];
$new = $_POST['new_password'];
$confirm = $_POST['confirm_password'];

if ($new !== $confirm) {
    die("Les mots de passe ne correspondent pas.");
}

// récupérer le mot de passe actuel
$req = $bdd->prepare("SELECT pswrd FROM utilisateurs WHERE id_utilisateur=?");
$req->execute([$id]);
$user = $req->fetch();

if (!password_verify($old, $user['pswrd'])) {
    die("Mot de passe actuel incorrect.");
}

// hacher le nouveau mot de passe
$newHash = password_hash($new, PASSWORD_DEFAULT);

// mettre à jour
$update = $bdd->prepare("UPDATE utilisateurs SET pswrd=? WHERE id_utilisateur=?");
$update->execute([$newHash, $id]);

header("Location: parametres.php?success=password");
exit;
