<?php
/**
 * Language Switcher Handler
 */
define('APP_INIT', true);
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
    $lang = $_POST['language'];
    
    if (setLanguage($lang)) {
        // Store success message
        $_SESSION['lang_changed'] = true;
    }
}

// Redirect back
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
header('Location: ' . $referer);
exit();
?>
