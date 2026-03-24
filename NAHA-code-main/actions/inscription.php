<?php
session_start();
require_once '../config/bdd.php';

$bdd = getBD();

if(
  empty($_POST['nom']) ||
  empty($_POST['prenom']) ||
  empty($_POST['mail']) ||
  empty($_POST['adresse']) ||
  empty($_POST['numero']) ||
  empty($_POST['mdp1']) ||
  empty($_POST['mdp2'])
) {
  header("Location: sinscrire.php?err=" . urlencode("Veuillez remplir tous les champs."));
  exit;
}

$nom = trim($_POST['nom']);
$prenom = trim($_POST['prenom']);
$mail = trim($_POST['mail']);
$adresse = trim($_POST['adresse']);
$numero = trim($_POST['numero']);
$mdp1 = $_POST['mdp1'];
$mdp2 = $_POST['mdp2'];

if ($mdp1 !== $mdp2) {
  header("Location: sinscrire.php?err=" . urlencode("Les mots de passe ne correspondent pas.")
    . "&nom=" .urlencode($nom)
    . "&prenom=" .urlencode($prenom)
    . "&mail=" .urlencode($mail)
    . "&adresse=" .urlencode($adresse)
    . "&numero=" .urlencode($numero)
  );
  exit;
}

$sql = "SELECT mail FROM utilisateurs WHERE mail = :mail LIMIT 1";
$stmt = $bdd->prepare($sql);
$stmt->execute(['mail' => $mail]);
if ($stmt->fetch(PDO::FETCH_ASSOC)) {
  header("Location: sinscrire.php?err=" . urlencode("Cette adresse e-mail est déjà utilisée.")
    . "&nom=" .urlencode($nom)
    . "&prenom=" .urlencode($prenom)
    . "&adresse=" .urlencode($adresse)
    . "&numero=" .urlencode($numero)
    . "&mail=" .urlencode($mail)
  );
  exit;
}

$hash = password_hash($_POST['mdp1'], PASSWORD_BCRYPT);

$sql = "INSERT INTO utilisateurs(nom, prenom, numero, adresse, mail, pswrd)
        VALUES(:nom, :prenom, :numero, :adresse, :mail, :pswrd)";

$stmt = $bdd->prepare($sql);
$stmt->execute([
  'nom' => $nom,
  'prenom' => $prenom,
  'numero' => $numero,
  'adresse' => $adresse,
  'mail' => $mail,
  'pswrd' => $hash
]);

header("Location: seconnecter.php");
exit;
