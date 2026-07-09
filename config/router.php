<?php
// 1. Initialize safe application session and state context
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

// Force clean JSON headers for async cross-communication
header('Content-Type: application/json');

// Guardrail: Enforce asynchronous POST request routing verification
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction vector method.']);
    exit;
}

$action = $_POST['action'] ?? '';

// Check general operational privileges (Viewers can never modify state parameters)
if ($_SESSION['user_role'] === 'Viewer') {
    echo json_encode(['success' => false, 'message' => 'Privilege Violation: Your current auditing scope is strictly Read-Only.']);
    exit;
}

/**
 * HELPER UTILITY: TRANSACTION LOG COMMITTER (WITH ERROR TRACING)
 */
function recordAuditLog($pdo, $actionType, $module, $recordId, $description) {
    try {
        // Enclosing table columns explicitly ensures keywords don't cause breaks
        $stmt = $pdo->prepare("INSERT INTO audit_logs (`user_id`, `user_name`, `user_role`, `action_type`, `module_target`, `record_id`, `meta_description`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['user_name'],
            $_SESSION['user_role'],
            $actionType,
            $module,
            $recordId,
            $description
        ]);
    } catch (PDOException $e) {
        // ERROR TRACER: If a log fails to save, this forces the browser console to reveal why
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'Audit Log Failure: ' . $e->getMessage()]);
        exit;
    }
}

