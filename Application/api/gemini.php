<?php
// ─────────────────────────────────────────────
// GEMINI PROXY — IA-NAHA
// Garde la clé API côté serveur
// ─────────────────────────────────────────────
require_once __DIR__ . '/config.php';
set_time_limit(0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['error' => 'Méthode non autorisée'], 405);
}

$body   = getBody();
$prompt = $body['prompt'] ?? '';

if (!$prompt) {
    jsonOut(['error' => 'Prompt manquant'], 422);
}

// ── Clé API Gemini (côté serveur uniquement) ──
define('GEMINI_API_KEY', 'AIzaSyB-OKg2fe9aiQSYtwB2qMrORfWXr2fYh68');
define('GEMINI_MODEL',   'gemini-2.5-flash');

$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;

$payload = json_encode([
    'contents'       => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => ['maxOutputTokens' => 65536, 'temperature' => 0.3],
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 120,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    jsonOut(['error' => 'Erreur réseau : ' . $curlErr], 502);
}

$data = json_decode($response, true);

if ($httpCode !== 200) {
    $msg = $data['error']['message'] ?? ('Erreur Gemini HTTP ' . $httpCode);
    jsonOut(['error' => $msg], 502);
}

$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

ob_clean();
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['text' => $text], JSON_UNESCAPED_UNICODE);
exit;
