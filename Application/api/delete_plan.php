<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(['error' => 'Méthode non autorisée'], 405);

$body   = getBody();
$planId = (int)($body['plan_id'] ?? 0);
$userId = (int)($body['user_id'] ?? 0);

if (!$planId || !$userId) jsonOut(['error' => 'Paramètres manquants'], 422);

$pdo = getPDO();

// Vérifie que le plan appartient bien à cet utilisateur
$stmt = $pdo->prepare('SELECT id FROM nutrition_plans WHERE id = ? AND user_id = ?');
$stmt->execute([$planId, $userId]);
if (!$stmt->fetch()) jsonOut(['error' => 'Plan introuvable ou accès refusé'], 404);

// Supprime (meals supprimés en CASCADE)
$pdo->prepare('DELETE FROM nutrition_plans WHERE id = ?')->execute([$planId]);

jsonOut(['success' => true, 'message' => 'Plan supprimé.']);