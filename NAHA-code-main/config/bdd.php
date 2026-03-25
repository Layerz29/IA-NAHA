<?php
function initCompatSchema(PDO $bdd): void {
  // Tables/Views attendues par le code existant (ancien schéma).
  // On les "reconstruit" à partir des tables BD actuelles quand c'est possible.

  // --- Tables de journalisation (consommation / sport) ---
  $bdd->exec("
    CREATE TABLE IF NOT EXISTS consommation (
      id INT AUTO_INCREMENT PRIMARY KEY,
      id_utilisateur INT NOT NULL,
      id_produit INT NOT NULL,
      quantite FLOAT NOT NULL,
      date_conso DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_consommation_user_date (id_utilisateur, date_conso),
      INDEX idx_consommation_user_produit (id_utilisateur, id_produit)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  $bdd->exec("
    CREATE TABLE IF NOT EXISTS activite (
      id INT AUTO_INCREMENT PRIMARY KEY,
      id_utilisateur INT NOT NULL,
      id_sport INT NOT NULL,
      duree_minutes INT NOT NULL,
      date_sport DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_activite_user_date (id_utilisateur, date_sport),
      INDEX idx_activite_user_sport (id_utilisateur, id_sport)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // --- Objectifs (questionnaire IA) ---
  $bdd->exec("
    CREATE TABLE IF NOT EXISTS objectif_utilisateur (
      id INT AUTO_INCREMENT PRIMARY KEY,
      id_utilisateur INT NOT NULL,
      objectif_nom VARCHAR(50) DEFAULT NULL,
      objectif_kcal INT DEFAULT NULL,
      maintenance INT DEFAULT NULL,
      age INT DEFAULT NULL,
      poids INT DEFAULT NULL,
      taille INT DEFAULT NULL,
      activite FLOAT DEFAULT NULL,
      sexe CHAR(1) DEFAULT NULL,
      date_maj DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_objectif_user_date (id_utilisateur, date_maj)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // --- Produits / Sports : vues sur les tables BD existantes ---
  // Note : CREATE VIEW n'accepte pas IF NOT EXISTS → on drop/recreate.
  $bdd->exec("DROP VIEW IF EXISTS sports");
  $bdd->exec("
    CREATE VIEW sports AS
    SELECT
      id AS id_sport,
      major_heading AS nom_sport,
      met_value AS MET,
      cal_60kg_h AS kcal_h_60kg,
      cal_70kg_h AS kcal_h_70kg,
      cal_80kg_h AS kcal_h_80kg
    FROM compendium_sports
  ");

  $bdd->exec("DROP VIEW IF EXISTS produits");
  $bdd->exec("
    CREATE VIEW produits AS
    SELECT
      id AS id_produit,
      nom AS nom_produit,
      calories_kcal_100g AS energie_kcal,
      proteines_g_100g AS proteines,
      glucides_g_100g AS glucides,
      lipides_g_100g AS lipides,
      fibres_g_100g AS fibres
    FROM ciqual_nutrition
  ");

  // --- Conseils / Newsletter / Contact (pages annexes) ---
  $bdd->exec("
    CREATE TABLE IF NOT EXISTS conseils (
      id INT AUTO_INCREMENT PRIMARY KEY,
      texte TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  $bdd->exec("
    CREATE TABLE IF NOT EXISTS newsletter_users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      email VARCHAR(255) NOT NULL,
      UNIQUE KEY uniq_newsletter_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  $bdd->exec("
    CREATE TABLE IF NOT EXISTS contact_message (
      id INT AUTO_INCREMENT PRIMARY KEY,
      id_utilisateur INT DEFAULT NULL,
      nom VARCHAR(100) NOT NULL,
      email VARCHAR(255) NOT NULL,
      sujet VARCHAR(255) NOT NULL,
      message TEXT NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // Seed minimal pour `conseils` si table vide
  $count = (int)$bdd->query("SELECT COUNT(*) FROM conseils")->fetchColumn();
  if ($count === 0) {
    $bdd->exec("
      INSERT INTO conseils (texte) VALUES
        ('Buvez suffisamment d''eau (souvent 1,5 à 2L/j).'),
        ('Priorisez une alimentation riche en fibres (légumes, fruits, légumineuses).'),
        ('Variez les sources de protéines pour un apport complet.'),
        ('Bougez régulièrement : marche, vélo, natation, adapté à ton rythme.'),
        ('Ajustez les portions selon la faim et la satiété, pas uniquement les calories.');
    ");
  }
}

function getBD() {
  try{

    //si vous arrivez pas à vous connecter, vérifier le bon port

    //si vous arrivez pas à vous connecter, vérifier le bon port ahmed tu peux repush ton code de base ? j'ai push le mien sans faire exprès

    $dsn = "mysql:host=localhost;port=8888;dbname=ia-naha;charset=utf8";
    $user = "root";
    $pass = "root";  

    $bdd = new PDO($dsn, $user, $pass);

    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Assure que le schéma "attendu par le code" existe.
    // (Ton `BD_NAHA.sql` ne contient pas toutes les tables du code, donc on crée les équivalents.)
    initCompatSchema($bdd);

    return $bdd;

}catch(Exception $e){
    die('Erreur : ' . $e->getMessage());
  }
}
?>
