<?php
session_start();
require_once '../config/bdd.php';
$bdd = getBD();

$id = $_SESSION['utilisateur']['id_utilisateur'];

// Avatar
$avatarPath = null;
if (!empty($_FILES['avatar']['name'])) {
    $fileName = time() . "_" . basename($_FILES["avatar"]["name"]);
    $target = "uploads/" . $fileName;
    move_uploaded_file($_FILES["avatar"]["tmp_name"], $target);
    $avatarPath = $target;
}

// MAJ table utilisateurs
$req = $bdd->prepare("UPDATE utilisateurs SET nom=?, prenom=?, avatar=IFNULL(?, avatar) WHERE id_utilisateur=?");
$req->execute([$_POST['nom'], $_POST['prenom'], $avatarPath, $id]);

// MAJ table objectif_utilisateur
$req2 = $bdd->prepare("
    INSERT INTO objectif_utilisateur 
    (id_utilisateur, age, poids, taille, activite, sexe, objectif_nom, objectif_kcal, maintenance, date_maj)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
$req2->execute([
    $id,
    $_POST['age'],
    $_POST['poids'],
    $_POST['taille'],
    1,      // placeholder
    'H',    // placeholder
    'Mise à jour',
    0,
    0
]);

header("Location: profil.php");
exit;
