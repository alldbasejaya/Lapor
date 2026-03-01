<aside class="sidebar">
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span>
            <span>Dashboard</span>
        </a>
        <a href="review.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'review.php' ? 'active' : ''; ?>">
            <span class="nav-icon">✅</span>
            <span>Review Reports</span>
        </a>
        <a href="reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📁</span>
            <span>All Reports</span>
        </a>
        <a href="users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
            <span class="nav-icon">👥</span>
            <span>User Management</span>
        </a>
        <a href="profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
            <span class="nav-icon">⚙</span>
            <span>Profile Settings</span>
        </a>
        <a href="../index.php" class="nav-item">
            <span class="nav-icon">🏠</span>
            <span>Home</span>
        </a>
        <a href="../auth/logout.php" class="nav-item nav-logout">
            <span class="nav-icon">🚪</span>
            <span>Logout</span>
        </a>
    </nav>
</aside>
