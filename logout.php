<?php
require_once 'config.php';
unset($_SESSION['user']);
header('Location: ' . BASE_URL . 'login.php');
exit;
?>
