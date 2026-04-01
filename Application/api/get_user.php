<?php
require_once __DIR__ . '/config.php';

$userId = (int)($_GET['user_id'] ?? 0);
if (!$userId) jsonOut(['error' => 'user_id manquant'], 422);

$pdo  = getPDO();
$stmt = $pdo->prepare('SELECT id, prenom, nom, email, age, sexe, poids, taille, activite, objectif, restrictions, allergies FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) jsonOut(['error' => 'Utilisateur introuvable'], 404);

jsonOut($user);