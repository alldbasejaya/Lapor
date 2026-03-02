<?php
/**
 * Language Switcher Component
 * Usage: include 'includes/language_switcher.php';
 */

if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}

$currentLang = getCurrentLanguage();
$languages = getAvailableLanguages();
?>

<div class="language-switcher">
    <form method="POST" action="lang/switch.php" id="lang-form">
        <select name="language" onchange="this.form.submit()" class="language-select">
            <?php foreach ($languages as $code => $name): ?>
                <option value="<?php echo htmlspecialchars($code); ?>" 
                        <?php echo ($currentLang === $code) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<style>
.language-switcher {
    position: fixed;
    top: 15px;
    right: 20px;
    z-index: 1000;
}

/* Sidebar language switcher */
.sidebar .language-switcher {
    position: static;
    padding: 10px 15px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar .language-select {
    width: 100%;
    background: rgba(255,255,255,0.1);
    color: white;
    border: 1px solid rgba(255,255,255,0.2);
}

.sidebar .language-select option {
    background: #1e293b;
    color: white;
}

.language-select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
    color: #333;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.language-select:hover {
    border-color: #4a90e2;
    box-shadow: 0 2px 8px rgba(74,144,226,0.2);
}

.language-select:focus {
    outline: none;
    border-color: #4a90e2;
    box-shadow: 0 0 0 3px rgba(74,144,226,0.1);
}

/* For login/register pages */
.login-page .language-switcher {
    top: 15px;
    right: 15px;
}
</style>
