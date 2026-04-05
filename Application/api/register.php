<?php
// ─────────────────────────────────────────────
// REGISTER — IA-NAHA
// ─────────────────────────────────────────────
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['error' => 'Méthode non autorisée'], 405);
}

$body = getBody();

$prenom   = trim($body['prenom']   ?? '');
$nom      = trim($body['nom']      ?? '');
$email    = trim($body['email']    ?? '');
$password =       $body['password'] ?? '';

// ── Validation ──
if (!$prenom || !$nom || !$email || !$password) {
    jsonOut(['error' => 'Tous les champs sont obligatoires.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonOut(['error' => 'Adresse email invalide.'], 422);
}
if (mb_strlen($prenom) > 100 || mb_strlen($nom) > 100) {
    jsonOut(['error' => 'Prénom ou nom trop long (max 100 caractères).'], 422);
}
if (strlen($password) < 8) {
    jsonOut(['error' => 'Le mot de passe doit faire au moins 8 caractères.'], 422);
}
if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    jsonOut(['error' => 'Le mot de passe doit contenir au moins une majuscule et un chiffre.'], 422);
}

$pdo = getPDO();

// ── Email déjà utilisé ? ──
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonOut(['error' => 'Cette adresse email est déjà utilisée.'], 409);
}

// ── Hash du mot de passe ──
$hash = password_hash($password, PASSWORD_BCRYPT);

// ── Insertion ──
$stmt = $pdo->prepare('
    INSERT INTO users (prenom, nom, email, password, created_at)
    VALUES (?, ?, ?, ?, NOW())
');
$stmt->execute([$prenom, $nom, $email, $hash]);
$userId = (int) $pdo->lastInsertId();

// ── Session ──
$token = bin2hex(random_bytes(32));
try {
    $pdo->prepare('INSERT INTO user_sessions (id, user_id, payload, last_activity, ip_address) VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?)')
        ->execute([$token, $userId, '', $_SERVER['REMOTE_ADDR'] ?? '']);
} catch (\Throwable $e) { /* non bloquant si table absente */ }

// ── Log ──
try {
    $pdo->prepare('INSERT INTO login_logs (user_id, email, ip_address, success, created_at) VALUES (?, ?, ?, 1, NOW())')
        ->execute([$userId, $email, $_SERVER['REMOTE_ADDR'] ?? '']);
} catch (\Throwable $e) { /* non bloquant */ }

jsonOut([
    'success' => true,
    'user_id' => $userId,
    'prenom'  => $prenom,
    'email'   => $email,
    'token'   => $token,
    'message' => 'Compte créé avec succès.',
]);