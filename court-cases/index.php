<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = "Court Cases & Litigation Ledger";
$breadcrumb = "LITIGATION / OVERVIEW";
require_once __DIR__ . '/../includes/header.php';

// Capture filtering parameter targets from standard URL query string vectors
$entity_filter = $_GET['entity'] ?? '';

// Build master database view joining necessary operational relations
$query = "SELECT cc.*, gc.company_name, cr.room_name, u.full_name as officer_name, cab.cabinet_location, a.title as linked_agreement_title
          FROM court_cases cc
          JOIN group_companies gc ON cc.group_company_id = gc.id
          JOIN court_rooms cr ON cc.court_id = cr.id
          JOIN users u ON cc.assigned_officer_id = u.id
          JOIN archive_cabinets cab ON cc.cabinet_id = cab.id
          LEFT JOIN agreements a ON cc.linked_agreement_id = a.id";

if (!empty($entity_filter)) {
    $query .= " WHERE cc.group_company_id = " . intval($entity_filter);
}
$query .= " ORDER BY cc.id DESC";
$records = $pdo->query($query)->fetchAll();

// Gather dropdown lookup definitions dynamically for workspace fields
$companies  = $pdo->query("SELECT * FROM group_companies ORDER BY company_name ASC")->fetchAll();
$officers   = $pdo->query("SELECT * FROM users WHERE role IN ('Admin', 'Staff') ORDER BY full_name ASC")->fetchAll();
$courts     = $pdo->query("SELECT * FROM court_rooms ORDER BY room_name ASC")->fetchAll();
$cabinets   = $pdo->query("SELECT * FROM archive_cabinets ORDER BY cabinet_location ASC")->fetchAll();
$agreements = $pdo->query("SELECT * FROM agreements ORDER BY title ASC")->fetchAll();
?>

<div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
    <?php if ($_SESSION['user_role'] !== 'Viewer'): ?>
        <a href="/corporate-legal-system/court-cases/add.php" class="btn btn-primary btn-tall" style="text-decoration: none; font-size: 13px; font-weight: 700;">
            + Log Litigation Profile
        </a>
    <?php endif; ?>
</div>

<div class="filtering-sub-bar" style="display: flex; gap: 16px; margin-bottom: 24px; align-items: center; background: var(--surface-white); padding: 16px; border-radius: 12px; border: 1px solid var(--border-color);">
    <div class="search-wrapper-input" style="flex: 1; max-width: 320px; margin-bottom: 0;">
        <input type="text" id="caseSearchInput" onkeyup="filterCasesMatrix()" placeholder="Search case metrics, description, party bounds...">
    </div>
    
    <select id="filterEntity" onchange="filterCasesMatrix()" class="dropdown-selector-filter" style="max-width: 200px; margin-bottom: 0;">
        <option value="">🏢 All Corporate Entities</option>
        <?php foreach ($companies as $c): ?>
            <option value="<?php echo htmlspecialchars($c['company_name']); ?>"><?php echo htmlspecialchars($c['company_name']); ?></option>
        <?php endforeach; ?>
    </select>

    <select id="filterCourt" onchange="filterCasesMatrix()" class="dropdown-selector-filter" style="max-width: 160px; margin-bottom: 0;">
        <option value="">All Courts</option>
        <?php foreach ($courts as $cr): ?>
            <option value="<?php echo htmlspecialchars($cr['room_name']); ?>"><?php echo htmlspecialchars($cr['room_name']); ?></option>
        <?php endforeach; ?>
    </select>

    <select id="filterOfficer" onchange="filterCasesMatrix()" class="dropdown-selector-filter" style="max-width: 180px; margin-bottom: 0;">
        <option value="">Assigned Officer</option>
        <?php foreach ($officers as $o): ?>
            <option value="<?php echo htmlspecialchars($o['full_name']); ?>"><?php echo htmlspecialchars($o['full_name']); ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="data-ledger-card">
    <table class="data-ledger-table" id="courtCasesLedgerGrid">
        <thead>
            <tr>
                <th>CASE TITLE / SUBSIDIARY ENTITY</th>
                <th>COURT ROOM</th>
                <th>ASSIGNED LEGAL TEAM</th>
                <th>CASE NUMBER</th>
                <th>NEXT HEARING MATTERS</th>
                <th>STATUS STAGE</th>
                <th style="text-align: center;">ATTACHMENT</th>
            </tr>
        </thead>
        <tbody id="data-body-court-cases">

        </tbody>
    </table>
