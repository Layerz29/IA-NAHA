
<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: seconnecter.php');
exit;
