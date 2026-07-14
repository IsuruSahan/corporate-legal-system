<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

restrictToEditors(); // Safeguard: Block view-only role users

$page_title = "Record Payment Milestone";
$breadcrumb = "PAYMENTS / RECORD OBLIGATION";
$agreements = $pdo->query("SELECT id, title FROM agreements ORDER BY title ASC")->fetchAll();
$court_cases = $pdo->query("SELECT id, case_number FROM court_cases ORDER BY case_number ASC")->fetchAll();
require_once __DIR__ . '/../includes/header.php';

// Gather relations
$companies = $pdo->query("SELECT id, company_name FROM group_companies ORDER BY company_name ASC")->fetchAll();
?>

<div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px; align-items: start; margin-top: 24px;">
    
    <div class="form-panel-card" style="padding: 32px; background: #fff; border-radius: 16px; border: 1px solid var(--border-color);">
        <h2 style="font-size: 16px; font-weight: 700; color: var(--text-dark); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Payment Ledger Details</h2>
        
        <form id="newPaymentForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_payment">

            <div class="form-group-row">
                <label class="field-label-text">GROUP ENTITY CHARGED</label>
                <select name="group_company_id" class="form-field-select" required>
                    <option value="">Select Subsidiary Entity...</option>
                    <?php foreach ($companies as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company_name']); ?></option><?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-row">
                <label class="field-label-text">PAYMENT DESCRIPTION / MILESTONE TITLE</label>
                <input type="text" name="description" class="form-field-input" placeholder="e.g., Retainer Fee Milestone 02" required>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">CURRENCY</label>
                    <input type="text" name="currency" value="Rs." class="form-field-input">
                </div>
                <div>
                    <label class="field-label-text">AMOUNT</label>
                    <input type="number" step="0.01" name="amount" class="form-field-input" placeholder="0.00" required>
                </div>
            </div>

<div class="form-group-row row-split">
    <div>
        <label class="field-label-text">PAYMENT SOURCE TYPE</label>
        <select name="source_type" id="sourceType" class="form-field-select" onchange="toggleLinkedSource()" required>
            <option value="Agreement">Agreement</option>
            <option value="Court Case">Court Case</option>
        </select>
    </div>
    <div>
        <label class="field-label-text">LINKED REFERENCE</label>
        <select name="linked_source_id" id="linkedSourceDropdown" class="form-field-select" required>
            <!-- Options will be injected by JavaScript -->
        </select>
    </div>
</div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">PAYMENT DUE DATE</label>
                    <input type="date" name="due_date" class="form-field-input" required>
                </div>
                <div>
                    <label class="field-label-text">PAYMENT DATE</label>
                    <input type="date" name="payment_date" class="form-field-input">
                </div>
            </div>

            <div class="form-group-row">
                <label class="field-label-text">PAYMENT COMMENT</label>
                <textarea name="payment_comment" class="form-field-textarea" style="height: 80px;" placeholder="Add transaction context, settlement notes, or voucher comments here..." maxlength="500"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='/corporate-legal-system/payments/index.php'">Cancel</button>
                <button type="submit" id="submitFormBtn" class="btn btn-primary">Save Payment</button>
            </div>
        </form>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="form-panel-card" style="padding: 24px; background: #fff; border-radius: 16px; border: 1px solid var(--border-color);">
            <h2 style="font-size: 15px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">External Financial References</h2>
            <div style="font-size: 12px; color: var(--text-light); margin-bottom: 20px;">Log system cross-references managed in external procurement arrays.</div>
            
            <div class="form-group-row">
                <label class="field-label-text">PRIOR APPROVAL (PA) REF NUMBER</label>
                <input type="text" name="pa_ref_number" form="newPaymentForm" class="form-field-input">
            </div>
            
            <div class="form-group-row" style="margin-bottom: 0;">
                <label class="field-label-text">EXPENSE CLAIM FORM (ECF) REF NUMBER</label>
                <input type="text" name="ecf_ref_number" form="newPaymentForm" class="form-field-input">
            </div>
        </div>

        <div class="form-panel-card" style="padding: 24px; background: #fff; border-radius: 16px; border: 1px solid var(--border-color);">
            <h2 style="font-size: 15px; font-weight: 700; color: var(--text-dark); margin-bottom: 4px;">Supporting Attachments</h2>
            <div style="font-size: 12px; color: var(--text-light); margin-bottom: 20px;">Upload bank vouchers or payment receipts.</div>

            <div onclick="document.getElementById('fileInput').click()" style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 40px 20px; text-align: center; background: #f8fafc; cursor: pointer;">
                <div style="font-size: 14px; font-weight: 700; color: var(--text-dark);">Drag and drop receipts here</div>
                <div style="font-size: 12px; color: var(--text-light); margin-bottom: 12px;">Supports PDF, JPG, PNG up to 25MB</div>
                <div style="font-size: 13px; font-weight: 600; color: var(--primary-brand);">or Browse Files...</div>
            </div>
            <input type="file" name="payment_files[]" id="fileInput" form="newPaymentForm" multiple style="display: none;">
            
            <div style="margin-top: 18px; font-size: 11px; font-weight: 700; color: var(--text-light);">QUEUED ATTACHMENTS</div>
            <div id="fileQueueContainer" style="margin-top: 8px; font-size: 13px; color: var(--text-dark);">
                <span style="font-style: italic; color: var(--text-light);">No transaction receipts attached yet</span>
            </div>
        </div>
    </div>
</div>

<script>
const agreements = <?php echo json_encode($agreements); ?>;
const courtCases = <?php echo json_encode($court_cases); ?>;

function toggleLinkedSource() {
    const type = document.getElementById('sourceType').value;
    const dropdown = document.getElementById('linkedSourceDropdown');
    dropdown.innerHTML = ''; // Clear existing options

    const data = (type === 'Agreement') ? agreements : courtCases;
    const labelKey = (type === 'Agreement') ? 'title' : 'case_number';

    data.forEach(item => {
        let opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item[labelKey];
        dropdown.appendChild(opt);
    });
}

// Initialize dropdown on page load
document.addEventListener('DOMContentLoaded', toggleLinkedSource);

document.getElementById('newPaymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitFormBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    fetch('/corporate-legal-system/config/router.php', { method: 'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            showSystemModal('Payment Logged', data.message, 'success');
            setTimeout(() => window.location.href = '/corporate-legal-system/payments/index.php', 1000);
        } else {
            showSystemModal('Error', data.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Save Payment';
        }
    });
});

document.getElementById('fileInput').addEventListener('change', function() {
    const container = document.getElementById('fileQueueContainer');
    container.innerHTML = this.files.length > 0 ? Array.from(this.files).map(f => `<div>📄 ${f.name}</div>`).join('') : '<span style="font-style: italic; color: var(--text-light);">No transaction receipts attached yet</span>';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>