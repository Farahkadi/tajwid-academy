
<?php

session_start();

// Supprimer les informations de connexion
$_SESSION = [];

// Détruire la session
session_destroy();

// Retourner à l'accueil
header("Location: ../index.php");
exit;

?>