</div>

<div id="drawerOverlay" class="side-drawer-overlay" onclick="closeDetailDrawer()">
    <div class="side-drawer-panel" onclick="event.stopPropagation()">
        <div class="drawer-header">
            <h2 id="drawerTitle" style="font-size: 16px; font-weight: 700; color: var(--text-dark);">Litigation Management Panel</h2>
            <span onclick="closeDetailDrawer()" style="cursor: pointer; font-weight: bold; color: var(--text-light); font-size: 18px;">✕</span>
        </div>

        <form id="drawerForm" class="view-mode" novalidate>
            <input type="hidden" name="action" value="update_court_case">
            <input type="hidden" name="id" id="edit_id">

            <div class="form-group-row">
                <label class="field-label-text">Case Title / Litigating Parties</label>
                <input type="text" name="case_parties" id="edit_parties" class="form-field-input" required readonly>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">Subsidiary Corporate Owner</label>
                    <select name="group_company_id" id="edit_company" class="form-field-select" required disabled>
                        <?php foreach ($companies as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label-text">Case File Reference Number</label>
                    <input type="text" name="case_number" id="edit_case_number" class="form-field-input" required readonly>
                </div>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">Court Jurisdiction</label>
                    <select name="court_id" id="edit_court" class="form-field-select" required disabled>
                        <?php foreach ($courts as $cr): ?><option value="<?php echo $cr['id']; ?>"><?php echo htmlspecialchars($cr['room_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label-text">Lead Legal Officer</label>
                    <select name="assigned_officer_id" id="edit_officer" class="form-field-select" required disabled>
                        <?php foreach ($officers as $u): ?><option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">Retained Counsel Name</label>
                    <input type="text" name="counsel_name" id="edit_counsel" class="form-field-input" required readonly>
                </div>
                <div>
                    <label class="field-label-text">Instructing Attorney</label>
                    <input type="text" name="instructing_attorney" id="edit_attorney" class="form-field-input" required readonly>
                </div>
            </div>

            <div class="form-group-row">
                <label class="field-label-text">Case Claims / Dispute Summary Details</label>
                <textarea name="case_description" id="edit_description" class="form-field-textarea" style="height: 80px;" required readonly></textarea>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">Next Hearing Date</label>
                    <input type="date" name="next_hearing_date" id="edit_hearing_date" class="form-field-input" readonly>
                </div>
                <div>
                    <label class="field-label-text">Next Step Due Date</label>
                    <input type="date" name="next_step_date" id="edit_step_date" class="form-field-input" readonly>
                </div>
            </div>

            <div class="form-group-row">
                <label class="field-label-text">Next Action Step Description Task</label>
                <input type="text" name="next_step_description" id="edit_step_desc" class="form-field-input" readonly>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">Physical Filing Cabinet Placement</label>
                    <select name="cabinet_id" id="edit_cabinet" class="form-field-select" required disabled>
                        <?php foreach ($cabinets as $cb): ?><option value="<?php echo $cb['id']; ?>"><?php echo htmlspecialchars($cb['cabinet_location']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label-text">Linked Vault Agreement Reference</label>
                    <select name="linked_agreement_id" id="edit_agreement" class="form-field-select" disabled>
                        <option value="">No Contract Linked...</option>
                        <?php foreach ($agreements as $ag): ?><option value="<?php echo $ag['id']; ?>"><?php echo htmlspecialchars($ag['title']); ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">Prior Approval (PA) Reference</label>
                    <input type="text" name="pa_ref_number" id="edit_pa" class="form-field-input" readonly>
                </div>
                <div>
                    <label class="field-label-text">Expense Claim Form (ECF) Reference</label>
                    <input type="text" name="ecf_ref_number" id="edit_ecf" class="form-field-input" readonly>
                </div>
            </div>

            <div class="form-group-row row-split" style="align-items: start;">
                <div>
                    <label class="field-label-text">Litigation Life Cycle Status</label>
                    <select name="case_status" id="edit_status" class="form-field-select" disabled>
                        <option value="Filing Stage">Filing Stage</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Settled">Settled</option>
                        <option value="Appealed">Appealed</option>
                    </select>
                </div>
                <div>
                    <label class="field-label-text">Internal Privileged Counsel Comments</label>
                    <textarea name="internal_comments" id="edit_comments" class="form-field-textarea tall-box" maxlength="500" readonly></textarea>
                </div>
            </div>

<div class="form-group-row">
    <label class="field-label-text">Current Attachments</label>
    <div id="drawerFilesContainer" class="mb-3"></div>
    
    <div id="addFilesContainer" style="display:none; margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;">
        <label class="field-label-text">Add More Files</label>
        <div class="file-dropzone-compact" onclick="document.getElementById('editFileInput').click()" 
             style="padding: 12px; border: 2px dashed var(--border-color); border-radius: 6px; text-align: center; cursor: pointer; color: var(--primary-brand);">
             <span class="browse-trigger-text">Browse & Upload PDF/DOCX</span>
        </div>
        <input type="file" name="court_case_files[]" id="editFileInput" multiple class="form-field-input" accept=".pdf,.docx" style="display:none;">
    </div>
</div>

            <div style="display: flex; justify-content: space-between; margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color);">
                <div>
                    <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                        <button type="button" class="btn" style="background: var(--bg-unlinked); color: var(--text-unlinked);" onclick="deleteActiveRecord()">Delete</button>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button type="button" class="btn btn-secondary" onclick="closeDetailDrawer()">Close</button>
                    
                    <?php if ($_SESSION['user_role'] !== 'Viewer'): ?>
                        <button type="button" class="btn btn-primary" id="drawerEditTriggerBtn" onclick="enableEditMode()">Edit Record</button>
                        <button type="submit" class="btn btn-primary" id="drawerSaveBtn" style="display: none;">Save Changes</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function filterCasesMatrix() {
    const searchVal  = document.getElementById('caseSearchInput').value.toLowerCase();
    const entityVal  = document.getElementById('filterEntity').value.toLowerCase();
    const courtVal   = document.getElementById('filterCourt').value.toLowerCase();
    const officerVal = document.getElementById('filterOfficer').value.toLowerCase();
    
    const rows = document.querySelectorAll('#courtCasesLedgerGrid tbody tr');
    
    rows.forEach(row => {
        if (row.classList.contains('no-records-row')) return;

        const caseTitleText = row.cells[0].querySelector('.primary-line').textContent.toLowerCase();
        const companyText   = row.cells[0].textContent.toLowerCase();
        const courtText     = row.cells[1].textContent.toLowerCase();
        const officerText   = row.cells[2].querySelector('.primary-line').textContent.toLowerCase();
        const caseNumText   = row.cells[3].textContent.toLowerCase();

        const matchesSearch = caseTitleText.includes(searchVal) || caseNumText.includes(searchVal) || companyText.includes(searchVal);
        const matchesEntity  = !entityVal  || companyText.includes(entityVal);
        const matchesCourt   = !courtVal   || courtText.includes(courtVal);
        const matchesOfficer = !officerVal || officerText.includes(officerVal);
        
        if (matchesSearch && matchesEntity && matchesCourt && matchesOfficer) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function openDetailDrawer(id) {
const fd = new FormData();
    fd.append('action', 'get_court_case');
    fd.append('id', id);

    fetch('/corporate-legal-system/config/router.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const d = res.data;
            
            // 1. Populate the hidden primary key tracking ID
            document.getElementById('edit_id').value = d.id;
            
            // 2. Map standard text field input selectors accurately
            document.getElementById('edit_parties').value = d.case_parties;
            document.getElementById('edit_case_number').value = d.case_number;
            document.getElementById('edit_counsel').value = d.counsel_name;
            document.getElementById('edit_attorney').value = d.instructing_attorney;
            document.getElementById('edit_description').value = d.case_description;
            document.getElementById('edit_step_desc').value = d.next_step_description;
            document.getElementById('edit_comments').value = d.internal_comments;
            
            // 3. Map calendar standard date properties elements safely
            document.getElementById('edit_hearing_date').value = d.next_hearing_date || "";
            document.getElementById('edit_step_date').value = d.next_step_date || "";
            document.getElementById('edit_pa').value = d.pa_ref_number || "";
            document.getElementById('edit_ecf').value = d.ecf_ref_number || "";

            // 4. Map dropdown relationship identifier keys correctly
            document.getElementById('edit_company').value = d.group_company_id;
            document.getElementById('edit_court').value = d.court_id;
            document.getElementById('edit_officer').value = d.assigned_officer_id;
            document.getElementById('edit_cabinet').value = d.cabinet_id;
            document.getElementById('edit_agreement').value = d.linked_agreement_id || "";
            document.getElementById('edit_status').value = d.case_status || "Filing Stage";

            // 5. ATTACHMENT HANDLING: Render files from JSON array
const fileContainer = document.getElementById('drawerFilesContainer');
            fileContainer.innerHTML = ''; 
            
            try {
                const files = JSON.parse(d.file_attachment_path || '[]');
                if (files.length > 0) {
                    files.forEach((path, index) => {
                        const fileName = path.split('/').pop();
                        fileContainer.innerHTML += `
                            <div class="d-flex align-items-center justify-content-between mb-2 p-2 border rounded">
                                <a href="..${path}" target="_blank" class="text-decoration-none">
                                    📄 ${fileName}
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="removeFile(${d.id}, ${index}, 'court_cases')">×</button>
                            </div>`;
                    });
                } else {
                    fileContainer.innerHTML = '<p class="text-muted small">No attachments found.</p>';
                }
            } catch (e) {
                console.error("Error parsing file paths:", e);
            }

            disableEditMode();
            document.getElementById('drawerOverlay').classList.add('active');
        }
    });
}


function enableEditMode() {
    // 1. Remove readonly/disabled from all inputs inside drawerForm
    const form = document.getElementById('drawerForm');
    form.classList.remove('view-mode');
    
    // Target inputs and textareas
    form.querySelectorAll('input:not([type="hidden"]), textarea').forEach(el => el.removeAttribute('readonly'));
    
    // Target selects
    form.querySelectorAll('select').forEach(el => el.removeAttribute('disabled'));
    
    // 2. Button toggles
    document.getElementById('drawerEditTriggerBtn').style.display = 'none';
    document.getElementById('drawerSaveBtn').style.display = 'inline-block';
    
    // 3. FORCE VISIBILITY OF UPLOAD SECTION
    const uploadSection = document.getElementById('addFilesContainer');
    if (uploadSection) {
        uploadSection.style.display = 'block';
    }
}

function disableEditMode() {
    const form = document.getElementById('drawerForm');
    form.classList.add('view-mode');
    
    // Re-apply readonly/disabled
    form.querySelectorAll('input:not([type="hidden"]), textarea').forEach(el => el.setAttribute('readonly', true));
    form.querySelectorAll('select').forEach(el => el.setAttribute('disabled', true));
    
    // Toggle buttons
    document.getElementById('drawerEditTriggerBtn').style.display = 'inline-block';
    const saveBtn = document.getElementById('drawerSaveBtn');
    if(saveBtn) saveBtn.style.display = 'none';
    
    // Hide upload section
    const uploadSection = document.getElementById('addFilesContainer');
    if (uploadSection) {
        uploadSection.style.display = 'none';
    }
}

function closeDetailDrawer() {
    document.getElementById('drawerOverlay').classList.remove('active');
}

document.getElementById('drawerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('drawerSaveBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    fetch('/corporate-legal-system/config/router.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            showSystemModal('Case Profiles Updated', data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showSystemModal('Error saving details', data.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Save Changes';
        }
    });
});

function deleteActiveRecord() {
    if(!confirm("Are you sure you want to permanently delete this litigation profile?")) return;
    
    const id = document.getElementById('edit_id').value;
    const fd = new FormData();
    fd.append('action', 'delete_court_case'); // Ensure this matches your router.php case
    fd.append('id', id);

    fetch('/corporate-legal-system/config/router.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            closeDetailDrawer();
            const row = document.getElementById(`case-row-${id}`);
            if(row) row.remove();
            showSystemModal('Record Purged', data.message, 'success');
        } else {
            showSystemModal('Action Blocked', data.message, 'error');
        }
    });
}

function removeFile(id, index, module) { // Ensure 'module' is passed here
    if(!confirm("Are you sure you want to remove this attachment?")) return;

    const fd = new FormData();
    fd.append('action', 'remove_court_file');
    fd.append('id', id);
    fd.append('file_index', index);
    fd.append('module', module); // CRITICAL: This links to the PHP $targetTable logic

    fetch('/corporate-legal-system/config/router.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        console.log("Server Response:", data);
        if(data.success) {
            openDetailDrawer(id); 
        } else {
            alert(data.message || "Error removing file.");
        }
    });
}

    // 1. Initialize pagination controls
    initPagination('court_cases', 'data-body-court-cases');

    // 2. Load the first page immediately on document ready
    document.addEventListener("DOMContentLoaded", function() {
        paginate('court_cases', 'data-body-court-cases', 1); 
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>