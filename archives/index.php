<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$page_title = "Physical Archives & Vault Mapping";
$breadcrumb = "ARCHIVES / PHYSICAL LEDGER";
require_once __DIR__ . '/../includes/header.php';

// Consolidated SQL Query joining layout data and checking raw JSON string lengths
$query = "
    SELECT 
        a.id AS original_id,
        cab.cabinet_location AS physical_location,
        a.physical_ref_no AS system_ref_no,
        a.title AS primary_title,
        gc.company_name AS group_company,
        a.party_b AS structural_subtext,
        'Agreement' AS module_type,
        a.file_attachment_path AS raw_file_field
    FROM agreements a
    JOIN archive_cabinets cab ON a.cabinet_id = cab.id
    JOIN group_companies gc ON a.group_company_id = gc.id

    UNION ALL

    SELECT 
        cc.id AS original_id,
        cab.cabinet_location AS physical_location,
        cc.case_number AS system_ref_no,
        cc.case_parties AS primary_title,
        gc.company_name AS group_company,
        cc.counsel_name AS structural_subtext,
        'Court Case' AS module_type,
        cc.file_attachment_path AS raw_file_field
    FROM court_cases cc
    JOIN archive_cabinets cab ON cc.cabinet_id = cab.id
    JOIN group_companies gc ON cc.group_company_id = gc.id

    ORDER BY system_ref_no DESC
";

$records = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
$companies = $pdo->query("SELECT * FROM group_companies ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$cabinets = $pdo->query("SELECT * FROM archive_cabinets ORDER BY cabinet_location ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Dynamic URL-Driven Filtering System Panel Structure -->
<form action="" method="GET" class="filtering-sub-bar" style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; align-items: center; background: #ffffff; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
    
    <!-- 1. Text Search Input Field -->
    <div class="search-wrapper-input" style="flex: 1; min-width: 220px; margin-bottom: 0;">
        <input type="text" name="search" id="tableSearchInput" class="form-field-input" placeholder="Search Cabinet ID, Shelf, Box code..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="margin-bottom: 0;">
    </div>

    <!-- 2. Corporate Entity Dropdown Filter -->
    <div style="margin-bottom: 0;">
        <select name="entity" class="dropdown-selector-filter" onchange="this.form.submit()" style="margin-bottom: 0; font-size: 13px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px;">
            <option value="">🏢 All Entities</option>
            <?php foreach ($companies as $comp): ?>
                <option value="<?php echo htmlspecialchars($comp['company_name']); ?>" <?php echo (($_GET['entity'] ?? '') === $comp['company_name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($comp['company_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- 3. Cabinet Location Dropdown Filter -->
    <div style="margin-bottom: 0;">
        <select name="cabinet" class="dropdown-selector-filter" onchange="this.form.submit()" style="margin-bottom: 0; font-size: 13px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px;">
            <option value="">🗄️ Filter Cabinet</option>
            <?php foreach ($cabinets as $cab): ?>
                <option value="<?php echo htmlspecialchars($cab['cabinet_location']); ?>" <?php echo (($_GET['cabinet'] ?? '') === $cab['cabinet_location']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cab['cabinet_location']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- 4. Module Registry Origin Dropdown Filter -->
    <div style="margin-bottom: 0;">
        <select name="module_type" class="dropdown-selector-filter" onchange="this.form.submit()" style="margin-bottom: 0; font-size: 13px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px;">
            <option value="">📁 Source Origin (All)</option>
            <option value="Agreement" <?php echo (($_GET['module_type'] ?? '') === 'Agreement') ? 'selected' : ''; ?>>Agreement Records</option>
            <option value="Court Case" <?php echo (($_GET['module_type'] ?? '') === 'Court Case') ? 'selected' : ''; ?>>Litigation / Court Cases</option>
        </select>
    </div>

    <!-- 5. Digital Attachment Status Filter -->
    <div style="margin-bottom: 0;">
        <select name="file_check" class="dropdown-selector-filter" onchange="this.form.submit()" style="margin-bottom: 0; font-size: 13px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px;">
            <option value="">📄 Digital Files (All)</option>
            <option value="scanned" <?php echo (($_GET['file_check'] ?? '') === 'scanned') ? 'selected' : ''; ?>>✔ Digital Scanned</option>
            <option value="pending" <?php echo (($_GET['file_check'] ?? '') === 'pending') ? 'selected' : ''; ?>>⚠ Upload Pending</option>
        </select>
    </div>

    <!-- 6. Wipe Clear Controls Button -->
    <?php if (!empty($_GET['entity']) || !empty($_GET['cabinet']) || !empty($_GET['module_type']) || !empty($_GET['file_check']) || !empty($_GET['search'])): ?>
        <a href="index.php" class="btn btn-secondary" style="padding: 8px 14px; font-size: 13px; text-decoration: none; border-radius: 6px; background: #f1f5f9; color: #475569; font-weight: 600;">Clear</a>
    <?php endif; ?>
</form>

<div class="data-ledger-card">
    <table class="data-ledger-table" id="archiveMasterTable">
        <thead>
            <tr>
                <th>PHYSICAL LOCATION REFERENCE</th>
                <th>SYSTEM REF NO</th>
                <th>LINKED CONTENT / TITLE / GROUP COMPANY</th>
                <th>MODULE TYPE</th>
                <th>FILE CHECK</th>
            </tr>
        </thead>
        <tbody id="data-body-archives">
            
        </tbody>
    </table>
</div>

<script>
function filterArchiveTable() {
    const searchVal = document.getElementById('archiveSearchInput').value.toLowerCase();
    const entityVal = document.getElementById('entityFilter').value;
    const cabinetVal = document.getElementById('cabinetFilter').value;
    const rows = document.querySelectorAll('#archiveMasterTable tbody tr');

    rows.forEach(row => {
        if (row.cells.length < 5) return;
        
        const cabinetText = row.cells[0].textContent;
        const refText = row.cells[1].textContent.toLowerCase();
        const titleMetaText = row.cells[2].textContent.toLowerCase();
        const entityText = row.querySelector('.entity-text-marker').textContent;

        const matchesSearch = refText.includes(searchVal) || titleMetaText.includes(searchVal) || cabinetText.toLowerCase().includes(searchVal);
        const matchesEntity = !entityVal || entityText === entityVal;
        const matchesCabinet = !cabinetVal || cabinetText === cabinetVal;

        if (matchesSearch && matchesEntity && matchesCabinet) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}



// Execute the initial paginated load sequence immediately on document ready
document.addEventListener("DOMContentLoaded", function() {
    initPagination('physical_archives_master', 'data-body-archives');
    paginate('physical_archives_master', 'data-body-archives', 1);

    // Bind text field submission behavior directly onto the form element structure
    const searchInput = document.getElementById('tableSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit(); // Force basic query string refresh layout matching payment logic
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>