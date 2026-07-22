<?php
// 1. Force dynamic authentication and database connection layers to load first
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = "Legal Management Reports";
$breadcrumb = "REPORTS / MANAGEMENT CENTER";

// 2. Load the structural layout header template
require_once __DIR__ . '/../includes/header.php';

// --- Capture Filter Inputs ---
$report_type = $_GET['report_type'] ?? 'master_case_register';
$start_date  = $_GET['start_date'] ?? date('Y-01-01');
$end_date    = $_GET['end_date'] ?? date('Y-12-31');
$company_id  = $_GET['company_id'] ?? 'All';
$officer_id  = $_GET['officer_id'] ?? 'All';

// --- Fetch Dropdown Collections ---
$companies = $pdo->query("SELECT id, company_name FROM group_companies ORDER BY company_name ASC")->fetchAll();
$officers  = $pdo->query("SELECT id, full_name FROM users WHERE role IN ('Admin', 'Staff') ORDER BY full_name ASC")->fetchAll();

// --- Execute Selected Report Query ---
$report_title = "Legal Report";
$report_data  = [];

try {
    switch ($report_type) {

        // ==========================================
        // REPORT 1: Master Case Register
        // ==========================================
        case 'master_case_register':
            $report_title = "Master Case Register";
            $sql = "SELECT cc.case_number, cc.case_parties, gc.company_name, cr.room_name as court_name, 
                           cc.case_status, cc.next_hearing_date, u.full_name as officer_name
                    FROM court_cases cc
                    LEFT JOIN group_companies gc ON cc.group_company_id = gc.id
                    LEFT JOIN court_rooms cr ON cc.court_id = cr.id
                    LEFT JOIN users u ON cc.assigned_officer_id = u.id
                    WHERE 1=1";
            
            $params = [];
            if ($company_id !== 'All') { $sql .= " AND cc.group_company_id = :cid"; $params[':cid'] = $company_id; }
            if ($officer_id !== 'All') { $sql .= " AND cc.assigned_officer_id = :oid"; $params[':oid'] = $officer_id; }
            $sql .= " ORDER BY cc.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $report_data = $stmt->fetchAll();
            break;

        // ==========================================
        // REPORT 2: Upcoming Hearings Schedule
        // ==========================================
        case 'upcoming_hearings':
            $report_title = "Upcoming Court Hearings Schedule ({$start_date} to {$end_date})";
            $sql = "SELECT cc.next_hearing_date, cc.case_number, cc.case_parties, gc.company_name, 
                           cr.room_name as court_name, u.full_name as officer_name, cc.case_description
                    FROM court_cases cc
                    LEFT JOIN group_companies gc ON cc.group_company_id = gc.id
                    LEFT JOIN court_rooms cr ON cc.court_id = cr.id
                    LEFT JOIN users u ON cc.assigned_officer_id = u.id
                    WHERE cc.next_hearing_date BETWEEN :sdate AND :edate
                      AND cc.case_status != 'Settled'";
            
            $params = [':sdate' => $start_date, ':edate' => $end_date];
            if ($company_id !== 'All') { $sql .= " AND cc.group_company_id = :cid"; $params[':cid'] = $company_id; }
            if ($officer_id !== 'All') { $sql .= " AND cc.assigned_officer_id = :oid"; $params[':oid'] = $officer_id; }
            $sql .= " ORDER BY cc.next_hearing_date ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $report_data = $stmt->fetchAll();
            break;

        // ==========================================
        // REPORT 3: Next Steps & Deadlines
        // ==========================================
        case 'next_steps_due':
            $report_title = "Procedural Action Steps Due ({$start_date} to {$end_date})";
            $sql = "SELECT cc.next_step_date, cc.case_number, cc.case_parties, cc.next_step_description, 
                           gc.company_name, u.full_name as officer_name, cc.case_status
                    FROM court_cases cc
                    LEFT JOIN group_companies gc ON cc.group_company_id = gc.id
                    LEFT JOIN users u ON cc.assigned_officer_id = u.id
                    WHERE cc.next_step_date BETWEEN :sdate AND :edate
                      AND cc.case_status != 'Settled'";
            
            $params = [':sdate' => $start_date, ':edate' => $end_date];
            if ($company_id !== 'All') { $sql .= " AND cc.group_company_id = :cid"; $params[':cid'] = $company_id; }
            if ($officer_id !== 'All') { $sql .= " AND cc.assigned_officer_id = :oid"; $params[':oid'] = $officer_id; }
            $sql .= " ORDER BY cc.next_step_date ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $report_data = $stmt->fetchAll();
            break;

        // ==========================================
        // REPORT 4: Legal Officer Workload
        // ==========================================
        case 'officer_workload':
            $report_title = "Legal Officer Workload Summary";
            $sql = "SELECT u.full_name as officer_name, u.email,
                           COUNT(cc.id) as total_cases,
                           SUM(CASE WHEN cc.case_status != 'Settled' THEN 1 ELSE 0 END) as active_cases,
                           SUM(CASE WHEN cc.case_status = 'Settled' THEN 1 ELSE 0 END) as settled_cases
                    FROM users u
                    LEFT JOIN court_cases cc ON cc.assigned_officer_id = u.id
                    WHERE u.role IN ('Admin', 'Staff')
                    GROUP BY u.id
                    ORDER BY active_cases DESC";

            $stmt = $pdo->query($sql);
            $report_data = $stmt->fetchAll();
            break;

        // ==========================================
        // REPORT 5: Group Entity Risk Exposure
        // ==========================================
        case 'company_exposure':
            $report_title = "Group Company Litigation Risk & Exposure";
            $sql = "SELECT gc.company_name,
                           COUNT(cc.id) as total_cases,
                           SUM(CASE WHEN cc.case_status != 'Settled' THEN 1 ELSE 0 END) as active_cases,
                           SUM(CASE WHEN cc.case_status = 'Settled' THEN 1 ELSE 0 END) as settled_cases
                    FROM group_companies gc
                    LEFT JOIN court_cases cc ON cc.group_company_id = gc.id
                    GROUP BY gc.id
                    ORDER BY active_cases DESC";

            $stmt = $pdo->query($sql);
            $report_data = $stmt->fetchAll();
            break;

        default:
            $report_data = [];
            break;
    }
} catch (PDOException $e) {
    $error_msg = "Error generating report: " . $e->getMessage();
}
?>

