<?php
// Initialize safe application session context
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

// CRITICAL FIX: Closed the open condition statement cleanly before setting parameters
if (!isset($_SESSION['user_id'])) {
    // Force redirect unauthenticated web traffic back to the secure entry gateway screen
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$page_title = "Dashboard";
require_once __DIR__ . '/includes/header.php';

// --- LAYER A: STATS CARD COUNTS ---
$stmtActiveAgr = $pdo->query("SELECT COUNT(*) FROM agreements WHERE initial_status = 'Active'");
$totalActiveAgreements = $stmtActiveAgr->fetchColumn();

$stmtCasesCount = $pdo->query("SELECT COUNT(*) FROM court_cases");
$totalCases = $stmtCasesCount->fetchColumn();
$stmtSettledCount = $pdo->query("SELECT COUNT(*) FROM court_cases WHERE case_status = 'Settled'");
$settledCases = $stmtSettledCount->fetchColumn();
$disputeResolutionRate = $totalCases > 0 ? round(($settledCases / $totalCases) * 100, 1) : 100.0;

$stmtPaMissing = $pdo->query("SELECT COUNT(*) FROM (
    SELECT id FROM agreements WHERE pa_ref_number IS NULL OR pa_ref_number = ''
    UNION ALL
    SELECT id FROM court_cases WHERE pa_ref_number IS NULL OR pa_ref_number = ''
) as combined_pa");
$awaitingPaInitialization = $stmtPaMissing->fetchColumn();

$stmtArchiveMatch = $pdo->query("SELECT COUNT(*) FROM agreements WHERE cabinet_id IS NOT NULL AND cabinet_id > 0");
$archiveMatchedAgreements = $stmtArchiveMatch->fetchColumn();
$stmtTotalAgreements = $pdo->query("SELECT COUNT(*) FROM agreements");
$totalAgreementsGlobal = $stmtTotalAgreements->fetchColumn();
$archiveMatchRate = $totalAgreementsGlobal > 0 ? round(($archiveMatchedAgreements / $totalAgreementsGlobal) * 100, 0) : 100;

// --- LAYER B: SIDE PANEL RIGHT STATS ---
$stmtPendingAgr = $pdo->query("SELECT COUNT(*) FROM agreements WHERE initial_status = 'Pending'");
$pendingAgreementsCount = $stmtPendingAgr->fetchColumn();

$stmtRenewalCases = $pdo->query("SELECT COUNT(*) FROM court_cases WHERE case_status IN ('Filing Stage', 'In Progress')");
$casesRenewalCount = $stmtRenewalCases->fetchColumn();

// Fetch both the date and the specific dispute name for the upcoming calendar item
$stmtNextHearing = $pdo->query("SELECT next_hearing_date, case_parties FROM court_cases WHERE next_hearing_date >= CURDATE() ORDER BY next_hearing_date ASC LIMIT 1");
$nextHearingRow = $stmtNextHearing->fetch(PDO::FETCH_ASSOC);

if ($nextHearingRow) {
    $nextHearingDate = date('F d', strtotime($nextHearingRow['next_hearing_date']));
    $nextHearingCase = htmlspecialchars($nextHearingRow['case_parties']);
} else {
    $nextHearingDate = 'None Scheduled';
    $nextHearingCase = 'Clear schedule';
}

$stmtMissingEcfSum = $pdo->query("SELECT SUM(amount) FROM payments WHERE ecf_ref_number IS NULL OR ecf_ref_number = ''");
$awaitingEcfAmount = $stmtMissingEcfSum->fetchColumn() ?: 0.00;

// --- LAYER C: ACTIVE LITIGATION RECORDS (LIMIT 2) ---
$activeLitigations = $pdo->query("
    SELECT cc.id, cc.case_parties, cc.case_status, gc.company_name AS group_company, cr.room_name, cab.cabinet_location 
    FROM court_cases cc
    JOIN group_companies gc ON cc.group_company_id = gc.id
    JOIN court_rooms cr ON cc.court_id = cr.id
    JOIN archive_cabinets cab ON cc.cabinet_id = cab.id
    WHERE cc.case_status != 'Settled'
    ORDER BY cc.id DESC LIMIT 2
")->fetchAll(PDO::FETCH_ASSOC);

// --- LAYER D: LINKED AGREEMENTS & PAYMENTS (LIMIT 1) ---
$linkedAgreements = $pdo->query("
    SELECT a.id, a.title, a.pa_ref_number, a.ecf_ref_number, a.initial_status
    FROM agreements a
    ORDER BY a.id DESC LIMIT 1
")->fetchAll(PDO::FETCH_ASSOC);

// --- LAYER E: COMPREHENSIVE DYNAMIC TASK AGENDA ---
$agendaTasks = $pdo->query("
    SELECT id, CONCAT('Review Pending Agreement: ', title) AS task_title, 'Pending' AS task_status, '#f59e0b' AS left_border_color FROM agreements WHERE initial_status = 'Pending'
    UNION ALL
    SELECT id, CONCAT('Add New Court Case: ', case_parties) AS task_title, 'Filing Stage' AS task_status, '#3b82f6' AS left_border_color FROM court_cases WHERE case_status = 'Filing Stage'
    UNION ALL
    SELECT id, CONCAT('Link Finance Codes for: ', description, ' (Rs. ', FORMAT(amount, 0), ')') AS task_title, 'Missing Ref' AS task_status, '#ef4444' AS left_border_color FROM payments WHERE pa_ref_number = '' OR pa_ref_number IS NULL OR ecf_ref_number = '' OR ecf_ref_number IS NULL
    UNION ALL
    SELECT id, CONCAT('Upload Document Scan to Vault for: ', title) AS task_title, 'Upload Pending' AS task_status, '#64748b' AS left_border_color FROM agreements WHERE file_attachment_path IS NULL OR file_attachment_path = '[]' OR file_attachment_path = 'null'
    ORDER BY id DESC LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$companiesLookup = $pdo->query("SELECT * FROM group_companies ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>


<!-- Top Header Branding & Filter Controls -->
<div class="dashboard-action-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; background: #ffffff; padding: 16px 24px; border-radius: 12px; border: 1px solid #e2e8f0;">
    <div>
        <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0;">Main Dashboard</h1>
        <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">Real-time stats for the company</p>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
        <select id="dashboardEntityFilter" class="dropdown-selector-filter" style="margin-bottom: 0; background: #f8fafc; padding: 10px 16px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 600; color: #334155;">
            <option value="">🏢 All Companies</option>
            <?php foreach ($companiesLookup as $comp): ?>
                <option value="<?php echo $comp['id']; ?>"><?php echo htmlspecialchars($comp['company_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <!-- <button class="btn" style="background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; padding: 10px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
            <span>⚠ Missing ECF Alerts</span>
        </button> -->
    </div>
</div>

<!-- Main Layout Grid -->
<div class="dashboard-grid-layout" style="display: grid; grid-template-columns: 1fr 400px; gap: 24px; align-items: start;">
    
    <!-- LEFT COLUMN: PRIMARY WORKSPACE -->
    <div class="main-dashboard-content" id="dynamicLeftWorkspace" style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Zone 1: Top Stats Cards -->
        <div class="health-cards-matrix" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            
            <!-- 1. Active Contracts Card -->
            <div class="metric-card-block" style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Active Contracts</div>
                    <div style="font-size: 32px; font-weight: 800; color: #1e293b; margin-top: 6px; line-height: 1;"><?php echo $totalActiveAgreements; ?></div>
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 10px; border-top: 1px solid #f1f5f9; padding-top: 8px; line-height: 1.4;">
                    Live, signed corporate deals running right now.
                </div>
            </div>
            
            <!-- 2. Finished Cases Card -->
            <div class="metric-card-block" style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Finished Cases</div>
                    <div style="font-size: 32px; font-weight: 800; color: #0f766e; margin-top: 6px; line-height: 1;"><?php echo $disputeResolutionRate; ?>%</div>
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 10px; border-top: 1px solid #f1f5f9; padding-top: 8px; line-height: 1.4;">
                    Percentage of legal disputes successfully resolved and closed.
                </div>
            </div>
            
            <!-- 3. Needs PA Number Card -->
            <div class="metric-card-block" style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #ea580c; text-transform: uppercase; letter-spacing: 0.5px;">Needs PA Number</div>
                    <div style="font-size: 32px; font-weight: 800; color: #ea580c; margin-top: 6px; line-height: 1;"><?php echo $awaitingPaInitialization; ?></div>
                </div>
                <div style="font-size: 11px; color: #a24415; margin-top: 10px; border-top: 1px solid #ffedd5; padding-top: 8px; line-height: 1.4;">
                    Items missing required procurement tracking numbers.
                </div>
            </div>
            
            <!-- 4. Filing Match Card -->
            <div class="metric-card-block" style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Filing Match</div>
                    <div style="font-size: 32px; font-weight: 800; color: #16a34a; margin-top: 6px; line-height: 1;"><?php echo $archiveMatchRate; ?>%</div>
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 10px; border-top: 1px solid #f1f5f9; padding-top: 8px; line-height: 1.4;">
                    Digital agreements successfully stored in physical office cabinets.
                </div>
            </div>
            
        </div>

        <!-- Zone 2: Task Agenda -->
        <div style="background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="position: relative; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; width: 100%;">
                <div>
                    <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Tasks to Do</h2>
                    <p style="font-size: 11px; color: #94a3b8; margin: 2px 0 0 0;">List of things that need fixing right now</p>
                </div>
                <button type="button" class="btn btn-secondary" style="margin: 0; padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; border-radius: 6px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569;" onclick="openAgendaDrawer()">
                    See All Tasks
                </button>
            </div>

            <div class="agenda-list-stack" style="display: flex; flex-direction: column; gap: 10px;">
                <?php if (count($agendaTasks) > 0): ?>
                    <?php foreach ($agendaTasks as $task): ?>
                        <?php 
                            $badgeType = 'progress';
                            if ($task['task_status'] === 'Pending') $badgeType = 'pending';
                            if ($task['task_status'] === 'Filing Stage') $badgeType = 'progress';
                            if ($task['task_status'] === 'Missing Ref') $badgeType = 'error';
                            if ($task['task_status'] === 'Upload Pending') $badgeType = 'pending';
                        ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 14px 20px; border-radius: 8px; border-left: 4px solid <?php echo $task['left_border_color']; ?>; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                            <span style="font-size: 13px; font-weight: 600; color: #334155;">
                                <?php echo htmlspecialchars($task['task_title']); ?>
                            </span>
                            <span class="status-badge <?php echo $badgeType; ?>" style="font-weight: 700; font-size: 10px; padding: 4px 10px; text-transform: uppercase; border-radius: 4px;">
                                <?php echo htmlspecialchars($task['task_status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="background: #f8fafc; padding: 32px; border-radius: 8px; text-align: center; color: #64748b; font-size: 13px; font-weight: 600; border: 1px dashed #cbd5e1;">
                        ✔ No tasks found! Everything looks clean.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<!-- RIGHT COLUMN: SIDE PANEL -->
<div class="side-dashboard-panel" id="dynamicRightWorkspace" style="background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 28px;">
        
    <!-- Backlog Metrics -->
    <div>
        <h3 style="font-size: 13px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0; text-transform: uppercase;">Total Backlog</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            
            <!-- 1. Pending Drafts Card -->
            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 28px; font-weight: 800; color: #1e293b; line-height: 1;"><?php echo $pendingAgreementsCount; ?></div>
                    <div style="font-size: 11px; font-weight: 600; color: #64748b; margin-top: 6px;">Pending Drafts</div>
                </div>
                <div style="font-size: 10px; color: #64748b; margin-top: 8px; border-top: 1px solid #e2e8f0; padding-top: 6px; line-height: 1.3;">
                    Contracts currently being written or edited that aren't officially signed yet.
                </div>
            </div>
            
            <!-- 2. Open Cases Card -->
            <div style="background: #fff7ed; padding: 16px; border-radius: 8px; border: 1px solid #ffedd5; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 28px; font-weight: 800; color: #c2410c; line-height: 1;"><?php echo $casesRenewalCount; ?></div>
                    <div style="font-size: 11px; font-weight: 600; color: #c2410c; margin-top: 6px;">Open Cases</div>
                </div>
                <div style="font-size: 10px; color: #a24415; margin-top: 8px; border-top: 1px solid #fed7aa; padding-top: 6px; line-height: 1.3;">
                    Active, unresolved disputes currently moving through the court system.
                </div>
            </div>
            
            <!-- 3. Next Hearing Card -->
            <!-- 3. Next Hearing Card (Enhanced with case titles context) -->
            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                        <div style="font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.2; min-height: 28px; display: flex; align-items: center;"><?php echo $nextHearingDate; ?></div>
                        <div style="font-size: 11px; font-weight: 600; color: #64748b; margin-top: 4px;">Next Hearing</div>
                        <div style="font-size: 12px; font-weight: 700; color: #475569; margin-top: 6px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 150px;" title="<?php echo $nextHearingCase; ?>">
                            ⚖ <?php echo $nextHearingCase; ?>
                        </div>
                    </div>
                    <div style="font-size: 10px; color: #64748b; margin-top: 8px; border-top: 1px solid #e2e8f0; padding-top: 6px; line-height: 1.3;">
                        The very next urgent calendar date our legal team must appear in court.
                    </div>
                </div>
            
            <!-- 4. Open Payments Card -->
            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.2; min-height: 28px; display: flex; align-items: center;">Rs. <?php echo number_format($awaitingEcfAmount, 0); ?></div>
                    <div style="font-size: 11px; font-weight: 600; color: #64748b; margin-top: 6px;">Open Payments</div>
                </div>
                <div style="font-size: 10px; color: #64748b; margin-top: 8px; border-top: 1px solid #e2e8f0; padding-top: 6px; line-height: 1.3;">
                    Payouts currently blocked or frozen because they are missing an ECF tracking code.
                </div>
            </div>
            
        </div>
    </div>

        <!-- Active Litigation List -->
        <div>
            <h3 style="font-size: 13px; font-weight: 700; color: #64748b; margin: 0 0 12px 0; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Active Cases</h3>
            <div class="feed-list" style="display: flex; flex-direction: column; gap: 14px;">
                <?php foreach ($activeLitigations as $lit): ?>
                    <a href="<?php echo BASE_URL; ?>court-cases/index.php" style="text-decoration: none; display: flex; justify-content: space-between; align-items: center; padding: 6px 0;" class="dashboard-feed-row">
                        <div style="max-width: 280px;">
                            <div style="font-size: 13px; font-weight: 700; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($lit['case_parties']); ?></div>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                                <?php echo htmlspecialchars($lit['group_company']); ?> <span style="color: #cbd5e1;">|</span> <?php echo htmlspecialchars($lit['room_name']); ?>
                            </div>
                        </div>
                        <span style="color: #94a3b8; font-weight: bold; font-size: 16px;">&rsaquo;</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Agreements Feed -->
        <div>
            <h3 style="font-size: 13px; font-weight: 700; color: #64748b; margin: 0 0 12px 0; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Recent Agreements</h3>
            <div class="feed-list" style="display: flex; flex-direction: column; gap: 14px;">
                <?php foreach ($linkedAgreements as $agr): ?>
                    <a href="<?php echo BASE_URL; ?>agreements/index.php" style="text-decoration: none; display: flex; justify-content: space-between; align-items: center; padding: 6px 0;">
                        <div style="max-width: 280px;">
                            <div style="font-size: 13px; font-weight: 700; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($agr['title']); ?></div>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                                PA: <?php echo htmlspecialchars($agr['pa_ref_number'] ?: 'N/A'); ?> <span style="color: #cbd5e1;">|</span> ECF: <?php echo htmlspecialchars($agr['ecf_ref_number'] ?: 'N/A'); ?>
                            </div>
                        </div>
                        <span style="color: #94a3b8; font-weight: bold; font-size: 16px;">&rsaquo;</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<!-- Drawer Custom Overlay Rules -->
<style>
    #agendaDrawerOverlay {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        background: rgba(15, 23, 42, 0.4) !important;
        z-index: 9999999 !important;
        display: none;
        justify-content: flex-end;
    }
    .side-drawer-panel {
        width: 460px !important;
        height: 100% !important;
        background: #ffffff !important;
        padding: 32px 24px !important;
        display: flex !important;
        flex-direction: column !important;
        box-shadow: -8px 0 32px rgba(15, 23, 42, 0.15) !important;
        box-sizing: border-box !important;
        animation: drawerSlideIn 0.2s ease-out;
    }
    @keyframes drawerSlideIn {
        from { transform: translateX(100%); }
        to { transform: translateX(0); }
    }
</style>

<!-- Hidden Drawer Markup Container -->
<div id="agendaDrawerOverlay" onclick="closeAgendaDrawer()">
    <div class="side-drawer-panel" onclick="event.stopPropagation()">
        <div class="drawer-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">
            <div>
                <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">All System Tasks</h2>
                <p style="font-size: 11px; color: #64748b; margin: 2px 0 0 0;">Complete list of tasks to process</p>
            </div>
            <span onclick="closeAgendaDrawer()" style="cursor: pointer; font-weight: bold; color: #64748b; font-size: 18px; padding: 4px 8px;">✕</span>
        </div>
        <div id="drawerAgendaItemsContainer" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; padding-right: 4px;">
            <p style="text-align: center; color: #64748b; font-size: 13px;">Loading tasks...</p>
        </div>
    </div>
</div>

<script>
function openAgendaDrawer() {
    const overlay = document.getElementById('agendaDrawerOverlay');
    const container = document.getElementById('drawerAgendaItemsContainer');
    
    // Safety check: Move the overlay directly under the main document body context
    if (overlay && overlay.parentNode !== document.body) {
        document.body.appendChild(overlay);
    }
    
    container.innerHTML = '<p style="text-align: center; color: #64748b; font-size: 13px; font-weight: 600; padding-top: 20px;">Fetching all pending system alerts...</p>';
    overlay.style.display = 'flex';

const fd = new FormData();
    fd.append('action', 'fetch_all_agenda_tasks');

    const endpoint = (typeof BASE_URL !== 'undefined') ? BASE_URL + 'config/router.php' : 'config/router.php';

    fetch(endpoint, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data.length > 0) {
            container.innerHTML = '';
            res.data.forEach(task => {
                let badgeType = 'progress';
                if (task.task_status === 'Pending') badgeType = 'pending';
                if (task.task_status === 'Filing Stage') badgeType = 'progress';
                if (task.task_status === 'Missing Ref') badgeType = 'error';
                if (task.task_status === 'Upload Pending') badgeType = 'pending';

                container.innerHTML += `
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 16px 20px; border-radius: 8px; border-left: 4px solid ${task.left_border_color}; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                        <span style="font-size: 13px; font-weight: 600; color: #334155; line-height: 1.4; max-width: 320px;">${task.task_title}</span>
                        <span class="status-badge ${badgeType}" style="font-weight: 700; font-size: 10px; padding: 4px 8px; text-transform: uppercase; white-space: nowrap; border-radius: 4px;">${task.task_status}</span>
                    </div>`;
            });
        } else {
            container.innerHTML = '<div style="text-align: center; color: #64748b; font-size: 13px; font-weight: 600; padding-top: 40px;">✔ All backlog cleared successfully!</div>';
        }
    })
    .catch(err => {
        container.innerHTML = '<p style="color: #ef4444; text-align: center; font-size: 12px;">Failed to aggregate backend data items.</p>';
    });
}

function closeAgendaDrawer() {
    const overlay = document.getElementById('agendaDrawerOverlay');
    if (overlay) overlay.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const filterSelect = document.getElementById('dashboardEntityFilter');
    const ecfAlertBtn = document.querySelector('.dashboard-action-header .btn');
    let ecfFilterActive = false;

    // 1. Dropdown filter logic
    if (filterSelect) {
        filterSelect.addEventListener('change', refreshDashboardData);
    }

    // 2. Missing ECF alert button highlight toggle logic
    if (ecfAlertBtn) {
        ecfAlertBtn.addEventListener('click', function(e) {
            e.preventDefault();
            ecfFilterActive = !ecfFilterActive;
            
            // Adjust styles visually to show active state
            if (ecfFilterActive) {
                this.style.background = '#ef4444';
                this.style.color = '#ffffff';
            } else {
                this.style.background = '#fef2f2';
                this.style.color = '#ef4444';
            }
            refreshDashboardData();
        });
    }

    // 3. Central execution function
    function refreshDashboardData() {
        const companyId = filterSelect ? filterSelect.value : '';
        
        const fd = new FormData();
        fd.append('action', 'filter_dashboard_metrics');
        fd.append('group_company_id', companyId);
        fd.append('missing_ecf_only', ecfFilterActive ? '1' : '0');

        // Add visual loading indicator transparency
        document.getElementById('dynamicLeftWorkspace').style.opacity = '0.5';
        document.getElementById('dynamicRightWorkspace').style.opacity = '0.5';

        const endpoint = (typeof BASE_URL !== 'undefined') ? BASE_URL + 'config/router.php' : 'config/router.php';

        fetch(endpoint, {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            document.getElementById('dynamicLeftWorkspace').style.opacity = '1';
            document.getElementById('dynamicRightWorkspace').style.opacity = '1';

            console.log("Backend AJAX Response Payload:", res);

            if (res.success && res.html) {
                // Update workspace fragments dynamically
                if (res.html.left_panel) {
                    document.getElementById('dynamicLeftWorkspace').innerHTML = res.html.left_panel;
                }
                if (res.html.right_panel) {
                    document.getElementById('dynamicRightWorkspace').innerHTML = res.html.right_panel;
                }
            }
        })
        .catch(err => {
            document.getElementById('dynamicLeftWorkspace').style.opacity = '1';
            document.getElementById('dynamicRightWorkspace').style.opacity = '1';
            console.error('Filtering pipeline error:', err);
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

