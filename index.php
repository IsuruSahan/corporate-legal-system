<?php
// 1. Force the dynamic authentication and database connection layers to load first
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = "Corporate Legal System - Dashboard";
$breadcrumb = "MAIN / DASHBOARD";

// 2. Load the structural layout header
require_once __DIR__ . '/includes/header.php';
?>

<div class="main-workspace-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-top: 20px;">
    
    <div class="form-panel-card" style="padding: 24px; background: #fff; border: 1px solid var(--border-color); border-radius: 16px;">
        <h3 style="font-size: 14px; color: var(--text-light); font-weight: 700; margin-bottom: 8px;">TOTAL AGREEMENTS</h3>
        <?php
            $countAgreements = $pdo->query("SELECT COUNT(*) FROM agreements")->fetchColumn();
            echo "<span style='font-size: 32px; font-weight: 700; color: var(--primary-brand);'>{$countAgreements}</span>";
        ?>
        <div style="margin-top: 12px;"><a href="/corporate-legal-system/agreements/index.php" style="font-size: 13px; color: var(--primary-brand); font-weight: 600; text-decoration: none;">View Registry →</a></div>
    </div>

    <div class="form-panel-card" style="padding: 24px; background: #fff; border: 1px solid var(--border-color); border-radius: 16px;">
        <h3 style="font-size: 14px; color: var(--text-light); font-weight: 700; margin-bottom: 8px;">SECURITY AUDIT TRAILS</h3>
        <?php
            $countLogs = $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
            echo "<span style='font-size: 32px; font-weight: 700; color: var(--text-dark);'>{$countLogs}</span>";
        ?>
        <div style="margin-top: 12px;">
            <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                <a href="/corporate-legal-system/logs/index.php" style="font-size: 13px; color: var(--primary-brand); font-weight: 600; text-decoration: none;">View Audit Logs →</a>
            <?php else: ?>
                <span style="font-size: 12px; color: var(--text-light);">Restricted to Administrators</span>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>