<?php
// ─────────────────────────────────────────────
// config.php — Connexion MySQL MAMP
// Placer dans : htdocs/ia-naha/api/config.php
// ─────────────────────────────────────────────

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');      // MAMP utilise parfois 8889, vérifie dans MAMP > Préférences > Ports
define('DB_NAME', 'ia-naha');
define('DB_USER', 'root');
define('DB_PASS', 'root');      // mot de passe par défaut MAMP

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Connexion DB impossible : ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Headers CORS pour React en dev
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Répondre aux preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
