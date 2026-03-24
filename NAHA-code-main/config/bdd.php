<?php
function getBD() {
  try{

    //si vous arrivez pas à vous connecter, vérifier le bon port

    //si vous arrivez pas à vous connecter, vérifier le bon port ahmed tu peux repush ton code de base ? j'ai push le mien sans faire exprès

    $dsn = "mysql:host=localhost;port=3306;dbname=naha;charset=utf8";
    $user = "root";
    $pass = "root";  

    $bdd = new PDO($dsn, $user, $pass);

    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $bdd;

}catch(Exception $e){
    die('Erreur : ' . $e->getMessage());
  }
}
?>
