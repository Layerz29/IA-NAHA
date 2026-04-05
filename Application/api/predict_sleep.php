<?php
/**
 * predict_sleep.php
 * Relaie la requête au serveur Flask ml_server.py (localhost:5050/predict).
 * POST JSON → { sleep_hours: float, success: true }
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

$body = file_get_contents('php://input');
if (!$body) {
    echo json_encode(['error' => 'Aucune donnée reçue', 'success' => false]); exit;
}

$_env    = @parse_ini_file(__DIR__ . '/../.env') ?: [];
$mlUrl   = trim($_env['ML_SERVER_URL'] ?? 'http://127.0.0.1:10000') . '/predict';
$ch = curl_init($mlUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 35, 
]);

$response = curl_exec($ch);
$err      = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    echo json_encode([
        'error'   => 'Serveur ML inaccessible. Lancez ml_server.py.',
        'detail'  => $err,
        'success' => false,
    ]);
    exit;
}

// Retransmet la réponse Flask telle quelle
http_response_code($httpCode);
echo $response;
