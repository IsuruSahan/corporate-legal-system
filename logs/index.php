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
        <tbody id="data-body">
<!-- JavaScript will fill it on load -->
        </tbody>
    </table>
</div>

<script>
    // 1. Initialize pagination controls
    initPagination('audit_logs', 'data-body');

    // 2. Load the first page immediately on document ready
    document.addEventListener("DOMContentLoaded", function() {
        paginate('audit_logs', 'data-body', 1); 
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>