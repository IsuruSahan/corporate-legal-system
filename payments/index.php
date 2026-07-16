<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
$page_title = "Payment Milestone Ledger";
$breadcrumb = "PAYMENTS / WORKSPACE";
require_once __DIR__ . '/../includes/header.php';



$companies = $pdo->query("SELECT * FROM group_companies ORDER BY company_name ASC")->fetchAll();
$agreements = $pdo->query("SELECT id, title FROM agreements ORDER BY title ASC")->fetchAll();
$court_cases = $pdo->query("SELECT id, case_number FROM court_cases ORDER BY case_number ASC")->fetchAll();
?>

<div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
    <?php if ($_SESSION['user_role'] !== 'Viewer'): ?>
        <button onclick="window.location.href='record.php'" class="btn btn-primary btn-tall" style="font-size: 13px; font-weight: 700;">+ Record Payment</button>
    <?php endif; ?>
</div>

<!-- Dynamic URL-Driven Filtering System Panel Structure -->
<form action="" method="GET" class="filtering-sub-bar" style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; align-items: center; background: var(--surface-white); padding: 16px; border-radius: 12px; border: 1px solid var(--border-color);">
    
    <!-- 1. Text Search Input (Description / Details) -->
    <div class="search-wrapper-input" style="flex: 1; min-width: 220px; margin-bottom: 0;">
        <input type="text" name="search" id="tableSearchInput" class="form-field-input" placeholder="Search by payment details, description..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="margin-bottom: 0;">
    </div>

    <!-- 2. PA REFERENCE Search Input -->
    <div class="search-wrapper-input" style="width: 150px; margin-bottom: 0;">
        <input type="text" name="pa_ref" id="paRefInput" class="form-field-input" placeholder="PA Ref No..." value="<?php echo htmlspecialchars($_GET['pa_ref'] ?? ''); ?>" style="margin-bottom: 0;">
    </div>

    <!-- 3. ECF REFERENCE Search Input -->
    <div class="search-wrapper-input" style="width: 150px; margin-bottom: 0;">
        <input type="text" name="ecf_ref" id="ecfRefInput" class="form-field-input" placeholder="ECF Ref No..." value="<?php echo htmlspecialchars($_GET['ecf_ref'] ?? ''); ?>" style="margin-bottom: 0;">
    </div>
    
    <!-- 4. CORPORATE ENTITY Dropdown Filter -->
    <div style="margin-bottom: 0;">
        <select name="entity" class="dropdown-selector-filter" onchange="this.form.submit()" style="margin-bottom: 0; font-size: 13px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px;">
            <option value="">🏢 Corporate Entity (All)</option>
            <?php foreach ($companies as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo (($_GET['entity'] ?? '') == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['company_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- 5. LINKED SOURCE TYPE Dropdown Filter (Agreement vs Court Case) -->
    <div style="margin-bottom: 0;">
        <select name="source_type" class="dropdown-selector-filter" onchange="this.form.submit()" style="margin-bottom: 0; font-size: 13px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px;">
            <option value="">🔗 Source Origin (All)</option>
            <option value="Agreement" <?php echo (($_GET['source_type'] ?? '') === 'Agreement') ? 'selected' : ''; ?>>Agreement Records</option>
            <option value="Court Case" <?php echo (($_GET['source_type'] ?? '') === 'Court Case') ? 'selected' : ''; ?>>Litigation / Court Cases</option>
        </select>
    </div>

    <!-- 6. Wipe Clear Controls Button -->
    <?php if (!empty($_GET['entity']) || !empty($_GET['source_type']) || !empty($_GET['pa_ref']) || !empty($_GET['ecf_ref']) || !empty($_GET['search'])): ?>
        <a href="index.php" class="btn btn-secondary" style="padding: 8px 14px; font-size: 13px; text-decoration: none; border-radius: 6px; background: #f1f5f9; color: #475569; font-weight: 600;">Clear</a>
    <?php endif; ?>
</form>

<div class="data-ledger-card">
    <table class="data-ledger-table" id="paymentsLedgerGrid">
        <thead>
            <tr><th>PAYMENT DESCRIPTION / SOURCE</th><th>PA REF</th><th>ECF REF</th><th>DUE DATE</th><th>AMOUNT</th><th>STATUS</th></tr>
        </thead>
        <tbody id="data-body-payments"></tbody>
    </table>
</div>

<div id="drawerOverlay" class="side-drawer-overlay" onclick="closeDetailDrawer()">
    <div class="side-drawer-panel" onclick="event.stopPropagation()">
        <div class="drawer-header">
            <h2 id="drawerTitle" style="font-size: 16px; font-weight: 700;">Payment Milestone Panel</h2>
            <span onclick="closeDetailDrawer()" style="cursor: pointer;">✕</span>
        </div>
        <form id="drawerForm" class="view-mode">
            <input type="hidden" name="action" value="update_payment">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group-row"><label>Payment Description</label><input type="text" name="description" id="edit_desc" class="form-field-input" readonly></div>
            <div class="form-group-row row-split">
                <div><label>Group Entity</label><select name="group_company_id" id="edit_company" class="form-field-select" disabled><?php foreach ($companies as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company_name']); ?></option><?php endforeach; ?></select></div>
                <div><label>Amount</label><input type="number" name="amount" id="edit_amount" class="form-field-input" readonly></div>
            </div>
            <div class="form-group-row row-split">
                <div><label>Due Date</label><input type="date" name="due_date" id="edit_due" class="form-field-input" readonly></div>
                <div><label>Payment Date</label><input type="date" name="payment_date" id="edit_pay_date" class="form-field-input" readonly></div>
            </div>
            <!-- Add these inside drawerForm -->
<div class="form-group-row row-split">
    <div><label class="field-label-text">Currency</label>
        <input type="text" name="currency" id="edit_currency" class="form-field-input" readonly>
    </div>
<div class="form-group-row">
    <label class="field-label-text">Source Type</label>
    <select name="source_type" id="edit_source_type" class="form-field-select" disabled>
        <option value="Agreement">Agreement</option>
        <option value="Court Case">Court Case</option>
    </select>
</div>
</div>

<div class="form-group-row row-split">
    <div>
        <label class="field-label-text">Linked Source Reference</label>
        <!-- This displays the Name/Title -->
        <input type="text" id="edit_linked_name" class="form-field-input" readonly>
        <!-- Keep the hidden ID for the update action -->
        <input type="hidden" name="linked_source_id" id="edit_linked_id">
    </div>
    <div>
        <label class="field-label-text">PA Ref</label>
        <input type="text" name="pa_ref_number" id="edit_pa" class="form-field-input" readonly>
    </div>
</div>

<div class="form-group-row">
    <label class="field-label-text">ECF Ref</label>
    <input type="text" name="ecf_ref_number" id="edit_ecf" class="form-field-input" readonly>
</div>
            <div class="form-group-row"><label>Comment</label><textarea name="payment_comment" id="edit_comment" class="form-field-textarea" readonly></textarea></div>
            <div class="form-group-row">
                <label>Attachments</label>
                <div id="drawerFilesContainer"></div>
                <div id="addFilesContainer" style="display:none; margin-top: 10px;"><input type="file" name="payment_files[]" multiple></div>
            </div>
            <div style="margin-top: 24px; display: flex; justify-content: space-between;">
                <button type="button" class="btn" style="background: var(--bg-unlinked); color: var(--text-unlinked);" onclick="deleteActiveRecord()">Delete</button>
                <div>
                    <button type="button" class="btn btn-secondary" onclick="closeDetailDrawer()">Close</button>
                    <button type="button" class="btn btn-primary" id="drawerEditBtn" onclick="enableEditMode()">Edit</button>
                    <button type="submit" class="btn btn-primary" id="drawerSaveBtn" style="display:none;">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const agreements = <?php echo json_encode($agreements); ?>;
    const courtCases = <?php echo json_encode($court_cases); ?>

   
// Initialize pagination engine control nodes on system load
    document.addEventListener("DOMContentLoaded", function() {
        initPagination('payments', 'data-body-payments');
        paginate('payments', 'data-body-payments', 1);

        // Bind interactive event capture properties to physical search input nodes
        const searchInput = document.getElementById('tableSearchInput');
        const paInput = document.getElementById('paRefInput');
        const ecfInput = document.getElementById('ecfRefInput');
        
        const submitFormOnEnter = function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit();
            }
        };

        if (searchInput) searchInput.addEventListener('keypress', submitFormOnEnter);
        if (paInput)      paInput.addEventListener('keypress', submitFormOnEnter);
        if (ecfInput)     ecfInput.addEventListener('keypress', submitFormOnEnter);
    });
function openDetailDrawer(id) {
    const fd = new FormData(); 
    fd.append('action', 'get_payment'); 
    fd.append('id', id);

    fetch('/corporate-legal-system/config/router.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const d = res.data;

            // 1. Map basic fields
            document.getElementById('edit_id').value = d.id;
            document.getElementById('edit_desc').value = d.description;
            document.getElementById('edit_amount').value = d.amount;
            document.getElementById('edit_due').value = d.due_date;
            document.getElementById('edit_pay_date').value = d.payment_date || '';
            document.getElementById('edit_currency').value = d.currency;
            document.getElementById('edit_source_type').value = d.source_type;
            document.getElementById('edit_linked_id').value = d.linked_source_id;
            document.getElementById('edit_pa').value = d.pa_ref_number || '';
            document.getElementById('edit_ecf').value = d.ecf_ref_number || '';
            document.getElementById('edit_company').value = d.group_company_id;
            document.getElementById('edit_comment').value = d.payment_comment || '';

            // 2. Resolve Linked Name logic (Must be inside the fetch block)
            const sourceId = d.linked_source_id;
            const sourceType = d.source_type;

            console.log("Looking up:", sourceType, "with ID:", sourceId);
            console.log("Available Agreements:", agreements);

            if (sourceType === 'Agreement') {
                const found = agreements.find(a => a.id == sourceId);
                document.getElementById('edit_linked_name').value = found ? found.title : 'N/A';
            } else if (sourceType === 'Court Case') {
                const found = courtCases.find(c => c.id == sourceId);
                document.getElementById('edit_linked_name').value = found ? found.case_number : 'N/A';
            } else {
                document.getElementById('edit_linked_name').value = 'N/A';
            }

            // 3. Render Files
            const fileContainer = document.getElementById('drawerFilesContainer'); 
            fileContainer.innerHTML = '';
            const files = JSON.parse(d.file_attachment_path || '[]');
            
            files.forEach((path, idx) => {
                fileContainer.innerHTML += `
                    <div class="mb-2">
                        📄 <a href="..${path}" target="_blank">${path.split('/').pop()}</a> 
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePaymentFile(${d.id}, ${idx})">×</button>
                    </div>`;
            });

            // 4. Reveal Drawer
            document.getElementById('drawerOverlay').classList.add('active');
        } else {
            alert(res.message || "Failed to load payment details.");
        }
    })
    .catch(err => console.error("Fetch error:", err));
}

    function enableEditMode() {
        document.getElementById('drawerForm').classList.remove('view-mode');
        document.getElementById('drawerEditBtn').style.display = 'none';
        document.getElementById('drawerSaveBtn').style.display = 'inline-block';
        document.getElementById('addFilesContainer').style.display = 'block';
        document.querySelectorAll('input, textarea, select').forEach(el => { el.removeAttribute('readonly'); el.removeAttribute('disabled'); });
    }

    function closeDetailDrawer() { document.getElementById('drawerOverlay').classList.remove('active'); }

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
            // This matches the court cases module call
            showSystemModal('Payment Milestones Updated', data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showSystemModal('Error saving details', data.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Save';
        }
    });
});

    function deleteActiveRecord() {
        if(!confirm("Are you sure?")) return;
        const fd = new FormData(); fd.append('action', 'delete_payment'); fd.append('id', document.getElementById('edit_id').value);
        fetch('/corporate-legal-system/config/router.php', { method: 'POST', body: fd }).then(() => window.location.reload());
    }

    function removePaymentFile(id, index) {
        const fd = new FormData(); fd.append('action', 'remove_payment_file'); fd.append('id', id); fd.append('file_index', index);
        fetch('/corporate-legal-system/config/router.php', { method: 'POST', body: fd }).then(() => openDetailDrawer(id));
    }
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>