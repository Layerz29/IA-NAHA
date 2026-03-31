<?php
// ─────────────────────────────────────────────
// CONFIG — IA-NAHA
// ─────────────────────────────────────────────

// Buffer output pour éviter que les warnings PHP cassent le JSON
ob_start();

// Empêche PHP d'afficher les erreurs dans la réponse
error_reporting(0);
ini_set('display_errors', '0');

define('DB_HOST', 'localhost');
define('DB_PORT', '8889');       // MAMP = 8889, XAMPP = 3306
define('DB_NAME', 'ia-naha');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Headers CORS + JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Connexion PDO
function getPDO(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Connexion BDD impossible : ' . $e->getMessage()]);
        exit;
    }
    return $pdo;
}

// Helper réponse JSON — vide le buffer avant d'envoyer
function jsonOut(array $data, int $code = 200): void {
    ob_clean(); // nettoie tout warning PHP qui aurait pu s'afficher avant
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper body JSON
function getBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}