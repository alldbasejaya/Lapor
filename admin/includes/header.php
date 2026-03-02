<?php
if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}
?>
<header class="admin-header">
    <div class="header-left">
        <h2 class="logo"><?php echo __('app_name'); ?> - Admin</h2>
    </div>
    <div class="header-right">
        <span class="user-info">
            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            <span class="badge badge-admin"><?php echo ucfirst($_SESSION['role']); ?></span>
        </span>
    </div>
</header>
