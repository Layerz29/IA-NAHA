<?php
// ─────────────────────────────────────────────
// SAVE PROFILE — IA-NAHA
// Met à jour le profil utilisateur (onboarding)
// ─────────────────────────────────────────────
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['error' => 'Méthode non autorisée'], 405);
}

$body   = getBody();
$userId = (int)($body['user_id'] ?? 0);

if (!$userId) {
    jsonOut(['error' => 'user_id manquant'], 422);
}

// ── Encodages catégoriels (mêmes que le modèle ML) ──────────────────────────
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

// ── Calcul BMI ───────────────────────────────────────────────────────────────
$poids  = (float)($body['poids']  ?? 0);
$taille = (float)($body['taille'] ?? 0);
$bmi    = ($poids > 0 && $taille > 0) ? round($poids / (($taille / 100) ** 2), 2) : null;

// ── UPDATE ───────────────────────────────────────────────────────────────────
$pdo = getPDO();

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
        $body['prenom']           ?? null,
        (int)($body['age']        ?? 0)    ?: null,
        encodeGender  ($body['sexe']          ?? ''),
        $poids                    ?: null,
        $taille                   ?: null,
        $bmi,
        encodeIntensity($body['activite']     ?? ''),
        encodeActivity ($body['activity_type'] ?? ''),
        (int)($body['stress_level']   ?? 0) ?: null,
        (float)($body['duration_minutes'] ?? 0) ?: null,
        (int)($body['daily_steps']    ?? 0) ?: null,
        (float)($body['hydration_level'] ?? 0) ?: null,
        encodeSmoking  ($body['smoking_status'] ?? ''),
        $body['objectif']         ?? null,
        $body['restrictions']     ?? null,
        $body['allergies']        ?? null,
        $userId,
    ]);
} catch (\Throwable $e) {
    jsonOut(['error' => 'Erreur mise à jour profil : ' . $e->getMessage()], 500);
}

jsonOut(['success' => true, 'message' => 'Profil mis à jour.']);