<style>
@media print {
    .no-print, header, footer, .form-panel-card, .report-actions-bar { display: none !important; }
    .data-ledger-card { border: none !important; box-shadow: none !important; }
    .print-header { display: block !important; margin-bottom: 20px; text-align: center; }
}
.print-header { display: none; }

/* In-line pill badges matching core application theme */
.status-pill {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}
.status-active { background: #fef3c7; color: #92400e; }
.status-settled { background: #d1fae5; color: #065f46; }
.status-total { background: #e0f2fe; color: #075985; }
</style>

<!-- Printable Header -->
<div class="print-header">
    <h2>BEN HOLDINGS - LEGAL MANAGEMENT SYSTEM</h2>
    <h4><?php echo htmlspecialchars($report_title); ?></h4>
    <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
    <hr style="border: 0.5px solid #ccc; margin: 15px 0;">
</div>

<!-- Filter Panel Card -->
<form method="GET" action="index.php" class="form-panel-card no-print" style="margin-bottom: 20px;">
    <h2>Legal Reporting Parameters</h2>
    <p class="form-sub-header-desc">Generate detailed, real-time analytics and summaries across all corporate litigation records.</p>

    <div class="form-group-row row-split" style="align-items: flex-end;">
        <div>
            <label class="field-label-text">Select Report Type</label>
            <select name="report_type" class="form-field-select" onchange="toggleDateInputs(this.value)">
                <option value="master_case_register" <?php echo $report_type === 'master_case_register' ? 'selected' : ''; ?>>1. All Court Cases List</option>
                <option value="upcoming_hearings" <?php echo $report_type === 'upcoming_hearings' ? 'selected' : ''; ?>>2. Upcoming Court Dates</option>
                <option value="next_steps_due" <?php echo $report_type === 'next_steps_due' ? 'selected' : ''; ?>>3. Pending Actions & Due Dates</option>
                <option value="officer_workload" <?php echo $report_type === 'officer_workload' ? 'selected' : ''; ?>>4. Cases by Legal Officer</option>
                <option value="company_exposure" <?php echo $report_type === 'company_exposure' ? 'selected' : ''; ?>>5. Cases by Group Company</option>
            </select>
        </div>

        <div>
            <label class="field-label-text">Group Entity Ownership</label>
            <select name="company_id" class="form-field-select">
                <option value="All">All Entities</option>
                <?php foreach ($companies as $comp): ?>
                    <option value="<?php echo $comp['id']; ?>" <?php echo $company_id == $comp['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($comp['company_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="field-label-text">Assigned Officer</label>
            <select name="officer_id" class="form-field-select">
                <option value="All">All Officers</option>
                <?php foreach ($officers as $off): ?>
                    <option value="<?php echo $off['id']; ?>" <?php echo $officer_id == $off['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($off['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-group-row row-split date-field-row" style="<?php echo in_array($report_type, ['upcoming_hearings', 'next_steps_due']) ? 'display: flex;' : 'display: none;'; ?> margin-top: 15px;">
        <div>
            <label class="field-label-text">From Date</label>
            <input type="date" name="start_date" class="form-field-input" value="<?php echo htmlspecialchars($start_date); ?>">
        </div>
        <div>
            <label class="field-label-text">To Date</label>
            <input type="date" name="end_date" class="form-field-input" value="<?php echo htmlspecialchars($end_date); ?>">
        </div>
    </div>

    <div class="form-actions-footer" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border-color);">
        <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">Run Report</button>
    </div>
</form>

<!-- Action Header Bar -->
<div class="report-actions-bar no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <h3 style="font-size: 16px; font-weight: 700; color: var(--text-dark); margin: 0;"><?php echo htmlspecialchars($report_title); ?></h3>
    <div style="display: flex; gap: 10px;">
        <button type="button" onclick="window.print()" class="btn btn-secondary" style="padding: 8px 14px; font-size: 13px;">🖨️ Print / PDF</button>
        <button type="button" onclick="exportTableToCSV('report_table', '<?php echo $report_type; ?>_export.csv')" class="btn btn-primary" style="padding: 8px 14px; font-size: 13px;">📊 Export Excel (CSV)</button>
    </div>
</div>

<!-- Ledger Data Output -->
<div class="data-ledger-card">
    <?php if (!empty($error_msg)): ?>
        <div style="padding: 20px; color: #dc2626; text-align: center; font-weight: 600;"><?php echo $error_msg; ?></div>
    <?php elseif (empty($report_data)): ?>
        <div style="padding: 30px; color: var(--text-muted); text-align: center; font-weight: 500;">No records matching the specified criteria were found in the database archive.</div>
    <?php else: ?>
        <table class="data-ledger-table" id="report_table">
            <thead>
                <tr>
                    <?php if ($report_type === 'master_case_register'): ?>
                        <th>CASE REF</th>
                        <th>PARTIES</th>
                        <th>GROUP ENTITY</th>
                        <th>COURT ROOM</th>
                        <th>STATUS</th>
                        <th>NEXT HEARING</th>
                        <th>RESPONSIBLE OFFICER</th>

                    <?php elseif ($report_type === 'upcoming_hearings'): ?>
                        <th>HEARING DATE</th>
                        <th>CASE REF</th>
                        <th>PARTIES</th>
                        <th>GROUP ENTITY</th>
                        <th>COURT ROOM</th>
                        <th>RESPONSIBLE OFFICER</th>

                    <?php elseif ($report_type === 'next_steps_due'): ?>
                        <th>TARGET DUE DATE</th>
                        <th>CASE REF</th>
                        <th>PARTIES</th>
                        <th>REQUIRED ACTION</th>
                        <th>GROUP ENTITY</th>
                        <th>RESPONSIBLE OFFICER</th>

                    <?php elseif ($report_type === 'officer_workload'): ?>
                        <th>LEGAL OFFICER</th>
                        <th>EMAIL ADDRESS</th>
                        <th>TOTAL MATTERS</th>
                        <th>ACTIVE MATTERS</th>
                        <th>SETTLED MATTERS</th>

                    <?php elseif ($report_type === 'company_exposure'): ?>
                        <th>GROUP COMPANY ENTITY</th>
                        <th>TOTAL MATTERS</th>
                        <th>ACTIVE MATTERS</th>
                        <th>SETTLED MATTERS</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_data as $row): ?>
                    <tr>
                        <?php if ($report_type === 'master_case_register'): ?>
                            <td style="font-weight: 700; color: var(--primary-brand);"><?php echo htmlspecialchars($row['case_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['case_parties']); ?></td>
                            <td><?php echo htmlspecialchars($row['company_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['court_name'] ?? 'N/A'); ?></td>
                            <td><span class="status-pill status-active"><?php echo htmlspecialchars($row['case_status']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['next_hearing_date'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['officer_name'] ?? 'Unassigned'); ?></td>

                        <?php elseif ($report_type === 'upcoming_hearings'): ?>
                            <td style="font-weight: 700; color: #dc2626;"><?php echo htmlspecialchars($row['next_hearing_date']); ?></td>
                            <td style="font-weight: 700; color: var(--primary-brand);"><?php echo htmlspecialchars($row['case_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['case_parties']); ?></td>
                            <td><?php echo htmlspecialchars($row['company_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['court_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['officer_name'] ?? 'Unassigned'); ?></td>

                        <?php elseif ($report_type === 'next_steps_due'): ?>
                            <td style="font-weight: 700; color: #d97706;"><?php echo htmlspecialchars($row['next_step_date']); ?></td>
                            <td style="font-weight: 700; color: var(--primary-brand);"><?php echo htmlspecialchars($row['case_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['case_parties']); ?></td>
                            <td><?php echo htmlspecialchars($row['next_step_description']); ?></td>
                            <td><?php echo htmlspecialchars($row['company_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['officer_name'] ?? 'Unassigned'); ?></td>

                        <?php elseif ($report_type === 'officer_workload'): ?>
                            <td style="font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($row['officer_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><span class="status-pill status-total"><?php echo $row['total_cases']; ?></span></td>
                            <td><span class="status-pill status-active"><?php echo $row['active_cases']; ?></span></td>
                            <td><span class="status-pill status-settled"><?php echo $row['settled_cases']; ?></span></td>

                        <?php elseif ($report_type === 'company_exposure'): ?>
                            <td style="font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($row['company_name'] ?? 'Unassigned'); ?></td>
                            <td><span class="status-pill status-total"><?php echo $row['total_cases']; ?></span></td>
                            <td><span class="status-pill status-active"><?php echo $row['active_cases']; ?></span></td>
                            <td><span class="status-pill status-settled"><?php echo $row['settled_cases']; ?></span></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
// Dynamic Display Management for Date Ranges
function toggleDateInputs(reportType) {
    const dateRow = document.querySelector('.date-field-row');
    const companyFilter = document.querySelector('select[name="company_id"]').parentElement;
    const officerFilter = document.querySelector('select[name="officer_id"]').parentElement;

    // 1. Toggle Date Range Inputs (Only for Hearings & Next Steps)
    if (reportType === 'upcoming_hearings' || reportType === 'next_steps_due') {
        dateRow.style.display = 'flex';
    } else {
        dateRow.style.display = 'none';
    }

    // 2. Hide Company & Officer Filters for Summary Reports (Report 4 & 5)
    if (reportType === 'officer_workload' || reportType === 'company_exposure') {
        companyFilter.style.display = 'none';
        officerFilter.style.display = 'none';
    } else {
        companyFilter.style.display = 'block';
        officerFilter.style.display = 'block';
    }
}

// Run on page load to ensure state is set correctly
document.addEventListener("DOMContentLoaded", function() {
    const reportSelect = document.querySelector('select[name="report_type"]');
    if (reportSelect) {
        toggleDateInputs(reportSelect.value);
    }
});

// Client-Side CSV Exporter
function exportTableToCSV(tableID, filename) {
    let csv = [];
    let rows = document.querySelectorAll("#" + tableID + " tr");
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        csv.push(row.join(","));
    }
    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>