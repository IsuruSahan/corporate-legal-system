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

<div class="filtering-sub-bar">
    <div class="search-wrapper-input">
        <input type="text" id="archiveSearchInput" placeholder="Search Cabinet ID, Shelf, Box code..." onkeyup="filterArchiveTable()">
    </div>
    <select id="entityFilter" class="dropdown-selector-filter" onchange="filterArchiveTable()">
        <option value="">🏢 All Entities</option>
        <?php foreach ($companies as $comp): ?>
            <option value="<?php echo htmlspecialchars($comp['company_name']); ?>"><?php echo htmlspecialchars($comp['company_name']); ?></option>
        <?php endforeach; ?>
    </select>
    <select id="cabinetFilter" class="dropdown-selector-filter" onchange="filterArchiveTable()">
        <option value="">🗄️ Filter Cabinet</option>
        <?php foreach ($cabinets as $cab): ?>
            <option value="<?php echo htmlspecialchars($cab['cabinet_location']); ?>"><?php echo htmlspecialchars($cab['cabinet_location']); ?></option>
        <?php endforeach; ?>
    </select>
</div>

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

// Initialize pagination controls targeting the custom MySQL Master View model
initPagination('physical_archives_master', 'data-body-archives');

// Execute the initial paginated load sequence immediately on document ready
document.addEventListener("DOMContentLoaded", function() {
    paginate('physical_archives_master', 'data-body-archives', 1); 
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>