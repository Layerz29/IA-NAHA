<?php
session_start();
require_once '../config/bdd.php';
$bdd = getBD();

$id = $_SESSION['utilisateur']['id_utilisateur'];

// MAJ table BD = `users`
// La table fournie ne contient pas de champ `avatar` ni d'historique `objectif_utilisateur`,
// donc on se limite aux infos présentes : nom/prenom/age/poids/taille.
$ageVal = $_POST['age'] ?? null;
$poidsVal = $_POST['poids'] ?? null;
$tailleVal = $_POST['taille'] ?? null;

$ageVal = ($ageVal === '' || $ageVal === null) ? null : (int)$ageVal;
$poidsVal = ($poidsVal === '' || $poidsVal === null) ? null : (float)$poidsVal;
$tailleVal = ($tailleVal === '' || $tailleVal === null) ? null : (float)$tailleVal;

$req = $bdd->prepare("
    UPDATE users
    SET nom = ?, prenom = ?, age = ?, poids = ?, taille = ?
    WHERE id = ?
");
$req->execute([
    $_POST['nom'] ?? null,
    $_POST['prenom'] ?? null,
    $ageVal,
    $poidsVal,
    $tailleVal,
    $id
]);

header("Location: profil.php");
exit;
