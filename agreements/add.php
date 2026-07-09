<?php
// 1. Force the dynamic authentication and database connection layers to load first
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = "Add New Agreement";
$breadcrumb = "AGREEMENTS / CREATE NEW";

// 2. Load the structural layout header template
require_once __DIR__ . '/../includes/header.php';

// Fetch Dynamic Dropdown Seeding Records safely via database client instance
$companies = $pdo->query("SELECT * FROM group_companies ORDER BY company_name ASC")->fetchAll();
$officers = $pdo->query("SELECT * FROM users WHERE role IN ('Admin', 'Staff') ORDER BY full_name ASC")->fetchAll();
$categories = $pdo->query("SELECT * FROM agreement_categories ORDER BY category_name ASC")->fetchAll();
$cabinets = $pdo->query("SELECT * FROM archive_cabinets ORDER BY cabinet_location ASC")->fetchAll();
?>

<form id="agreementForm" class="form-grid-workspace" novalidate enctype="multipart/form-data">
    <input type="hidden" name="action" value="save_agreement">
    
    <div class="form-panel-card">
        <h2>Agreement Details</h2>

        <div class="form-group-row">
            <label class="field-label-text" for="group_company_id">Group Entity Ownership (Party A)</label>
            <select name="group_company_id" id="group_company_id" class="form-field-select" required>
                <option value="" disabled selected>Select Subsidiary Company...</option>
                <?php foreach ($companies as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group-row">
            <label class="field-label-text" for="title">Agreement Title</label>
            <input type="text" name="title" id="title" class="form-field-input" placeholder="e.g., Annual Office Lease Extension" required maxlength="255">
        </div>

        <div class="form-group-row">
            <label class="field-label-text" for="party_b">Other Party (Party B)</label>
            <input type="text" name="party_b" id="party_b" class="form-field-input" placeholder="Company Name, Vendor or Individual" required maxlength="255">
        </div>

        <div class="form-group-row">
            <label class="field-label-text" for="assigned_officer_id">Assigned Legal Officer</label>
            <select name="assigned_officer_id" id="assigned_officer_id" class="form-field-select" required>
                <option value="" disabled selected>Select Legal Officer...</option>
                <?php foreach ($officers as $u): ?>
                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group-row">
            <label class="field-label-text" for="category_id">Category (Dynamic)</label>
            <select name="category_id" id="category_id" class="form-field-select" required>
                <option value="" disabled selected>Select Category...</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group-row row-split">
            <div>
                <label class="field-label-text" for="physical_ref_no">Physical System Ref No</label>
                <input type="text" name="physical_ref_no" id="physical_ref_no" class="form-field-input" value="AGR-2026-" required maxlength="100">
            </div>
            <div>
                <label class="field-label-text" for="cabinet_id">Physical Storage Cabinet</label>
                <select name="cabinet_id" id="cabinet_id" class="form-field-select" required>
                    <option value="" disabled selected>Select Cabinet...</option>
                    <?php foreach ($cabinets as $cab): ?>
                        <option value="<?php echo $cab['id']; ?>"><?php echo htmlspecialchars($cab['cabinet_location']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group-row row-split">
            <div>
                <label class="field-label-text" for="effective_date">Effective Date</label>
                <input type="date" name="effective_date" id="effective_date" class="form-field-input" required>
            </div>
            <div>
                <label class="field-label-text" for="expiry_date">Expiry Date</label>
                <input type="date" name="expiry_date" id="expiry_date" class="form-field-input" required>
            </div>
        </div>

        <div class="form-group-row row-split" style="align-items: start;">
            <div>
                <label class="field-label-text" for="initial_status">Initial Status</label>
                <select name="initial_status" id="initial_status" class="form-field-select">
                    <option value="Active" selected>Active</option>
                    <option value="Pending">Pending</option>
                    <option value="Renewing">Renewing</option>
                </select>
            </div>
            <div>
                <label class="field-label-text" for="comments">Internal Comments</label>
                <textarea name="internal_comments" id="comments" class="form-field-textarea tall-box" maxlength="500" placeholder="Add confidential internal legal notes..."></textarea>
                <span class="character-budget-counter" id="comments_counter">0 / 500 characters</span>
            </div>
        </div>

        <div class="form-actions-footer">
            <a href="index.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" id="saveBtn">Index Agreement</button>
        </div>
    </div>

    <div class="form-workspace-right">
        <div class="form-panel-card" style="margin-bottom: 24px;">
            <h2>External Financial References</h2>
            <p class="form-sub-header-desc">Log cross-system pointers managed across procurement or expense arrays.</p>
            
            <div class="form-group-row">
                <label class="field-label-text" for="pa_ref_number">Prior Approval (PA) Ref Number</label>
                <input type="text" name="pa_ref_number" id="pa_ref_number" class="form-field-input" placeholder="Enter external system PA tracking code" maxlength="100">
            </div>

            <div class="form-group-row">
                <label class="field-label-text" for="ecf_ref_number">Expense Claim Form (ECF) Ref Number</label>
                <input type="text" name="ecf_ref_number" id="ecf_ref_number" class="form-field-input" placeholder="Enter external system ECF tracking code" maxlength="100">
            </div>
        </div>

        <div class="form-panel-card">
            <h2>Attachments</h2>
            <p class="form-sub-header-desc">Upload scanned master agreements, annexures, or supporting legal materials.</p>
            
            <div class="file-dropzone-area" id="dropzone" onclick="document.getElementById('fileInput').click()">
                <h3>Drag and drop agreement files here</h3>
                <p>Supports scanned PDF, DOCX up to 25MB</p>
                <div class="browse-trigger-text">or Browse Files...</div>
                <input type="file" name="agreement_file" id="fileInput" style="display: none;" accept=".pdf,.docx">
            </div>

            <div class="queued-registry-section">
                <div class="field-label-text">Queued Attachments</div>
                <div id="fileQueueContainer" style="margin-top: 8px; font-size: 13px; color: var(--text-muted); font-weight: 500;">
                    No files attached yet.
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// A. Live Characters Budget Constraints Dynamic Updater 
document.getElementById('comments').addEventListener('input', function() {
    document.getElementById('comments_counter').textContent = `${this.value.length} / 500 characters`;
});

// B. Drag & Drop Upload Zone State Manager Interactivity Engine
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const queueContainer = document.getElementById('fileQueueContainer');

fileInput.addEventListener('change', function() {
    if (this.files.length) {
        queueContainer.innerHTML = `<div class="queued-file-row"><span class="queued-file-name">📄 ${this.files[0].name}</span><span class="queued-file-size">${(this.files[0].size / (1024 * 1024)).toFixed(2)} MB</span></div>`;
    }
});

// C. AJAX Core Transaction Interception Submission Event Script Hook
document.getElementById('agreementForm').addEventListener('submit', function(e) {
    e.preventDefault(); 

    if (!this.checkValidity()) {
        this.reportValidity();
        return; 
    }

    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Indexing...';

    // Broadcast modern multi-part configuration payload using absolute gateway address
    fetch('/corporate-legal-system/config/router.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(response => {
        if (!response.ok) throw new Error('Platform Connection Interrupted.');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showSystemModal('Vault Entry Confirmed', data.message, 'success');
            setTimeout(() => { window.location.href = 'index.php'; }, 1200);
        } else {
            showSystemModal('Validation Failure', data.message, 'error');
            saveBtn.disabled = false;
            saveBtn.textContent = 'Index Agreement';
        }
    })
    .catch(error => {
        showSystemModal('Connection Fault', 'The central application engine couldn\'t preserve this record entry.', 'error');
        saveBtn.disabled = false;
        saveBtn.textContent = 'Index Agreement';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>