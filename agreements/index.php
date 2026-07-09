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

<div class="filtering-sub-bar">
    <div class="search-wrapper-input"><input type="text" placeholder="Filter by contract party..."></div>
    <form action="" method="GET">
        <select name="entity" class="dropdown-selector-filter entity-select" onchange="this.form.submit()">
            <option value="">🏢 All Entities</option>
            <?php foreach ($companies as $comp): ?>
                <option value="<?php echo $comp['id']; ?>" <?php echo ($entity_filter == $comp['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($comp['company_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

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
        <tbody>
            <?php if (count($records) > 0): ?>
                <?php foreach ($records as $row): ?>
                    <tr id="agreement-row-<?php echo $row['id']; ?>">
                        <td class="title-meta-cell" style="cursor: pointer;" onclick="openDetailDrawer(<?php echo $row['id']; ?>)">
                            <div class="primary-line" style="color: var(--primary-brand); font-weight: 700; text-decoration: underline;"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="secondary-sub"><?php echo htmlspecialchars($row['company_name']); ?> <span>| Party B: <?php echo htmlspecialchars($row['party_b']); ?></span></div>
                        </td>
                        <td><span class="text-data-regular"><?php echo htmlspecialchars($row['category_name']); ?></span></td>
                        <td><span class="text-data-regular"><?php echo htmlspecialchars($row['officer_name']); ?></span></td>
                        <td><span class="text-data-bold"><?php echo htmlspecialchars($row['cabinet_location']); ?></span></td>
                        <td><span class="text-data-regular"><?php echo date('M d, Y', strtotime($row['expiry_date'])); ?></span></td>
                        <td><span class="status-badge <?php echo strtolower($row['initial_status']); ?>"><?php echo htmlspecialchars($row['initial_status']); ?></span></td>
                        <td><span class="file-link-trigger none">📁 None</span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align: center; padding: 100px; color: var(--text-light);">No records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="drawerOverlay" class="side-drawer-overlay" onclick="closeDetailDrawer()">
    <div class="side-drawer-panel" onclick="event.stopPropagation()">
        <div class="drawer-header">
            <h2 id="drawerTitle" style="font-size: 16px; font-weight: 700; color: var(--text-dark);">Agreement Workspace</h2>
            <span onclick="closeDetailDrawer()" style="cursor: pointer; font-weight: bold; color: var(--text-light); font-size: 18px;">✕</span>
        </div>

        <form id="drawerForm" class="view-mode" novalidate>
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

            // Enforce default View Mode bounds upon initialization
            disableEditMode();
            document.getElementById('drawerOverlay').classList.add('active');
        }
    });
}

function enableEditMode() {
    const form = document.getElementById('drawerForm');
    form.classList.remove('view-mode');
    
    // Remove locked element attribute states
    form.querySelectorAll('input, textarea').forEach(el => el.removeAttribute('readonly'));
    form.querySelectorAll('select').forEach(el => el.removeAttribute('disabled'));
    
    // Toggle Button View State Links
    document.getElementById('drawerEditTriggerBtn').style.display = 'none';
    document.getElementById('drawerSaveBtn').style.display = 'inline-block';
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>