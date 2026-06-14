<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $segments = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
    $loginPath = count($segments) > 2 ? str_repeat('../', count($segments) - 2) . 'login.php' : 'login.php';
    header('Location: ' . $loginPath);
    exit();
}
?>