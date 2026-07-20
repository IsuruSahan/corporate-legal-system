<?php
// Ensure active navigation highlight context
$current_uri = $_SERVER['REQUEST_URI'];
$base_path   = parse_url(BASE_URL, PHP_URL_PATH) ?: '/';
?>
<div class="sidebar">
    <a href="<?php echo BASE_URL; ?>index.php" class="brand-logo">BenLegal</a>
    <ul class="nav-links" style="display: flex; flex-direction: column; height: calc(100% - 50px);">

        <?php 
            $is_home = ($current_uri === $base_path || 
                        $current_uri === $base_path . 'index.php' || 
                        rtrim($current_uri, '/') === rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/'));
            ?>
            <li class="nav-item <?php echo $is_home ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            </li>
        <li class="nav-item <?php echo (strpos($current_uri, '/agreements/') !== false) ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>agreements/index.php">Agreements</a>
        </li>
        <li class="nav-item <?php echo (strpos($current_uri, '/court-cases/') !== false) ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>court-cases/index.php">Court Cases</a>
        </li>
        <li class="nav-item <?php echo (strpos($current_uri, '/payments/') !== false) ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>payments/index.php">Payments (PA/ECF)</a>
        </li>
        <li class="nav-item <?php echo (strpos($current_uri, '/archives/') !== false) ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>archives/index.php">Physical Archives</a>
        </li>
        <!-- <li class="nav-item <?php echo (strpos($current_uri, '/secretarial/') !== false) ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>secretarial/index.php">Secretarial Vault</a>
        </li> -->
        
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin'): ?>
            <li class="nav-item <?php echo (strpos($current_uri, '/users/') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>users/index.php">Settings</a>
            </li>
            <li class="nav-item <?php echo (strpos($current_uri, '/logs/') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>logs/index.php">Change Logs</a>
            </li>
        <?php endif; ?>
        
        <li class="nav-item" style="margin-top: auto; border-top: 1px solid var(--border-color); padding-top: 15px;">
            <a href="<?php echo BASE_URL; ?>logout.php" style="color: var(--text-unlinked, #E11D48); font-weight: 700;">
            Logout
            </a>
        </li>
    </ul>
</div>