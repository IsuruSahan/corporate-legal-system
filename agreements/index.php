<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';


ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = "Agreements & Contracts Vault";
$breadcrumb = "AGREEMENTS / ALL RECORDS";
require_once __DIR__ . '/../includes/header.php';

// Fetch agreements ledger
$entity_filter = $_GET['entity'] ?? '';
$query = "SELECT a.*, c.company_name, cat.category_name, u.full_name as officer_name, cab.cabinet_location 
          FROM agreements a
          JOIN group_companies c ON a.group_company_id = c.id
          JOIN agreement_categories cat ON a.category_id = cat.id
          JOIN users u ON a.assigned_officer_id = u.id
          JOIN archive_cabinets cab ON a.cabinet_id = cab.id";

if (!empty($entity_filter)) {
    $query .= " WHERE a.group_company_id = " . intval($entity_filter);
}
$query .= " ORDER BY a.id DESC";
$records = $pdo->query($query)->fetchAll();

// Gather dependent collections for our inline editor drop-downs
$companies  = $pdo->query("SELECT * FROM group_companies ORDER BY company_name ASC")->fetchAll();
$officers   = $pdo->query("SELECT * FROM users WHERE role IN ('Admin', 'Staff') ORDER BY full_name ASC")->fetchAll();
$categories = $pdo->query("SELECT * FROM agreement_categories ORDER BY category_name ASC")->fetchAll();
$cabinets   = $pdo->query("SELECT * FROM archive_cabinets ORDER BY cabinet_location ASC")->fetchAll();
?>

<?php if ($_SESSION['user_role'] !== 'Viewer'): ?>
<div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
    <a href="add.php" class="btn btn-primary btn-tall">+ Add Agreement</a>
</div>
<?php endif; ?>

