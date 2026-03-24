<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/bdd.php';
$bdd = getBD();

/* ========= CONSEIL DU JOUR ========= */
/* ========= CONSEIL DU JOUR ========= */
$dayIndex = date('z'); // 0 → 365

// compter les conseils
$total = $bdd->query("SELECT COUNT(*) FROM conseils")->fetchColumn();

if ($total > 0) {
    // créer un index qui boucle entre 0 et total-1
    $offset = $dayIndex % $total;

    $stmt = $bdd->prepare("SELECT texte FROM conseils LIMIT 1 OFFSET :o");
    $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $conseil = $stmt->fetch();
}

// fallback si aucun conseil
if (!$conseil) {
    $conseil = ["texte" => "Aucun conseil dispo aujourd’hui fréro 😭"];
}


/* ========= CSRF ========= */
if (empty($_SESSION['csrf_news'])) {
    $_SESSION['csrf_news'] = bin2hex(random_bytes(32));
}

/* ========= INSCRIPTION NEWSLETTER (AJAX) ========= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'newsletter') {
    header("Content-Type: application/json; charset=utf-8");

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        echo json_encode(['ok' => false, 'msg' => 'Méthode interdite fréro']);
        exit;
    }

    $token = $_POST['csrf'] ?? "";
    if (!hash_equals($_SESSION['csrf_news'], $token)) {
        echo json_encode(['ok' => false, 'msg' => 'Token CSRF invalidé frère 💀']);
        exit;
    }

    $email = trim($_POST["email"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'msg' => 'Email éclaté fréro 🤦‍♂️']);
        exit;
    }

    try {
        $stmt = $bdd->prepare("INSERT IGNORE INTO newsletter_users(email) VALUES (:e)");
        $stmt->execute(['e' => $email]);

        echo json_encode(['ok' => true, 'msg' => 'Tu recevras ton conseil chaque jour 🌱']);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'msg' => 'Erreur serveur 😭']);
    }


    exit;
}
?>

<!-- ======= AFFICHAGE ======= -->
<link rel="stylesheet" href="assets/css/conseil.css">

<div class="conseil-card">
    <h3>Conseil du jour 🍃</h3>
    <p><?= htmlspecialchars($conseil["texte"]) ?></p>
</div>

<script src="assets/js/conseil.js"></script>
