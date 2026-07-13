<?php
// Ensure active navigation highlight context
$current_uri = $_SERVER['REQUEST_URI'];
?>
<div class="sidebar">
    <a href="/corporate-legal-system/index.php" class="brand-logo">BenLegal</a>
    <ul class="nav-links" style="display: flex; flex-direction: column; height: calc(100% - 50px);">
        <li class="nav-item <?php echo (strpos($current_uri, 'index.php') !== false || substr($current_uri, -1) === 'system/') ? 'active' : ''; ?>">
            <a href="/corporate-legal-system/index.php">Home</a>
        </li>
        <li class="nav-item <?php echo (strpos($current_uri, '/agreements/') !== false) ? 'active' : ''; ?>">
            <a href="/corporate-legal-system/agreements/index.php">Agreements</a>
        </li>
        <li class="nav-item <?php echo (strpos($current_uri, '/court-cases/') !== false) ? 'active' : ''; ?>">
            <a href="/corporate-legal-system/court-cases/index.php">Court Cases</a>
        </li>
        <li class="nav-item <?php echo (strpos($current_uri, '/payments/') !== false) ? 'active' : ''; ?>">
            <a href="/corporate-legal-system/payments/index.php">Payments (PA/ECF)</a>
        </li>
        <li class="nav-item <?php echo (strpos($current_uri, '/archives/') !== false) ? 'active' : ''; ?>">
            <a href="/corporate-legal-system/archives/index.php">Physical Archives</a>
        </li>
        <li class="nav-item <?php echo (strpos($current_uri, '/secretarial/') !== false) ? 'active' : ''; ?>">
            <a href="/corporate-legal-system/secretarial/index.php">Secretarial Vault</a>
        </li>
        
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin'): ?>
            <li class="nav-item <?php echo (strpos($current_uri, '/users/') !== false) ? 'active' : ''; ?>">
                <a href="/corporate-legal-system/users/index.php">User Roles</a>
            </li>
            <li class="nav-item <?php echo (strpos($current_uri, '/logs/') !== false) ? 'active' : ''; ?>">
                <a href="/corporate-legal-system/logs/index.php">Change Logs</a>
            </li>
        <?php endif; ?>
        
        <li class="nav-item" style="margin-top: auto; border-top: 1px solid var(--border-color); padding-top: 15px;">
            <a href="/corporate-legal-system/logout.php" style="color: var(--text-unlinked, #E11D48); font-weight: 700;">
            Logout
            </a>
        </li>
    </ul>
</div>