<form action="" method="GET" class="filtering-sub-bar" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 20px; background: #ffffff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
    <!-- 1. Text Search Input Field -->
    <div class="search-wrapper-input" style="flex: 1; min-width: 200px; margin-bottom: 0;">
        <input type="text" name="search" id="tableSearchInput" class="form-field-input" placeholder="Search title, party B..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="margin-bottom: 0;">
    </div>
    
    <!-- 2. Group Subsidiary Filter -->
    <div style="margin-bottom: 0;">
        <select name="entity" class="dropdown-selector-filter" onchange="this.form.submit()" style="margin-bottom: 0; background: #f8fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
            <option value="">🏢 All Entities</option>
            <?php foreach ($companies as $comp): ?>
                <option value="<?php echo $comp['id']; ?>" <?php echo (($_GET['entity'] ?? '') == $comp['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($comp['company_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- 3. Category Filter -->
    <div style="margin-bottom: 0;">
        <select name="category" class="dropdown-selector-filter" onchange="this.form.submit()" style="margin-bottom: 0; background: #f8fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
            <option value="">📁 All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo (($_GET['category'] ?? '') == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- 4. Legal Officer Filter -->
    <div style="margin-bottom: 0;">
        <select name="officer" class="dropdown-selector-filter" onchange="this.form.submit()" style="margin-bottom: 0; background: #f8fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
            <option value="">💼 All Officers</option>
            <?php foreach ($officers as $u): ?>
                <option value="<?php echo $u['id']; ?>" <?php echo (($_GET['officer'] ?? '') == $u['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['full_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- 5. Storage Cabinet Filter -->
    <div style="margin-bottom: 0;">
        <select name="cabinet" class="dropdown-selector-filter" onchange="this.form.submit()" style="margin-bottom: 0; background: #f8fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
            <option value="">🔒 All Vaults</option>
            <?php foreach ($cabinets as $cb): ?>
                <option value="<?php echo $cb['id']; ?>" <?php echo (($_GET['cabinet'] ?? '') == $cb['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cb['cabinet_location']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- 6. Lifecycle Status Filter -->
    <div style="margin-bottom: 0;">
        <select name="status" class="dropdown-selector-filter" onchange="this.form.submit()" style="margin-bottom: 0; background: #f8fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
            <option value="">📊 All Statuses</option>
            <option value="Active" <?php echo (($_GET['status'] ?? '') === 'Active') ? 'selected' : ''; ?>>Active</option>
            <option value="Pending" <?php echo (($_GET['status'] ?? '') === 'Pending') ? 'selected' : ''; ?>>Pending</option>
            <option value="Renewing" <?php echo (($_GET['status'] ?? '') === 'Renewing') ? 'selected' : ''; ?>>Renewing</option>
        </select>
    </div>

    <!-- 7. Clear Button -->
    <?php if (!empty($_GET['entity']) || !empty($_GET['category']) || !empty($_GET['officer']) || !empty($_GET['cabinet']) || !empty($_GET['status']) || !empty($_GET['search'])): ?>
        <a href="index.php" class="btn btn-secondary" style="padding: 8px 14px; font-size: 13px; text-decoration: none; border-radius: 6px; background: #f1f5f9; color: #475569; font-weight: 600;">Clear</a>
    <?php endif; ?>
</form>

<div class="data-ledger-card">
    <table class="data-ledger-table">
        <thead>
            <tr>
                <th>DOCUMENT TITLE / GROUP ENTITY / VENDOR</th>
                <th>CATEGORY</th>
                <th>RESPONSIBLE OFFICER</th>
                <th>PHYSICAL VAULT</th>
                <th>EXPIRY DATE</th>
                <th>STATUS</th>
                <th>FILE</th>
            </tr>
        </thead>
        <tbody id="data-body-agreements">

        </tbody>
    </table>
</div>

<div id="drawerOverlay" class="side-drawer-overlay" onclick="closeDetailDrawer()">
    <div class="side-drawer-panel" onclick="event.stopPropagation()">
        <div class="drawer-header">
            <h2 id="drawerTitle" style="font-size: 16px; font-weight: 700; color: var(--text-dark);">Agreement Workspace</h2>
            <span onclick="closeDetailDrawer()" style="cursor: pointer; font-weight: bold; color: var(--text-light); font-size: 18px;">✕</span>
        </div>

        <form id="drawerForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_agreement">
            <input type="hidden" name="id" id="edit_id">

            <div class="form-group-row">
                <label class="field-label-text">Agreement Title</label>
                <input type="text" name="title" id="edit_title" class="form-field-input" required readonly>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">Group Entity (Party A)</label>
                    <select name="group_company_id" id="edit_company" class="form-field-select" required disabled>
                        <?php foreach ($companies as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label-text">Party B</label>
                    <input type="text" name="party_b" id="edit_party_b" class="form-field-input" required readonly>
                </div>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">Category</label>
                    <select name="category_id" id="edit_category" class="form-field-select" required disabled>
                        <?php foreach ($categories as $cat): ?><option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label-text">Legal Officer</label>
                    <select name="assigned_officer_id" id="edit_officer" class="form-field-select" required disabled>
                        <?php foreach ($officers as $u): ?><option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">System Ref</label>
                    <input type="text" name="physical_ref_no" id="edit_ref" class="form-field-input" required readonly>
                </div>
                <div>
                    <label class="field-label-text">Storage Cabinet</label>
                    <select name="cabinet_id" id="edit_cabinet" class="form-field-select" required disabled>
                        <?php foreach ($cabinets as $cb): ?><option value="<?php echo $cb['id']; ?>"><?php echo htmlspecialchars($cb['cabinet_location']); ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">Effective Date</label>
                    <input type="date" name="effective_date" id="edit_eff_date" class="form-field-input" required readonly>
                </div>
                <div>
                    <label class="field-label-text">Expiry Date</label>
                    <input type="date" name="expiry_date" id="edit_exp_date" class="form-field-input" required readonly>
                </div>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">PA Code Reference</label>
                    <input type="text" name="pa_ref_number" id="edit_pa" class="form-field-input" readonly>
                </div>
                <div>
                    <label class="field-label-text">ECF Code Reference</label>
                    <input type="text" name="ecf_ref_number" id="edit_ecf" class="form-field-input" readonly>
                </div>
            </div>

            <div class="form-group-row row-split" style="align-items: start;">
                <div>
                    <label class="field-label-text">Lifecycle Status</label>
                    <select name="initial_status" id="edit_status" class="form-field-select" disabled>
                        <option value="Active">Active</option>
                        <option value="Pending">Pending</option>
                        <option value="Renewing">Renewing</option>
                    </select>
                </div>
                <div>
                    <label class="field-label-text">Comments</label>
                    <textarea name="internal_comments" id="edit_comments" class="form-field-textarea tall-box" maxlength="500" readonly></textarea>
                </div>
            </div>
<div class="form-group-row">
    <label class="field-label-text">Current Attachments</label>
    <div id="drawerFilesContainer" class="mb-3">
        </div>
    
    <div id="addFilesContainer" style="display:none; margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;">
        <label class="field-label-text">Add More Files</label>
        <div class="file-dropzone-compact" onclick="document.getElementById('editFileInput').click()" 
             style="padding: 12px; border: 2px dashed var(--border-color); border-radius: 6px; text-align: center; cursor: pointer; color: var(--primary-brand);">
             <span class="browse-trigger-text">Browse & Upload PDF/DOCX</span>
        </div>
        <input type="file" name="agreement_files[]" id="editFileInput" multiple class="form-field-input" accept=".pdf,.docx" style="display:none;">
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
function openDetailDrawer(id) {
    const fd = new FormData();
    fd.append('action', 'get_agreement');
    fd.append('id', id);

    fetch('/corporate-legal-system/config/router.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const d = res.data;
            // Populate standard metadata
            document.getElementById('edit_id').value = d.id;
            document.getElementById('edit_title').value = d.title;
            document.getElementById('edit_company').value = d.group_company_id;
            document.getElementById('edit_party_b').value = d.party_b;
            document.getElementById('edit_category').value = d.category_id;
            document.getElementById('edit_officer').value = d.assigned_officer_id;
            document.getElementById('edit_ref').value = d.physical_ref_no;
            document.getElementById('edit_cabinet').value = d.cabinet_id;
            document.getElementById('edit_eff_date').value = d.effective_date;
            document.getElementById('edit_exp_date').value = d.expiry_date;
            document.getElementById('edit_pa').value = d.pa_ref_number;
            document.getElementById('edit_ecf').value = d.ecf_ref_number;
            document.getElementById('edit_status').value = d.initial_status;
            document.getElementById('edit_comments').value = d.internal_comments;

            // --- FILE ATTACHMENT HANDLING ---
            const fileContainer = document.getElementById('drawerFilesContainer');
            fileContainer.innerHTML = ''; // Clear previous files
            
            try {
                // Parse JSON array of paths from database
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
                                        onclick="removeFile(${d.id}, ${index})">×</button>
                            </div>`;
                    });
                } else {
                    fileContainer.innerHTML = '<p class="text-muted small">No attachments found.</p>';
                }
            } catch (e) {
                console.error("Error parsing file paths:", e);
            }

            // Enforce default View Mode bounds upon initialization
            disableEditMode();
            document.getElementById('drawerOverlay').classList.add('active');
        }
    });
}

function enableEditMode() {
    // 1. Remove readonly/disabled from all inputs
    document.getElementById('drawerForm').classList.remove('view-mode');
    document.querySelectorAll('#drawerForm input, #drawerForm select, #drawerForm textarea').forEach(el => el.removeAttribute('readonly'));
    document.querySelectorAll('#drawerForm select').forEach(el => el.removeAttribute('disabled'));
    
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
    
    form.querySelectorAll('input, textarea').forEach(el => el.setAttribute('readonly', true));
    form.querySelectorAll('select').forEach(el => el.setAttribute('disabled', true));
    
    document.getElementById('drawerEditTriggerBtn').style.display = 'inline-block';
    const saveBtn = document.getElementById('drawerSaveBtn');
    if(saveBtn) saveBtn.style.display = 'none';
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
            showSystemModal('Revisions Saved', data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showSystemModal('Error saving details', data.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Save Changes';
        }
    });
});

function deleteActiveRecord() {
    if(!confirm("Are you sure you want to permanently purge this agreement from the secure archive?")) return;
    
    const id = document.getElementById('edit_id').value;
    const fd = new FormData();
    fd.append('action', 'delete_agreement');
    fd.append('id', id);

    fetch('/corporate-legal-system/config/router.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            closeDetailDrawer();
            document.getElementById(`agreement-row-${id}`).remove();
            showSystemModal('Record Purged', data.message, 'success');
        } else {
            showSystemModal('Action Blocked', data.message, 'error');
        }
    });
}
function removeFile(id, index) {
    if(!confirm("Are you sure you want to remove this attachment?")) return;

    const fd = new FormData();
    fd.append('action', 'remove_file');
    fd.append('id', id);
    fd.append('file_index', index);

    fetch('/corporate-legal-system/config/router.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            // This re-runs the function that fetches and renders the file list
            openDetailDrawer(id); 
        } else {
            alert(data.message || "Error removing file.");
        }
    });
}

    // 1. Initialize pagination controls
    // 1. Initialize pagination controls
// 1. Initialize your standard pagination controls
    initPagination('agreements', 'data-body-agreements');

    // 2. CRITICAL FIX: Intercept the page load and pass the active filter to the router
    document.addEventListener("DOMContentLoaded", function() {
        // Read the ?entity=X parameter from the browser address bar
        const urlParams = new URLSearchParams(window.location.search);
        const entityFilterId = urlParams.get('entity') || '';

        // Safely execute paginate with the filter parameters attached
        paginateAgreementsWithFilter('agreements', 'data-body-agreements', 1, entityFilterId);
    });

    // Custom helper function that replaces the default paginate call for this filtered ledger view
    function paginateAgreementsWithFilter(table, targetBodyId, page, entityId) {
        const fd = new FormData();
        fd.append('action', 'fetch_paginated_data');
        fd.append('table', table);
        fd.append('page', page);
        
        // Pass the company filter along if it exists in the URL
        if (entityId) {
            fd.append('group_company_id', entityId);
        }

        fetch('/corporate-legal-system/config/router.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                const body = document.getElementById(targetBodyId);
                body.innerHTML = ''; // Clear current rows
                
                if (res.data.length === 0) {
                    body.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 24px; color: var(--text-light);">No records match this company filter.</td></tr>';
                    return;
                }

                // Render out only the filtered rows dynamically
                res.data.forEach(row => {
                    body.innerHTML += `
                        <tr id="agreement-row-${row.id}" onclick="openDetailDrawer(${row.id})" style="cursor:pointer;">
                            <td>
                                <strong style="color: var(--text-dark); display:block;">${escapeHtml(row.title)}</strong>
                                <span style="font-size:11px; color: var(--text-light);">${escapeHtml(row.company_name)} | Party B: ${escapeHtml(row.party_b)}</span>
                            </td>
                            <td><span class="category-pill">${escapeHtml(row.category_name)}</span></td>
                            <td><span style="font-weight:600; color: var(--text-dark);">${escapeHtml(row.officer_name)}</span></td>
                            <td><span class="vault-location-text">${escapeHtml(row.cabinet_location)}</span></td>
                            <td><span style="font-family:monospace; font-weight:700;">${row.expiry_date}</span></td>
                            <td><span class="status-badge ${row.initial_status.toLowerCase()}">${row.initial_status}</span></td>
                            <td onclick="event.stopPropagation()">
                                <a href="/corporate-legal-system/agreements/download.php?id=${row.id}" class="action-link-icon">📥</a>
                            </td>
                        </tr>`;
                });
            }
        })
        .catch(err => console.error("Filter injection error:", err));
    }

    // Standard string helper to protect text output
    function escapeHtml(text) {
        if (!text) return 'None';
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
    // 2. Load the first page immediately on document ready
    document.addEventListener("DOMContentLoaded", function() {
        paginate('agreements', 'data-body-agreements', 1); 
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>