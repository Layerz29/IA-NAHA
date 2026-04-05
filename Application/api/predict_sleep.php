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
$mlUrl   = trim($_env['ML_SERVER_URL'] ?? 'http://127.0.0.1:5050') . '/predict';
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

if ($err || $httpCode === 0) {
    // Fallback : estimation simple si le serveur ML est inaccessible
    $data     = json_decode($body, true) ?: [];
    $stress   = (float)($data['stress_level'] ?? 5);
    $steps    = (float)($data['daily_steps']  ?? 8000);
    $duration = (float)($data['duration_minutes'] ?? 30);

    $estimate = 7.5
        - ($stress - 5) * 0.2
        + ($steps  - 8000) / 10000 * 0.3
        + ($duration - 30) / 60 * 0.2;
    $estimate = round(max(4.0, min(12.0, $estimate)), 1);

    echo json_encode(['sleep_hours' => $estimate, 'success' => true, 'fallback' => true]);
    exit;
}

// Retransmet la réponse Flask telle quelle
http_response_code($httpCode);
echo $response;
