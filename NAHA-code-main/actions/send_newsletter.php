<?php
require_once '../config/bdd.php';
$bdd = getBD();

/* ==== Récupérer le conseil du jour ==== */
$dayIndex = date('z');
$stmt = $bdd->prepare("SELECT texte FROM conseils LIMIT 1 OFFSET :o");
$stmt->bindValue(':o', $dayIndex, PDO::PARAM_INT);
$stmt->execute();
$conseil = $stmt->fetchColumn() ?: "Aucun conseil disponible aujourd’hui.";

/* ==== Récupérer tous les emails ==== */
$emails = $bdd->query("SELECT email FROM newsletter_users")->fetchAll(PDO::FETCH_COLUMN);

/* ==== Envoyer un mail à chaque utilisateur ==== */
foreach ($emails as $email) {
    mail(
        $email,
        "Conseil du jour 🍃",
        $conseil,
        "From: no-reply@ton-site.fr"
    );
}

echo "Newsletter envoyée à " . count($emails) . " utilisateurs.";
