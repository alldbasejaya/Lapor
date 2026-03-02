<?php
if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}
?>
<aside class="sidebar">
    <?php include __DIR__ . '/../../includes/language_switcher.php'; ?>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span>
            <span><?php echo __('common.dashboard'); ?></span>
        </a>
        <a href="review.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'review.php' ? 'active' : ''; ?>">
            <span class="nav-icon">✅</span>
            <span><?php echo __('admin.review_reports'); ?></span>
        </a>
        <a href="reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📁</span>
            <span><?php echo __('admin.all_reports'); ?></span>
        </a>
        <a href="users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
            <span class="nav-icon">👥</span>
            <span><?php echo __('admin.user_management'); ?></span>
        </a>
        <a href="profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
            <span class="nav-icon">⚙</span>
            <span><?php echo __('common.profile'); ?></span>
        </a>
        <a href="../index.php" class="nav-item">
            <span class="nav-icon">🏠</span>
            <span><?php echo __('common.home'); ?></span>
        </a>
        <a href="../auth/logout.php" class="nav-item nav-logout">
            <span class="nav-icon">🚪</span>
            <span><?php echo __('common.logout'); ?></span>
        </a>
    </nav>
</aside>
