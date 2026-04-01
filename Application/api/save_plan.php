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
function encodeGender(string $v): int {
    return in_array(strtolower($v), ['homme', 'm', 'male']) ? 1 : 0;
}
function encodeIntensity(string $v): int {
    $map = ['high'=>0,'actif'=>0,'tres_actif'=>0,
            'low'=>1,'sedentaire'=>1,'leger'=>1,
            'medium'=>2,'modere'=>2];
    return $map[strtolower($v)] ?? 2;
}
function encodeActivity(string $v): int {
    $map = ['cycling'=>0,'velo'=>0,'dancing'=>1,'danse'=>1,'hiit'=>2,
            'running'=>3,'course'=>3,'strength'=>4,'musculation'=>4,
            'swimming'=>5,'natation'=>5,'walking'=>6,'marche'=>6,
            'weight_training'=>7,'yoga'=>8];
    return $map[strtolower($v)] ?? 6;
}
function encodeSmoking(string $v): int {
    $map = ['current'=>0,'fumeur'=>0,'former'=>1,'ancien'=>1,'ancien_fumeur'=>1,
            'never'=>2,'jamais'=>2,'non_fumeur'=>2];
    return $map[strtolower($v)] ?? 2;
}

$poids  = (float)($profil['poids']  ?? 0);
$taille = (float)($profil['taille'] ?? 0);
$bmi    = ($poids > 0 && $taille > 0) ? round($poids / (($taille / 100) ** 2), 2) : null;

try {
    $pdo->prepare('
        UPDATE users SET
            prenom           = ?,
            age              = ?,
            gender           = ?,
            poids            = ?,
            taille           = ?,
            bmi              = ?,
            intensity        = ?,
            activity_type    = ?,
            stress_level     = ?,
            duration_minutes = ?,
            daily_steps      = ?,
            hydration_level  = ?,
            smoking_status   = ?,
            objectif         = ?,
            restrictions     = ?,
            allergies        = ?
        WHERE id = ?
    ')->execute([
        $profil['prenom']             ?? null,
        (int)($profil['age']          ?? 0) ?: null,
        encodeGender  ($profil['sexe']           ?? ''),
        $poids                        ?: null,
        $taille                       ?: null,
        $bmi,
        encodeIntensity($profil['activite']      ?? ''),
        encodeActivity ($profil['activity_type'] ?? ''),
        (int)($profil['stress_level']      ?? 0) ?: null,
        (float)($profil['duration_minutes'] ?? 0) ?: null,
        (int)($profil['daily_steps']       ?? 0) ?: null,
        (float)($profil['hydration_level']  ?? 0) ?: null,
        encodeSmoking  ($profil['smoking_status'] ?? ''),
        $profil['objectif']           ?? null,
        $profil['restrictions']       ?? null,
        $profil['allergies']          ?? null,
        $userId,
    ]);
} catch (\Throwable $e) { /* non bloquant */ }

// ── Insertion nutrition_plans ──
$tempsSommeil = isset($body['temps_sommeil']) && $body['temps_sommeil'] !== ''
    ? (float)$body['temps_sommeil']
    : null;

$stmt = $pdo->prepare('
    INSERT INTO nutrition_plans
        (user_id, duree_jours, repas_par_jour, calories_cibles,
         proteines_g, glucides_g, lipides_g, bmr, plan_texte, temps_sommeil, date_creation)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
    $tempsSommeil,
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