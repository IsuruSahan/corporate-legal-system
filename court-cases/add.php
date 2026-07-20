<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

restrictToEditors(); // Safeguard: Block view-only role users from structural input forms

$page_title = "Add New Court Case";
$breadcrumb = "COURT CASES / CREATE NEW";
require_once __DIR__ . '/../includes/header.php';

// Gather operational lookup relations for the drop-down element selections
$companies = $pdo->query("SELECT id, company_name FROM group_companies ORDER BY company_name ASC")->fetchAll();
$officers  = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetchAll();
$courts    = $pdo->query("SELECT id, room_name FROM court_rooms ORDER BY room_name ASC")->fetchAll();
$cabinets  = $pdo->query("SELECT id, cabinet_location FROM archive_cabinets ORDER BY cabinet_location ASC")->fetchAll();
$agreements = $pdo->query("SELECT id, title FROM agreements ORDER BY title ASC")->fetchAll();
?>

<div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px; align-items: start; margin-top: 24px;">
    
    <div class="form-panel-card" style="padding: 32px; background: #fff; border-radius: 16px; border: 1px solid var(--border-color);">
        <h2 style="font-size: 16px; font-weight: 700; color: var(--text-dark); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Case Information</h2>
        
        <form id="newCourtCaseForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_court_case">

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">GROUP ENTITY OWNERSHIP</label>
                    <select name="group_company_id" class="form-field-select" required>
                        <option value="">Select Subsidiary...</option>
                        <?php foreach ($companies as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label-text">CASE NUMBER</label>
                    <input type="text" name="case_number" class="form-field-input" placeholder="e.g., CASE-2026-005" required>
                </div>
            </div>

            <div class="form-group-row">
                <label class="field-label-text">CASE PARTIES (PLAINTIFFS / DEFENDANTS)</label>
                <input type="text" name="case_parties" class="form-field-input" placeholder="e.g., Ben Holdings Ltd vs. Logistics Provider Ltd" required>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">ASSIGNED LEGAL OFFICER</label>
                    <select name="assigned_officer_id" class="form-field-select" required>
                        <option value="">Assign Responsible Officer...</option>
                        <?php foreach ($officers as $u): ?><option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label-text">COURT</label>
                    <select name="court_id" class="form-field-select" required>
                        <option value="">Select Court...</option>
                        <?php foreach ($courts as $cr): ?><option value="<?php echo $cr['id']; ?>"><?php echo htmlspecialchars($cr['room_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">NAME OF THE COUNSEL</label>
                    <input type="text" name="counsel_name" class="form-field-input" placeholder="e.g., Akib Ahmad, Senior Counsel" required>
                </div>
                <div>
                    <label class="field-label-text">INSTRUCTING ATTORNEY</label>
                    <input type="text" name="instructing_attorney" class="form-field-input" placeholder="e.g., Nimmi Silva, Legal Officer" required>
                </div>
            </div>

            <div class="form-group-row">
                <label class="field-label-text">CASE DESCRIPTION (MAX 1000 CHARACTERS)</label>
                <textarea name="case_description" class="form-field-textarea" style="height: 100px;" placeholder="Provide summary of claim, key dispute points and legal arguments..." maxlength="1000" required></textarea>
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">NEXT HEARING DATE</label>
                    <input type="date" name="next_hearing_date" class="form-field-input">
                </div>
                <div>
                    <label class="field-label-text">NEXT STEP DATE</label>
                    <input type="date" name="next_step_date" class="form-field-input">
                </div>
            </div>

            <div class="form-group-row">
                <label class="field-label-text">NEXT STEP DESCRIPTION</label>
                <input type="text" name="next_step_description" class="form-field-input" placeholder="e.g., Filing of replication by Plaintiff">
            </div>

            <div class="form-group-row row-split">
                <div>
                    <label class="field-label-text">PHYSICAL STORAGE CABINET</label>
                    <select name="cabinet_id" class="form-field-select" required>
                        <option value="">Select Cabinet...</option>
                        <?php foreach ($cabinets as $cb): ?><option value="<?php echo $cb['id']; ?>"><?php echo htmlspecialchars($cb['cabinet_location']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label-text">LINKED AGREEMENT (OPTIONAL)</label>
                    <select name="linked_agreement_id" class="form-field-select">
                        <option value="">Search references...</option>
                        <?php foreach ($agreements as $ag): ?><option value="<?php echo $ag['id']; ?>"><?php echo htmlspecialchars($ag['title']); ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

<div class="form-group-row row-split" style="align-items: start;">
    <div>
        <label class="field-label-text">LITIGATION STAGE STATUS</label>
        <select name="case_status" class="form-field-select" required>
            <option value="Filing Stage" selected>Filing Stage</option>
            <option value="In Progress">In Progress</option>
            <option value="Settled">Settled</option>
            <option value="Appealed">Appealed</option>
        </select>
    </div>
    <div>
        <label class="field-label-text">INTERNAL COMMENTS</label>
        <textarea name="internal_comments" class="form-field-textarea" style="height: 80px;" placeholder="Log privileged counsel notes or internal strategic summaries..." maxlength="500"></textarea>
    </div>
</div>

            

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo BASE_URL; ?>court-cases/index.php'">Cancel</button>
                <button type="submit" id="submitFormBtn" class="btn btn-primary">Save Case Profile</button>
            </div>
        </form>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <div class="form-panel-card" style="padding: 24px; background: #fff; border-radius: 16px; border: 1px solid var(--border-color);">
            <h2 style="font-size: 15px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">External Financial References</h2>
            <div style="font-size: 12px; color: var(--text-light); margin-bottom: 20px;">Log system cross-references managed in external procurement arrays.</div>
            
            <div class="form-group-row">
                <label class="field-label-text">PRIOR APPROVAL (PA) REF NUMBER</label>
                <input type="text" name="pa_ref_number" form="newCourtCaseForm" class="form-field-input" placeholder="Enter external system PA tracking code">
            </div>
            
            <div class="form-group-row" style="margin-bottom: 0;">
                <label class="field-label-text">EXPENSE CLAIM FORM (ECF) REF NUMBER</label>
                <input type="text" name="ecf_ref_number" form="newCourtCaseForm" class="form-field-input" placeholder="Enter external system ECF tracking code">
            </div>
        </div>

<div class="form-panel-card" style="padding: 24px; background: #fff; border-radius: 16px; border: 1px solid var(--border-color);">
    <h2 style="font-size: 15px; font-weight: 700; color: var(--text-dark); margin-bottom: 4px;">Attachments</h2>
    <div style="font-size: 12px; color: var(--text-light); margin-bottom: 20px;">Upload court filings, case motions, or supporting legal materials.</div>

    <div onclick="document.getElementById('fileInput').click()" style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 40px 20px; text-align: center; background: #f8fafc; cursor: pointer;">
        <div style="font-size: 14px; font-weight: 700; color: var(--text-dark); margin-bottom: 4px;">Drag and drop case documents here</div>
        <div style="font-size: 12px; color: var(--text-light); margin-bottom: 12px;">Supports scanned PDF, DOCX up to 25MB</div>
        <div style="font-size: 13px; font-weight: 600; color: var(--primary-brand);">or Browse Files...</div>
    </div>
    
<input type="file" name="court_case_files[]" id="fileInput" form="newCourtCaseForm" multiple accept=".pdf,.docx" style="display: none;">
    <div style="margin-top: 18px; font-size: 11px; font-weight: 700; color: var(--text-light); letter-spacing: 0.5px;">QUEUED ATTACHMENTS</div>
    <div id="fileQueueContainer" style="margin-top: 8px; font-size: 13px; color: var(--text-dark);">
        <span style="font-style: italic; color: var(--text-light);">No files attached yet</span>
    </div>
</div>

    </div>
</div>

<script>
document.getElementById('newCourtCaseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitFormBtn');
    btn.disabled = true;
    btn.textContent = 'Saving Case Entry...';

    const endpoint = (typeof BASE_URL !== 'undefined') ? BASE_URL + 'config/router.php' : '../config/router.php';

    fetch(endpoint, {
        method: 'POST',
        body: new FormData(this)
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            showSystemModal('Case Profiles Indexed', data.message, 'success');
            const targetRedirect = (typeof BASE_URL !== 'undefined') ? BASE_URL + 'court-cases/index.php' : 'index.php';
            setTimeout(() => window.location.href = targetRedirect, 1000);
        } else {
            showSystemModal('Transaction Blocked', data.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Save Case Profile';
        }
    });
});
document.getElementById('fileInput').addEventListener('change', function() {
    const container = document.getElementById('fileQueueContainer');
    container.innerHTML = ''; // Clear existing
    
    if (this.files.length > 0) {
        let listHtml = '<ul style="list-style:none; padding:0;">';
        Array.from(this.files).forEach(file => {
            listHtml += `<li style="padding: 4px 0;">📄 ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</li>`;
        });
        listHtml += '</ul>';
        container.innerHTML = listHtml;
    } else {
        container.innerHTML = '<span style="font-style: italic; color: var(--text-light);">No files attached yet</span>';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>