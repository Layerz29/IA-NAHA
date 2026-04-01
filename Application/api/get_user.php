<?php
require_once __DIR__ . '/config.php';

$userId = (int)($_GET['user_id'] ?? 0);
if (!$userId) jsonOut(['error' => 'user_id manquant'], 422);

$pdo  = getPDO();
$stmt = $pdo->prepare('SELECT id, prenom, nom, email, age, gender, poids, taille, intensity, activity_type, stress_level, duration_minutes, daily_steps, hydration_level, smoking_status, bmi, objectif, restrictions, allergies FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) jsonOut(['error' => 'Utilisateur introuvable'], 404);

jsonOut($user);