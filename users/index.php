<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Enforce strict administrative clearance gate
if ($_SESSION['user_role'] !== 'Admin') {
    echo '<script>window.location.href="' . BASE_URL . 'index.php";</script>';
    exit;
}

$page_title = "Users & System Configuration";
$breadcrumb = "SYSTEM MANAGEMENT / USERS & CONFIG";
require_once __DIR__ . '/../includes/header.php';

// Fetch lookups from schema parameters to render live cards
$identities = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
$courts = $pdo->query("SELECT * FROM court_rooms ORDER BY room_name ASC")->fetchAll();
$categories = $pdo->query("SELECT * FROM agreement_categories ORDER BY category_name ASC")->fetchAll();
$cabinets = $pdo->query("SELECT * FROM archive_cabinets ORDER BY cabinet_location ASC")->fetchAll();
$companies = $pdo->query("SELECT * FROM group_companies ORDER BY company_name ASC")->fetchAll();
$stages = $pdo->query("SELECT * FROM court_rooms ORDER BY id ASC")->fetchAll(); // Using standard court rooms mapping for preview or alter if staging exists
?>

<div class="form-grid-workspace">
    
    <div class="form-panel-card">
        <h2>User Profiles &amp; Access Controls</h2>
        
        <form id="asyncUserForm" style="background: #F4F7F6; padding: 20px; border-radius: 12px; margin-bottom: 24px;" novalidate>
            <input type="hidden" name="action" value="ajax_create_user">
            <div style="font-size: 12px; font-weight: 700; color: var(--primary-brand); margin-bottom: 14px; letter-spacing: 0.5px;">ADD NEW SYSTEM USER</div>
            
            <div class="form-group-row row-split" style="gap: 12px;">
                <input type="email" name="email" class="form-field-input" placeholder="user.email@benholdings.com" required style="height: 36px;">
                <select name="role" class="form-field-select" required style="height: 36px;">
                    <option value="" disabled selected>Assign Access Level ▾</option>
                    <option value="Admin">Admin</option>
                    <option value="Staff">Staff</option>
                    <option value="Viewer">Viewer</option>
                </select>
            </div>
            
            <div class="form-group-row" style="margin-bottom: 12px;">
                <input type="text" name="full_name" class="form-field-input" placeholder="Full Legal Name" required style="height: 36px;">
            </div>

            <div class="form-group-row" style="margin-bottom: 16px;">
                <input type="password" name="password" class="form-field-input" placeholder="Assign Login Password Token" required style="height: 36px;">
            </div>
            
            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" id="createUserBtn" style="height: 36px; padding: 0 24px; font-weight: 700; border-radius: 8px;">Create User</button>
            </div>
        </form>

        <div class="field-label-text" style="margin-bottom: 14px; color: var(--text-muted);">EXISTING IDENTITIES (<?php echo count($identities); ?>)</div>
        <div style="display: flex; flex-direction: column; gap: 12px;" id="identitiesContainer">
            <?php foreach ($identities as $user): ?>
                <div class="queued-file-row" style="background: #FFFFFF; border: 1px solid var(--border-color); padding: 16px; border-radius: 12px; margin: 0;" id="user-row-<?php echo $user['id']; ?>">
                    <div>
                        <div style="font-size: 14px; font-weight: 600; color: var(--text-dark);"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <div style="font-size: 12px; color: var(--text-light); font-weight: 500; margin-top: 2px;"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <span class="status-badge <?php echo ($user['role'] === 'Admin') ? 'linked' : (($user['role'] === 'Staff') ? 'progress' : 'renewing'); ?>" style="width: 70px; text-align: center;">
                            <?php echo htmlspecialchars($user['role']); ?>
                        </span>
                        <span onclick="executeDeleteUser(<?php echo $user['id']; ?>)" style="cursor: pointer; color: var(--text-unlinked); font-size: 14px; font-weight: bold;" title="Revoke Privilege Token">✕</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-panel-card">
        <h2>Dynamic Dropdown Configurations</h2>
        <p class="form-sub-header-desc">Manage data properties sourced globally into workflow select menus.</p>

        <div style="margin-bottom: 20px;">
            <div class="field-label-text">COURT ROOM PROPERTIES</div>
            <form class="async-config-form" style="display: flex; gap: 8px; margin-top: 6px; margin-bottom: 10px;">
                <input type="hidden" name="action" value="add_lookup">
                <input type="hidden" name="target_table" value="court_rooms">
                <input type="text" name="value" class="form-field-input" placeholder="Add new courtroom entry (e.g., Commercial High Court)..." required style="height: 34px;">
                <button type="submit" class="btn btn-primary" style="height: 34px; width: 42px; font-size: 16px; padding: 0;">+</button>
            </form>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                <?php foreach ($courts as $ct): ?>
                    <span style="display: inline-flex; align-items: center; gap: 6px; background: #F4F7F6; color: var(--text-muted); font-size: 11px; font-weight: 600; padding: 6px 12px; border-radius: 6px;">
                        <?php echo htmlspecialchars($ct['room_name']); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <hr style="border: none; border-top: 1px solid var(--line-row-border); margin: 16px 0;">

        <div style="margin-bottom: 20px;">
            <div class="field-label-text">AGREEMENT CATEGORIES</div>
            <form class="async-config-form" style="display: flex; gap: 8px; margin-top: 6px; margin-bottom: 10px;">
                <input type="hidden" name="action" value="add_lookup">
                <input type="hidden" name="target_table" value="agreement_categories">
                <input type="text" name="value" class="form-field-input" placeholder="Add new agreement structural class..." required style="height: 34px;">
                <button type="submit" class="btn btn-primary" style="height: 34px; width: 42px; font-size: 16px; padding: 0;">+</button>
            </form>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                <?php foreach ($categories as $cat): ?>
                    <span style="display: inline-flex; align-items: center; gap: 6px; background: #F4F7F6; color: var(--text-muted); font-size: 11px; font-weight: 600; padding: 6px 12px; border-radius: 6px;">
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <hr style="border: none; border-top: 1px solid var(--line-row-border); margin: 16px 0;">

        <div style="margin-bottom: 20px;">
            <div class="field-label-text">PHYSICAL ARCHIVE VAULT CABINETS</div>
            <form class="async-config-form" style="display: flex; gap: 8px; margin-top: 6px; margin-bottom: 10px;">
                <input type="hidden" name="action" value="add_lookup">
                <input type="hidden" name="target_table" value="archive_cabinets">
                <input type="text" name="value" class="form-field-input" placeholder="Add new cabinet reference location..." required style="height: 34px;">
                <button type="submit" class="btn btn-primary" style="height: 34px; width: 42px; font-size: 16px; padding: 0;">+</button>
            </form>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                <?php foreach ($cabinets as $cab): ?>
                    <span style="display: inline-flex; align-items: center; gap: 6px; background: #F4F7F6; color: var(--text-muted); font-size: 11px; font-weight: 600; padding: 6px 12px; border-radius: 6px;">
                        <?php echo htmlspecialchars($cab['cabinet_location']); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <hr style="border: none; border-top: 1px solid var(--line-row-border); margin: 16px 0;">

        <div style="margin-bottom: 10px;">
            <div class="field-label-text">GROUP COMPANIES &amp; MANAGED ENTITIES</div>
            <form class="async-config-form" style="display: flex; gap: 8px; margin-top: 6px; margin-bottom: 10px;">
                <input type="hidden" name="action" value="add_lookup">
                <input type="hidden" name="target_table" value="group_companies">
                <input type="text" name="value" class="form-field-input" placeholder="Add new group company identity..." required style="height: 34px;">
                <button type="submit" class="btn btn-primary" style="height: 34px; width: 42px; font-size: 16px; padding: 0;">+</button>
            </form>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                <?php foreach ($companies as $comp): ?>
                    <span style="display: inline-flex; align-items: center; gap: 6px; background: #F4F7F6; color: var(--text-muted); font-size: 11px; font-weight: 600; padding: 6px 12px; border-radius: 6px;">
                        <?php echo htmlspecialchars($comp['company_name']); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
