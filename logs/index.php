<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Strict Security Barrier: Keep audit tracking reports locked down to Administrators only
if ($_SESSION['user_role'] !== 'Admin') {
    echo '<script>window.location.href="../index.php";</script>';
    exit;
}

$page_title = "System Security & Mutation Logs";
$breadcrumb = "SYSTEM ARCHITECTURE / AUDIT TRAIL";
require_once __DIR__ . '/../includes/header.php';

// Fetch the complete audit trail history array log sequentially
$logs = $pdo->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 500")->fetchAll();
?>

<div class="data-ledger-card">
    <table class="data-ledger-table">
        <thead>
            <tr>
                <th style="width: 180px;">TIMESTAMP</th>
                <th style="width: 140px;">OPERATIONAL OPERATOR</th>
                <th style="width: 100px;">ACTION TYPE</th>
                <th style="width: 150px;">TARGET COMPONENT</th>
                <th>MUTATION TRACE SUMMARY</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($logs) > 0): ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <span class="text-data-regular" style="font-family: monospace; font-size: 12px; font-weight: 600; color: var(--text-dark);">
                                <?php echo date('Y-m-d H:i:s', strtotime($log['timestamp'])); ?>
                            </span>
                        </td>
                        <td>
                            <div class="primary-line" style="font-size: 13px; font-weight: 600;"><?php echo htmlspecialchars($log['user_name']); ?></div>
                            <div style="font-size: 11px; color: var(--text-light); font-weight: 500; margin-top: 1px;">Role: <?php echo htmlspecialchars($log['user_role']); ?></div>
                        </td>
                        <td>
                            <?php 
                            $badge = 'pending';
                            if($log['action_type'] === 'INSERT') $badge = 'linked';
                            if($log['action_type'] === 'UPDATE') $badge = 'progress';
                            if($log['action_type'] === 'DELETE') $badge = 'error';
                            ?>
                            <span class="status-badge <?php echo $badge; ?>" style="display: block; text-align: center; font-size: 10px; padding: 2px 0;">
                                <?php echo $log['action_type']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="text-data-bold" style="font-size: 12px; color: var(--primary-brand);">
                                📁 <?php echo htmlspecialchars($log['module_target']); ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-size: 13px; font-weight: 500; color: var(--text-muted); line-height: 1.4;">
                                <?php echo htmlspecialchars($log['meta_description']); ?>
                                <span style="font-size: 11px; color: var(--text-light); font-family: monospace; margin-left: 6px;">(Ref ID: #<?php echo $log['record_id']; ?>)</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 100px; color: var(--text-light);">
                        No ledger mutations recorded yet inside the tracking subsystem registry.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>