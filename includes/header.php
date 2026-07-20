<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1440, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " | Legal" : "Legal Portal"; ?></title>

    <!-- Global JS Constant for API endpoints -->
    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
    </script>

    <!-- Absolute root routing paths for assets -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <script src="<?php echo BASE_URL; ?>assets/js/pagination.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/app.js?v=<?php echo time(); ?>"></script>
</head>
<body>
<div class="app-container">
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <div class="main-workspace">
        <header class="top-header">
            <div class="header-title-block">
                <div class="breadcrumb"><?php echo isset($breadcrumb) ? $breadcrumb : 'SYSTEM'; ?></div>
                <h1><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
            </div>
            
            <div class="header-actions-block">
                <?php if (isset($header_btn_link) && isset($header_btn_text) && isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'Viewer'): ?>
                    <a href="<?php echo $header_btn_link; ?>" class="btn btn-primary btn-tall"><?php echo $header_btn_text; ?></a>
                <?php endif; ?>
                
                <div class="user-profile-badge">
                    <div>
                        <div class="user-meta-name"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Guest'; ?></div>
                        <div class="user-meta-role">
                            <?php 
                            if (isset($_SESSION['user_role'])) {
                                if ($_SESSION['user_role'] === 'Admin') echo 'Legal Admin';
                                elseif ($_SESSION['user_role'] === 'Staff') echo 'Legal Staff (Editor)';
                                else echo 'Read-Only Auditing Scope';
                            } else {
                                echo 'Unauthenticated';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="avatar-circle"></div>
                </div>

            </div>
        </header>