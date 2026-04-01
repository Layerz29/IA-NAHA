<?php
// ─────────────────────────────────────────────
// GET PLANS — IA-NAHA
// ─────────────────────────────────────────────
require_once __DIR__ . '/config.php';

$pdo    = getPDO();
$userId = (int)($_GET['user_id'] ?? 0);
$planId = (int)($_GET['plan_id'] ?? 0);

// ── Détail d'un plan ──
if ($planId) {
    $stmt = $pdo->prepare('
        SELECT np.*, np.date_creation AS created_at,
               u.age, u.sexe, u.poids, u.taille, u.activite, u.objectif, u.restrictions
        FROM nutrition_plans np
        JOIN users u ON u.id = np.user_id
        WHERE np.id = ?
        LIMIT 1
    ');
    $stmt->execute([$planId]);
    $plan = $stmt->fetch();

    if (!$plan) jsonOut(['error' => 'Plan introuvable'], 404);

    // Récupère les meals
    $stmtM = $pdo->prepare('SELECT * FROM meals WHERE plan_id = ? ORDER BY jour, id');
    $stmtM->execute([$planId]);
    $plan['meals'] = $stmtM->fetchAll();

    jsonOut($plan);
}

// ── Liste des plans d'un user ──
if ($userId) {
    $stmt = $pdo->prepare('
        SELECT id, duree_jours, repas_par_jour, calories_cibles,
               proteines_g, glucides_g, lipides_g, bmr, date_creation AS created_at,
               (SELECT objectif FROM users WHERE id = np.user_id) as objectif,
               (SELECT activite FROM users WHERE id = np.user_id) as activite,
               (SELECT restrictions FROM users WHERE id = np.user_id) as restrictions
        FROM nutrition_plans np
        WHERE user_id = ?
        ORDER BY date_creation DESC
    ');
    $stmt->execute([$userId]);
    $plans = $stmt->fetchAll();

    jsonOut($plans);
}

jsonOut(['error' => 'user_id ou plan_id requis'], 422);