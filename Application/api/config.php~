<?php
// ─────────────────────────────────────────────
// CONFIG — IA-NAHA (WAMP VERSION)
// ─────────────────────────────────────────────

// Buffer output pour éviter que les warnings PHP cassent le JSON
ob_start();

// Empêche PHP d'afficher les erreurs dans la réponse
error_reporting(0);
ini_set('display_errors', '0');

// Lecture du .env pour surcharger les valeurs par défaut
$_cfg = @parse_ini_file(__DIR__ . '/../.env') ?: [];

// 🔥 CONFIG WAMP (معدّل)
define('DB_HOST', trim($_cfg['DB_HOST'] ?? 'localhost'));
define('DB_PORT', trim($_cfg['DB_PORT'] ?? '3306')); // ✅ WAMP
define('DB_NAME', trim($_cfg['DB_NAME'] ?? 'ia-naha'));
define('DB_USER', trim($_cfg['DB_USER'] ?? 'root'));
define('DB_PASS', trim($_cfg['DB_PASS'] ?? ''));     // ✅ فارغة في WAMP

// Headers CORS + JSON
header('Content-Type: application/json; charset=utf-8');
$_allowed_origins = [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:8080',
    'http://127.0.0.1:8080',
];
$_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
header('Access-Control-Allow-Origin: ' . (in_array($_origin, $_allowed_origins, true) ? $_origin : 'http://localhost'));
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

// Helper réponse JSON
function jsonOut(array $data, int $code = 200): void {
    ob_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper body JSON
function getBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

// Auth — valide le token Bearer
function requireAuth(): int {

    $auth = $_SERVER['HTTP_AUTHORIZATION']
         ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
         ?? '';

    if (!$auth && function_exists('getallheaders')) {
        $h    = array_change_key_case(getallheaders(), CASE_LOWER);
        $auth = $h['authorization'] ?? '';
    }

    $token = trim(str_replace('Bearer', '', $auth));

    // Fallback
    if (!$token) {
        $token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
        if (!$token && function_exists('getallheaders')) {
            $h     = array_change_key_case(getallheaders(), CASE_LOWER);
            $token = $h['x-auth-token'] ?? '';
        }
        $token = trim($token);
    }

    if (!$token) {
        ob_clean();
        http_response_code(401);
        echo json_encode(['error' => 'Non authentifié'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT user_id FROM user_sessions WHERE id = ? LIMIT 1');
    $stmt->execute([$token]);
    $row  = $stmt->fetch();

    if (!$row) {
        ob_clean();
        http_response_code(401);
        echo json_encode(['error' => 'Session invalide ou expirée'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return (int)$row['user_id'];
}