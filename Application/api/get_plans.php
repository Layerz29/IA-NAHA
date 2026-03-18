<?php
// ─────────────────────────────────────────────
// get_plans.php — Récupère les plans pour le dashboard
// GET /api/get_plans.php?user_id=1
// GET /api/get_plans.php?plan_id=5  (détail complet)
// ─────────────────────────────────────────────

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

$db = getDB();

// ── Détail d'un plan spécifique ──────────────
if (isset($_GET['plan_id'])) {
    $plan_id = (int)$_GET['plan_id'];

    // Plan + infos user
    $stmt = $db->prepare("
        SELECT np.*, u.age, u.sexe, u.poids, u.taille, u.activite, u.objectif, u.restrictions
        FROM nutrition_plans np
        JOIN users u ON np.user_id = u.id
        WHERE np.id = ?
    ");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch();

    if (!$plan) {
        http_response_code(404);
        echo json_encode(['error' => 'Plan introuvable']);
        exit();
    }

    // Repas du plan
    $stmt = $db->prepare("
        SELECT * FROM meals WHERE plan_id = ? ORDER BY jour, 
        FIELD(type_repas, 'petit_dejeuner', 'dejeuner', 'collation', 'diner')
    ");
    $stmt->execute([$plan_id]);
    $meals = $stmt->fetchAll();

    // Parse le detail JSON des aliments
    foreach ($meals as &$meal) {
        $meal['aliments'] = json_decode($meal['detail'], true) ?? [];
        unset($meal['detail']);
    }

    // Groupe les repas par jour
    $jours = [];
    foreach ($meals as $meal) {
        $jours[$meal['jour']][] = $meal;
    }

    // Stats par jour (calories totales)
    $stats_jours = [];
    foreach ($jours as $j => $repas) {
        $stats_jours[] = [
            'jour'     => $j,
            'calories' => round(array_sum(array_column($repas, 'calories'))),
            'proteines'=> round(array_sum(array_column($repas, 'proteines')), 1),
            'glucides' => round(array_sum(array_column($repas, 'glucides')), 1),
            'lipides'  => round(array_sum(array_column($repas, 'lipides')), 1),
        ];
    }

    echo json_encode([
        'plan'       => $plan,
        'jours'      => $jours,
        'stats_jours'=> $stats_jours,
    ]);
    exit();
}

// ── Liste des plans d'un utilisateur ────────
if (isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];

    $stmt = $db->prepare("
        SELECT np.id, np.date_creation, np.duree_jours, np.repas_par_jour,
               np.calories_cibles, np.proteines_g, np.glucides_g, np.lipides_g, np.bmr,
               u.age, u.sexe, u.poids, u.objectif,
               (SELECT COUNT(id) FROM meals WHERE plan_id = np.id) as nb_repas
        FROM nutrition_plans np
        JOIN users u ON np.user_id = u.id
        WHERE np.user_id = ?
        ORDER BY np.date_creation DESC
    ");
    $stmt->execute([$user_id]);
    $plans = $stmt->fetchAll();

    echo json_encode(['plans' => $plans, 'total' => count($plans)]);
    exit();
}

// ── Tous les plans (dashboard admin) ────────
$stmt = $db->prepare("
    SELECT np.id, np.date_creation, np.duree_jours, np.calories_cibles,
           np.proteines_g, np.glucides_g, np.lipides_g,
           u.age, u.sexe, u.poids, u.objectif,
           (SELECT COUNT(id) FROM meals WHERE plan_id = np.id) as nb_repas
    FROM nutrition_plans np
    JOIN users u ON np.user_id = u.id
    ORDER BY np.date_creation DESC
    LIMIT 50
");
$stmt->execute();
$plans = $stmt->fetchAll();

echo json_encode(['plans' => $plans, 'total' => count($plans)]);