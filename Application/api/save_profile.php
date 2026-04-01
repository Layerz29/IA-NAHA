<?php
// ─────────────────────────────────────────────
// SAVE PROFILE — IA-NAHA
// Met à jour le profil utilisateur sans plan
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

$pdo = getPDO();

try {
    $pdo->prepare('
        UPDATE users SET
            age = ?, sexe = ?, poids = ?, taille = ?,
            activite = ?, objectif = ?, restrictions = ?, allergies = ?
        WHERE id = ?
    ')->execute([
        $body['age']          ?? null,
        $body['sexe']         ?? null,
        $body['poids']        ?? null,
        $body['taille']       ?? null,
        $body['activite']     ?? null,
        $body['objectif']     ?? null,
        $body['restrictions'] ?? null,
        $body['allergies']    ?? null,
        $userId,
    ]);
} catch (\Throwable $e) {
    jsonOut(['error' => 'Erreur mise à jour profil : ' . $e->getMessage()], 500);
}

jsonOut(['success' => true, 'message' => 'Profil mis à jour.']);
