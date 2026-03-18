<?php
// ─────────────────────────────────────────────
// save_plan.php — Sauvegarde le plan Gemini en MySQL
// POST /api/save_plan.php
// Body JSON : { profil: {...}, plan: {...} }
// ─────────────────────────────────────────────

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

// Récupère le body JSON
$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !isset($body['profil']) || !isset($body['plan'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Body invalide — profil et plan requis']);
    exit();
}

$profil = $body['profil'];
$plan   = $body['plan'];
$db     = getDB();

try {
    $db->beginTransaction();

    // ── 1. Créer ou retrouver l'utilisateur ──────────────
    // On utilise l'email si fourni, sinon on crée un user anonyme
    $email = $profil['email'] ?? null;
    $user_id = null;

    if ($email) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $user_id = $user['id'];
            // Met à jour le profil
            $stmt = $db->prepare("
                UPDATE users SET age=?, sexe=?, poids=?, taille=?, activite=?, objectif=?, restrictions=?, allergies=?
                WHERE id=?
            ");
            $stmt->execute([
                $profil['age']    ?? null,
                $profil['sexe']   ?? null,
                $profil['poids']  ?? null,
                $profil['taille'] ?? null,
                $profil['activite']    ?? null,
                $profil['objectifs']   ?? null,
                $profil['restrictions'] ?? null,
                $profil['allergies']   ?? null,
                $user_id
            ]);
        }
    }

    if (!$user_id) {
        // Crée un nouvel utilisateur
        $stmt = $db->prepare("
            INSERT INTO users (email, age, sexe, poids, taille, activite, objectif, restrictions, allergies)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $email,
            $profil['age']         ?? null,
            $profil['sexe']        ?? null,
            $profil['poids']       ?? null,
            $profil['taille']      ?? null,
            $profil['activite']    ?? null,
            $profil['objectifs']   ?? null,
            $profil['restrictions'] ?? null,
            $profil['allergies']   ?? null,
        ]);
        $user_id = $db->lastInsertId();
    }

    // ── 2. Créer le plan nutritionnel ────────────────────
    $stmt = $db->prepare("
        INSERT INTO nutrition_plans
            (user_id, duree_jours, repas_par_jour, calories_cibles, proteines_g, glucides_g, lipides_g, bmr, plan_texte)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $profil['duree']          ?? 7,
        $profil['repas']          ?? 3,
        $plan['calories_cibles']  ?? null,
        $plan['proteines_g']      ?? null,
        $plan['glucides_g']       ?? null,
        $plan['lipides_g']        ?? null,
        $plan['bmr']              ?? null,
        json_encode($plan, JSON_UNESCAPED_UNICODE), // stocke le JSON complet
    ]);
    $plan_id = $db->lastInsertId();

    // ── 3. Insérer les repas ────────────────────────────
    $stmt = $db->prepare("
        INSERT INTO meals (plan_id, jour, type_repas, nom, calories, proteines, glucides, lipides, fibres, detail)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($plan['jours'] ?? [] as $jour) {
        foreach ($jour['repas'] ?? [] as $repas) {
            $detail = json_encode($repas['aliments'] ?? [], JSON_UNESCAPED_UNICODE);
            $stmt->execute([
                $plan_id,
                $jour['jour']       ?? null,
                $repas['type']      ?? null,
                $repas['nom']       ?? null,
                $repas['calories']  ?? null,
                $repas['proteines'] ?? null,
                $repas['glucides']  ?? null,
                $repas['lipides']   ?? null,
                $repas['fibres']    ?? null,
                $detail,
            ]);
        }
    }

    $db->commit();

    echo json_encode([
        'success'  => true,
        'user_id'  => (int)$user_id,
        'plan_id'  => (int)$plan_id,
        'message'  => 'Plan sauvegardé avec succès',
    ]);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
