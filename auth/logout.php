<?php
define('APP_INIT', true);
require_once '../config/config.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page
header("Location: " . BASE_URL . "index.php?error=logout");
exit();
?>