switch ($action) {
    /* ==========================================================================
       MODULE A: AGREEMENTS INDEXING & WORKSPACE OPERATIONS
       ========================================================================== */
    case 'save_agreement':
        try {
            $stmt = $pdo->prepare("INSERT INTO agreements (
                group_company_id, title, party_b, assigned_officer_id, 
                category_id, physical_ref_no, cabinet_id, effective_date, 
                expiry_date, initial_status, internal_comments, 
                pa_ref_number, ecf_ref_number
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $_POST['group_company_id'], $_POST['title'], $_POST['party_b'], $_POST['assigned_officer_id'],
                $_POST['category_id'], $_POST['physical_ref_no'], $_POST['cabinet_id'], $_POST['effective_date'],
                $_POST['expiry_date'], $_POST['initial_status'], $_POST['internal_comments'], 
                $_POST['pa_ref_number'], $_POST['ecf_ref_number']
            ]);
            
            $newId = $pdo->lastInsertId();
            recordAuditLog($pdo, 'INSERT', 'Agreements', $newId, "Created agreement entry: " . $_POST['title']);

            echo json_encode(['success' => true, 'message' => 'Agreement successfully indexed into the secure contract vault.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database constraint mismatch: ' . $e->getMessage()]);
        }
        break;

    case 'get_agreement':
        try {
            $stmt = $pdo->prepare("SELECT * FROM agreements WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $record = $stmt->fetch();
            
            if ($record) {
                echo json_encode(['success' => true, 'data' => $record]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Record not found.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'update_agreement':
        try {
            $stmt = $pdo->prepare("UPDATE agreements SET 
                group_company_id = ?, title = ?, party_b = ?, assigned_officer_id = ?, 
                category_id = ?, physical_ref_no = ?, cabinet_id = ?, effective_date = ?, 
                expiry_date = ?, initial_status = ?, internal_comments = ?, 
                pa_ref_number = ?, ecf_ref_number = ? WHERE id = ?");
            
            $stmt->execute([
                $_POST['group_company_id'], $_POST['title'], $_POST['party_b'], $_POST['assigned_officer_id'],
                $_POST['category_id'], $_POST['physical_ref_no'], $_POST['cabinet_id'], $_POST['effective_date'],
                $_POST['expiry_date'], $_POST['initial_status'], $_POST['internal_comments'], 
                $_POST['pa_ref_number'], $_POST['ecf_ref_number'], $_POST['id']
            ]);

            recordAuditLog($pdo, 'UPDATE', 'Agreements', $_POST['id'], "Revised core metadata parameters for record: " . $_POST['title']);

            echo json_encode(['success' => true, 'message' => 'Agreement revisions successfully saved.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Update operation failed: ' . $e->getMessage()]);
        }
        break;

    case 'delete_agreement':
        try {
            // Pull the context detail properties briefly to preserve readable audit metadata trails
            $info = $pdo->prepare("SELECT title FROM agreements WHERE id = ?");
            $info->execute([$_POST['id']]);
            $title = $info->fetchColumn() ?: 'Unknown Title';

            $stmt = $pdo->prepare("DELETE FROM agreements WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            
            recordAuditLog($pdo, 'DELETE', 'Agreements', $_POST['id'], "Permanently deleted agreement archival record item: " . $title);

            echo json_encode(['success' => true, 'message' => 'Agreement permanently deleted from the secure vault.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Deletion blocked: This record has active dependencies.']);
        }
        break;

    /* ==========================================================================
       MODULE B: LITIGATION MATTERS INDEXING ROUTINE
       ========================================================================== */
    case 'save_court_case':
        try {
            $stmt = $pdo->prepare("INSERT INTO court_cases (
                group_company_id, case_number, case_parties, assigned_officer_id, 
                court_id, counsel_name, instructing_attorney, case_description, 
                next_hearing_date, next_step_date, next_step_description, 
                cabinet_id, linked_agreement_id, internal_comments, 
                pa_ref_number, ecf_ref_number
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $_POST['group_company_id'], $_POST['case_number'], $_POST['case_parties'], $_POST['assigned_officer_id'],
                $_POST['court_id'], $_POST['counsel_name'], $_POST['instructing_attorney'], $_POST['case_description'],
                $_POST['next_hearing_date'], $_POST['next_step_date'], $_POST['next_step_description'],
                $_POST['cabinet_id'], !empty($_POST['linked_agreement_id']) ? $_POST['linked_agreement_id'] : null,
                $_POST['internal_comments'], $_POST['pa_ref_number'], $_POST['ecf_ref_number']
            ]);

            $newId = $pdo->lastInsertId();
            recordAuditLog($pdo, 'INSERT', 'Court Cases', $newId, "Logged new litigation profile index file: " . $_POST['case_number']);

            echo json_encode(['success' => true, 'message' => 'Court case file successfully committed into the litigation registry.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database operation error: ' . $e->getMessage()]);
        }
        break;

    /* ==========================================================================
       MODULE C: ADMIN CUSTOM USER CREATION (FRAME-5 DESIGN)
       ========================================================================== */
    case 'ajax_create_user':
        if ($_SESSION['user_role'] !== 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Access Denied: User creation requires administrative clearance bounds.']);
            exit;
        }

        $email     = trim($_POST['email'] ?? '');
        $role      = $_POST['role'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $password  = $_POST['password'] ?? '';

        if (empty($email) || empty($role) || empty($full_name) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'All identification form parameters are mandatory.']);
            exit;
        }

        try {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, role, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $role, $hashed]);

            $newId = $pdo->lastInsertId();
            recordAuditLog($pdo, 'INSERT', 'Users', $newId, "Provisioned new access profile identity for: $full_name ($role)");

            echo json_encode(['success' => true, 'message' => "Corporate profile generated for $full_name safely inside the master ledger."]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Identity Collision: Account parameters already match an existing user profile.']);
        }
        break;

    /* ==========================================================================
       MODULE D: ADMIN PROFILE REVOLVING / DELETION PROCESS
       ========================================================================== */
    case 'ajax_delete_user':
        if ($_SESSION['user_role'] !== 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Access Denied: Destruction token parameters require complete Admin privilege mapping.']);
            exit;
        }

        $id = intval($_POST['user_id'] ?? 0);
        if ($id === intval($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Identity Security Guardrail: You cannot purge your own active session token profile.']);
            exit;
        }

        try {
            $info = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $info->execute([$id]);
            $uName = $info->fetchColumn() ?: 'Unknown Account';

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            recordAuditLog($pdo, 'DELETE', 'Users', $id, "Revoked account identity tokens and access privileges for employee user: " . $uName);

            echo json_encode(['success' => true, 'message' => 'User system permissions successfully revoked from the workspace directory.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Purge Blocked: Profile has historical data properties linked to open legal actions or agreements.']);
        }
        break;

    /* ==========================================================================
       MODULE E: ADMINISTRATIVE LOOKUP MASTER DROPDOWNS INITIALIZATION
       ========================================================================== */
    case 'add_lookup':
        if ($_SESSION['user_role'] !== 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Privilege violation: Administrative access required.']);
            exit;
        }

        $table = $_POST['target_table'] ?? '';
        $value = trim($_POST['value'] ?? '');

        $allowed_tables = [
            'court_rooms'          => 'room_name', 
            'agreement_categories' => 'category_name', 
            'archive_cabinets'     => 'cabinet_location', 
            'group_companies'      => 'company_name'
        ];
        
        if (!array_key_exists($table, $allowed_tables) || empty($value)) {
            echo json_encode(['success' => false, 'message' => 'Invalid configuration tracking request properties.']);
            exit;
        }

        try {
            $column = $allowed_tables[$table];
            $stmt = $pdo->prepare("INSERT INTO {$table} ({$column}) VALUES (?)");
            $stmt->execute([$value]);
            
            recordAuditLog($pdo, 'INSERT', 'System Settings Dropdowns', 0, "Injected dynamic dropdown value attribute inside [{$table}] lookup matrix: {$value}");

            echo json_encode(['success' => true, 'message' => 'Dropdown mapping option indexed successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Parameter input already matches a registered attribute entry.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown core application execution transaction path routing.']);
        break;
}
?>