<?php
/**
 * Language Helper Functions
 * Multi-language support for Lapor System
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}

// Available languages
define('AVAILABLE_LANGUAGES', ['id', 'en']);
define('DEFAULT_LANGUAGE', 'id');

// Load language file
function loadLanguage($lang = null) {
    // Determine language
    if ($lang === null) {
        // Check session
        $lang = $_SESSION['language'] ?? DEFAULT_LANGUAGE;
    }
    
    // Validate language
    if (!in_array($lang, AVAILABLE_LANGUAGES)) {
        $lang = DEFAULT_LANGUAGE;
    }
    
    // Load language file
    $langFile = __DIR__ . '/../lang/' . $lang . '.json';
    
    if (file_exists($langFile)) {
        $translations = json_decode(file_get_contents($langFile), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $_SESSION['language'] = $lang;
            $_SESSION['lang_data'] = $translations;
            return $translations;
        }
    }
    
    // Fallback to default language
    $defaultFile = __DIR__ . '/../lang/' . DEFAULT_LANGUAGE . '.json';
    if (file_exists($defaultFile)) {
        $translations = json_decode(file_get_contents($defaultFile), true);
        $_SESSION['language'] = DEFAULT_LANGUAGE;
        $_SESSION['lang_data'] = $translations;
        return $translations;
    }
    
    return [];
}

// Get translation by key (supports dot notation: login.username)
function lang($key, $default = null) {
    static $translations = null;
    
    if ($translations === null) {
        $translations = $_SESSION['lang_data'] ?? loadLanguage();
    }
    
    $keys = explode('.', $key);
    $value = $translations;
    
    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $default ?? $key;
        }
    }
    
    return is_array($value) ? $key : $value;
}

// Alias for lang()
function __($key, $default = null) {
    return lang($key, $default);
}

// Get current language
function getCurrentLanguage() {
    return $_SESSION['language'] ?? DEFAULT_LANGUAGE;
}

// Get all available languages
function getAvailableLanguages() {
    return [
        'id' => 'Indonesia',
        'en' => 'English'
    ];
}

// Change language
function setLanguage($lang) {
    if (in_array($lang, AVAILABLE_LANGUAGES)) {
        $_SESSION['language'] = $lang;
        loadLanguage($lang);
        return true;
    }
    return false;
}

// Initialize language on session start
function initLanguage() {
    if (!isset($_SESSION['lang_data'])) {
        loadLanguage();
    }
}
?>
