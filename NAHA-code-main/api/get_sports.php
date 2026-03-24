<?php
// api/get_sports.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Optionnel : obliger à être connecté pour voir les sports
if (!isset($_SESSION['utilisateur'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non connecté']);
    exit;
}

require_once __DIR__ . '/../config/bdd.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $bdd = getBD();

    $sql = "SELECT id_sport, nom_sport, MET, kcal_h_60kg, kcal_h_70kg, kcal_h_80kg
            FROM sports
            ORDER BY nom_sport ASC";
    $stmt = $bdd->query($sql);
    $sports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($sports);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
}