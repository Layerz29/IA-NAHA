<?php
// ─────────────────────────────────────────────
// GET STATS — IA-NAHA Dataviz
// ─────────────────────────────────────────────
require_once __DIR__ . '/config.php';

$pdo = getPDO();

// ── KPIs globaux ──
$kpis = $pdo->query('
    SELECT
        (SELECT COUNT(*) FROM users)            AS total_users,
        (SELECT COUNT(*) FROM nutrition_plans)  AS total_plans,
        ROUND(AVG(np.temps_sommeil), 2)         AS avg_sleep,
        ROUND(AVG(u.bmi), 1)                    AS avg_bmi,
        ROUND(AVG(np.calories_cibles))          AS avg_calories,
        ROUND(AVG(u.stress_level), 1)           AS avg_stress
    FROM nutrition_plans np
    LEFT JOIN users u ON u.id = np.user_id
')->fetch();

// ── Distribution types activité (encodé 0-8) ──
$activity = $pdo->query('
    SELECT activity_type, COUNT(*) AS cnt
    FROM users WHERE activity_type IS NOT NULL
    GROUP BY activity_type ORDER BY cnt DESC
')->fetchAll();

// ── Distribution stress (1-10) ──
$stress = $pdo->query('
    SELECT stress_level, COUNT(*) AS cnt
    FROM users WHERE stress_level IS NOT NULL
    GROUP BY stress_level ORDER BY stress_level
')->fetchAll();

// ── Macros moyens des plans ──
$macros = $pdo->query('
    SELECT
        ROUND(AVG(proteines_g)) AS avg_prot,
        ROUND(AVG(glucides_g))  AS avg_gluc,
        ROUND(AVG(lipides_g))   AS avg_lip,
        ROUND(AVG(calories_cibles)) AS avg_cal,
        ROUND(AVG(bmr))         AS avg_bmr
    FROM nutrition_plans
')->fetch();

// ── Sommeil prédit moyen par type d'activité ──
$sleepByActivity = $pdo->query('
    SELECT u.activity_type, ROUND(AVG(np.temps_sommeil), 2) AS avg_sleep, COUNT(*) AS n
    FROM users u
    JOIN nutrition_plans np ON np.user_id = u.id
    WHERE np.temps_sommeil IS NOT NULL AND u.activity_type IS NOT NULL
    GROUP BY u.activity_type
    ORDER BY avg_sleep DESC
')->fetchAll();

// ── Calories moyennes par objectif ──
$calByObjectif = $pdo->query('
    SELECT u.objectif, ROUND(AVG(np.calories_cibles)) AS avg_cal, COUNT(*) AS n
    FROM users u
    JOIN nutrition_plans np ON np.user_id = u.id
    WHERE u.objectif IS NOT NULL AND u.objectif != ""
    GROUP BY u.objectif
    HAVING n >= 1
    ORDER BY avg_cal DESC
')->fetchAll();

// ── Évolution plans (12 derniers mois) ──
$plansOverTime = $pdo->query('
    SELECT DATE_FORMAT(date_creation, "%Y-%m") AS month,
           COUNT(*) AS n,
           ROUND(AVG(calories_cibles)) AS avg_cal
    FROM nutrition_plans
    GROUP BY month ORDER BY month ASC LIMIT 12
')->fetchAll();

// ── BMI distribution ──
$bmiDist = $pdo->query('
    SELECT
        SUM(bmi < 18.5)                    AS maigre,
        SUM(bmi >= 18.5 AND bmi < 25)      AS normal,
        SUM(bmi >= 25 AND bmi < 30)        AS surpoids,
        SUM(bmi >= 30)                     AS obesite
    FROM users WHERE bmi IS NOT NULL
')->fetch();

jsonOut([
    'kpis'              => $kpis,
    'activity_dist'     => $activity,
    'stress_dist'       => $stress,
    'macros_avg'        => $macros,
    'sleep_by_activity' => $sleepByActivity,
    'cal_by_objectif'   => $calByObjectif,
    'plans_over_time'   => $plansOverTime,
    'bmi_dist'          => $bmiDist,
]);
