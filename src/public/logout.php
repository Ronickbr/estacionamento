<?php
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: index.php');
exit();
?>