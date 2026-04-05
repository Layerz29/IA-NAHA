<?php
// ─────────────────────────────────────────────
// CONFIG — IA-NAHA
// ─────────────────────────────────────────────

// Buffer output pour éviter que les warnings PHP cassent le JSON
ob_start();

// Empêche PHP d'afficher les erreurs dans la réponse
error_reporting(0);
ini_set('display_errors', '0');

// Lecture du .env pour surcharger les valeurs par défaut
$_cfg = @parse_ini_file(__DIR__ . '/../.env') ?: [];

define('DB_HOST', trim($_cfg['DB_HOST'] ?? 'localhost'));
define('DB_PORT', trim($_cfg['DB_PORT'] ?? '8889'));  // MAMP=8889, XAMPP/WAMP=3306
define('DB_NAME', trim($_cfg['DB_NAME'] ?? 'ia-naha'));
define('DB_USER', trim($_cfg['DB_USER'] ?? 'root'));
define('DB_PASS', trim($_cfg['DB_PASS'] ?? 'root'));

// Headers CORS + JSON — wildcard pour compatibilité locale et prod
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

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

// Helper body JSON — fallback sur $_POST si php://input est restreint
function getBody(): array {
    $raw = file_get_contents('php://input');
    if ($raw) return json_decode($raw, true) ?? [];
    // fallback : certains hébergeurs (InfinityFree) peuvent restreindre php://input
    if (!empty($_POST)) return $_POST;
    return [];
}

// Auth — valide le token Bearer et retourne le user_id
function requireAuth(): int {
    // Plusieurs fallbacks pour MAMP/Apache qui bloque parfois Authorization
    $auth = $_SERVER['HTTP_AUTHORIZATION']
         ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
         ?? '';
    if (!$auth && function_exists('getallheaders')) {
        $h    = array_change_key_case(getallheaders(), CASE_LOWER);
        $auth = $h['authorization'] ?? '';
    }
    $token = trim(str_replace('Bearer', '', $auth));

    // Fallback : header custom X-Auth-Token (jamais bloqué par Apache)
    if (!$token) {
        $token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
        if (!$token && function_exists('getallheaders')) {
            $h     = array_change_key_case(getallheaders(), CASE_LOWER);
            $token = $h['x-auth-token'] ?? '';
        }
        $token = trim($token);
    }

    if (!$token) {
        ob_clean(); http_response_code(401);
        echo json_encode(['error' => 'Non authentifié'], JSON_UNESCAPED_UNICODE); exit;
    }

    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT user_id FROM user_sessions WHERE id = ? LIMIT 1');
    $stmt->execute([$token]);
    $row  = $stmt->fetch();

    if (!$row) {
        ob_clean(); http_response_code(401);
        echo json_encode(['error' => 'Session invalide ou expirée'], JSON_UNESCAPED_UNICODE); exit;
    }

    return (int)$row['user_id'];
}