// AJAX Implementation processing form mutations dynamically
document.getElementById('asyncUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if(!this.checkValidity()) { this.reportValidity(); return; }

    const btn = document.getElementById('createUserBtn');
    btn.disabled = true;
    btn.textContent = 'Creating...';

    const endpoint = (typeof BASE_URL !== 'undefined') ? BASE_URL + 'config/router.php' : '../config/router.php';

    fetch(endpoint, {
        method: 'POST',
        body: new FormData(this)
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            showSystemModal('Identity Provisioned', data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showSystemModal('Action Aborted', data.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Create User';
        }
    });
});


// Dynamic Attribute Seeding Actions Async Broadcaster
document.querySelectorAll('.async-config-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const endpoint = (typeof BASE_URL !== 'undefined') ? BASE_URL + 'config/router.php' : '../config/router.php';
        fetch(endpoint, {
            method: 'POST',
            body: new FormData(this)
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                window.location.reload();
            } else {
                showSystemModal('Error Adding Parameter', data.message, 'error');
            }
        });
    });
});

// Administrative User Drop Action Trigger System
function executeDeleteUser(userId) {
    if(!confirm("Are you sure you want to revoke system privileges for this identity?")) return;

    const fd = new FormData();
    fd.append('action', 'ajax_delete_user');
    fd.append('user_id', userId);

    const endpoint = (typeof BASE_URL !== 'undefined') ? BASE_URL + 'config/router.php' : '../config/router.php';

    fetch(endpoint, {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            document.getElementById(`user-row-${userId}`).remove();
            showSystemModal('Privileges Revoked', data.message, 'success');
        } else {
            showSystemModal('Action Blocked', data.message, 'error');
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>