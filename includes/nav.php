<?php
$role = $_SESSION['role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar">
    <div class="sidebar-brand">
        <h2>📦 WMS</h2>
    </div>
    <ul class="nav-links">
        <?php if ($role === 'admin'): ?>
            <li><a href="<?php echo BASE_PATH; ?>/admin/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/admin/items.php" class="<?php echo $current_page == 'items.php' ? 'active' : ''; ?>">Items</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/admin/movements.php" class="<?php echo $current_page == 'movements.php' ? 'active' : ''; ?>">Stock Movements</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/admin/reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">Reports</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/admin/users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">Users</a></li>
        <?php else: ?>
            <li><a href="<?php echo BASE_PATH; ?>/user/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/user/items.php" class="<?php echo $current_page == 'items.php' ? 'active' : ''; ?>">Items</a></li>
        <?php endif; ?>
        <li><a href="<?php echo BASE_PATH; ?>/logout.php" class="logout-link">Logout</a></li>
    </ul>
</nav>