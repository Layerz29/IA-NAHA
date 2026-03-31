<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_exception_handler(function($e) {
    ob_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $errstr, 'file' => $errfile, 'line' => $errline]);
    exit;
});
// ─────────────────────────────────────────────
// SAVE PLAN — IA-NAHA
// ─────────────────────────────────────────────
require_once __DIR__ . '/config.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['error' => 'Méthode non autorisée'], 405);
}

$body   = getBody();
$profil = $body['profil'] ?? [];
$plan   = $body['plan']   ?? [];
$userId = (int)($body['user_id'] ?? 0);

if (!$plan || !$userId) {
    jsonOut(['error' => 'Données manquantes (user_id ou plan)'], 422);
}

$pdo = getPDO();

// ── Mise à jour du profil user ──
try {
    $pdo->prepare('
        UPDATE users SET
            age = ?, sexe = ?, poids = ?, taille = ?,
            activite = ?, objectif = ?, restrictions = ?, allergies = ?
        WHERE id = ?
    ')->execute([
        $profil['age']          ?? null,
        $profil['sexe']         ?? null,
        $profil['poids']        ?? null,
        $profil['taille']       ?? null,
        $profil['activite']     ?? null,
        $profil['objectif']     ?? null,
        $profil['restrictions'] ?? null,
        $profil['allergies']    ?? null,
        $userId,
    ]);
} catch (\Throwable $e) { /* non bloquant */ }

// ── Insertion nutrition_plan ──
$stmt = $pdo->prepare('
    INSERT INTO nutrition_plans
        (user_id, duree_jours, repas_par_jour, calories_cibles,
         proteines_g, glucides_g, lipides_g, bmr, plan_texte, date_creation)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
');
$stmt->execute([
    $userId,
    (int)($profil['duree']  ?? count($plan['jours'] ?? [])),
    (int)($profil['repas']  ?? 3),
    (int)($plan['calories_cibles'] ?? 0),
    (float)($plan['proteines_g']   ?? 0),
    (float)($plan['glucides_g']    ?? 0),
    (float)($plan['lipides_g']     ?? 0),
    (int)($plan['bmr']             ?? 0),
    json_encode($plan, JSON_UNESCAPED_UNICODE),
]);
$planId = (int)$pdo->lastInsertId();

// ── Insertion meals ──
$stmtMeal = $pdo->prepare('
    INSERT INTO meals
        (plan_id, jour, type_repas, nom, calories,
         proteines, glucides, lipides, fibres, detail)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
');

foreach ($plan['jours'] ?? [] as $jour) {
    foreach ($jour['repas'] ?? [] as $repas) {
        $stmtMeal->execute([
            $planId,
            (int)$jour['jour'],
            $repas['type']      ?? '',
            $repas['nom']       ?? '',
            (float)($repas['calories']  ?? 0),
            (float)($repas['proteines'] ?? 0),
            (float)($repas['glucides']  ?? 0),
            (float)($repas['lipides']   ?? 0),
            (float)($repas['fibres']    ?? 0),
            json_encode($repas['aliments'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
    }
}

jsonOut([
    'success' => true,
    'plan_id' => $planId,
    'user_id' => $userId,
    'message' => 'Plan sauvegardé avec succès.',
]);