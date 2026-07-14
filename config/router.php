<?php
// 1. Initialize safe application session and state context
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

// Force clean JSON headers for async cross-communication
header('Content-Type: application/json');

// --- FIX: Define $action BEFORE using it ---
$action = $_POST['action'] ?? '';

// Guardrail: Enforce asynchronous POST request routing verification
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction vector method.']);
    exit;
}

$readOnlyActions = ['fetch_paginated_data', 'get_agreement', 'get_court_case'];

// 2. Now $action is defined, so this check will execute correctly
if ($_SESSION['user_role'] === 'Viewer' && !in_array($action, $readOnlyActions)) {
    echo json_encode(['success' => false, 'message' => 'Privilege Violation: Read-Only.']);
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
            $filePaths = [];
            
            // 1. Handle Multiple File Uploads
            if (!empty($_FILES['agreement_files']['name'][0])) {
                $uploadDir = '../uploads/agreements/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                // Loop through each uploaded file
                foreach ($_FILES['agreement_files']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['agreement_files']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileName = time() . '_' . basename($_FILES['agreement_files']['name'][$key]);
                        $targetPath = $uploadDir . $fileName;

                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $filePaths[] = '/uploads/agreements/' . $fileName;
                        }
                    }
                }
            }

            // 2. Encode the array of paths into a JSON string for storage
            $jsonFilePaths = !empty($filePaths) ? json_encode($filePaths) : null;

            // 3. Insert into database
            $stmt = $pdo->prepare("INSERT INTO agreements (
                group_company_id, title, party_b, assigned_officer_id, 
                category_id, physical_ref_no, cabinet_id, effective_date, 
                expiry_date, initial_status, internal_comments, 
                pa_ref_number, ecf_ref_number, file_attachment_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $_POST['group_company_id'], $_POST['title'], $_POST['party_b'], $_POST['assigned_officer_id'],
                $_POST['category_id'], $_POST['physical_ref_no'], $_POST['cabinet_id'], $_POST['effective_date'],
                $_POST['expiry_date'], $_POST['initial_status'], $_POST['internal_comments'], 
                $_POST['pa_ref_number'], $_POST['ecf_ref_number'], $jsonFilePaths
            ]);
            
            $newId = $pdo->lastInsertId();
            recordAuditLog($pdo, 'INSERT', 'Agreements', $newId, "Created agreement: " . $_POST['title']);

            echo json_encode(['success' => true, 'message' => 'Agreement and attachments successfully indexed.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
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
        $id = $_POST['id'];
        
        // 1. Get the CURRENT files from the database first
        $stmt = $pdo->prepare("SELECT file_attachment_path FROM agreements WHERE id = ?");
        $stmt->execute([$id]);
        $existingPaths = json_decode($stmt->fetchColumn(), true) ?: [];

        // 2. Handle new uploads and APPEND to the array
        if (!empty($_FILES['agreement_files']['name'][0])) {
            $uploadDir = '../uploads/agreements/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            foreach ($_FILES['agreement_files']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['agreement_files']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileName = time() . '_' . basename($_FILES['agreement_files']['name'][$key]);
                    if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                        $existingPaths[] = '/uploads/agreements/' . $fileName; // APPENDING
                    }
                }
            }
        }

        // 3. Update DB with the MERGED array
        $stmt = $pdo->prepare("UPDATE agreements SET title=?, group_company_id=?, party_b=?, category_id=?, 
                               assigned_officer_id=?, physical_ref_no=?, cabinet_id=?, effective_date=?, 
                               expiry_date=?, initial_status=?, internal_comments=?, pa_ref_number=?, 
                               ecf_ref_number=?, file_attachment_path=? WHERE id=?");
        
        $stmt->execute([
            $_POST['title'], $_POST['group_company_id'], $_POST['party_b'], $_POST['category_id'],
            $_POST['assigned_officer_id'], $_POST['physical_ref_no'], $_POST['cabinet_id'], 
            $_POST['effective_date'], $_POST['expiry_date'], $_POST['initial_status'], 
            $_POST['internal_comments'], $_POST['pa_ref_number'], $_POST['ecf_ref_number'], 
            json_encode($existingPaths), $id // Saving the full merged JSON
        ]);

        echo json_encode(['success' => true, 'message' => 'Files updated successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

case 'remove_file':
    try {
        $id = $_POST['id'];
        $indexToRemove = (int)$_POST['file_index'];

        // 1. Fetch current array
        $stmt = $pdo->prepare("SELECT file_attachment_path FROM agreements WHERE id = ?");
        $stmt->execute([$id]);
        $files = json_decode($stmt->fetchColumn(), true) ?: [];

        // 2. Validate and delete physical file
        if (isset($files[$indexToRemove])) {
            $physicalPath = '..' . $files[$indexToRemove];
            if (file_exists($physicalPath)) {
                unlink($physicalPath);
            }

            // 3. Remove from array and update DB
            array_splice($files, $indexToRemove, 1);
            $stmt = $pdo->prepare("UPDATE agreements SET file_attachment_path = ? WHERE id = ?");
            $stmt->execute([json_encode($files), $id]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'File not found.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
       MODULE B: LITIGATION WORKSPACE & DRAWER ACTIONS
       ========================================================================== */
case 'save_court_case':
    if (empty($_FILES)) {
        echo json_encode(['success' => false, 'message' => 'No files detected. Check form enctype.']);
        break;
    }
        try {
            $filePaths = [];
            
            // 1. Process multiple file uploads
            if (!empty($_FILES['court_case_files']['name'][0])) {
                $uploadDir = '../uploads/court_cases/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                foreach ($_FILES['court_case_files']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['court_case_files']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileName = time() . '_' . basename($_FILES['court_case_files']['name'][$key]);
                        if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                            $filePaths[] = '/uploads/court_cases/' . $fileName;
                        }
                    }
                }
            }

            // 2. Prepare JSON string
            $jsonFilePaths = !empty($filePaths) ? json_encode($filePaths) : null;

            // 3. Insert record with file_attachment_path
            $stmt = $pdo->prepare("INSERT INTO court_cases (
                group_company_id, case_number, case_parties, assigned_officer_id, 
                court_id, counsel_name, instructing_attorney, case_description, 
                next_hearing_date, next_step_date, next_step_description, 
                cabinet_id, linked_agreement_id, internal_comments, 
                pa_ref_number, ecf_ref_number, case_status, file_attachment_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $_POST['group_company_id'], $_POST['case_number'], $_POST['case_parties'], $_POST['assigned_officer_id'],
                $_POST['court_id'], $_POST['counsel_name'], $_POST['instructing_attorney'], $_POST['case_description'],
                !empty($_POST['next_hearing_date']) ? $_POST['next_hearing_date'] : null,
                !empty($_POST['next_step_date']) ? $_POST['next_step_date'] : null,
                $_POST['next_step_description'], $_POST['cabinet_id'], 
                !empty($_POST['linked_agreement_id']) ? $_POST['linked_agreement_id'] : null,
                $_POST['internal_comments'], $_POST['pa_ref_number'], $_POST['ecf_ref_number'],
                !empty($_POST['case_status']) ? $_POST['case_status'] : 'Filing Stage',
                $jsonFilePaths
            ]);

            $newId = $pdo->lastInsertId();
            recordAuditLog($pdo, 'INSERT', 'Court Cases', $newId, "Logged new litigation profile index file: " . $_POST['case_number']);

            echo json_encode(['success' => true, 'message' => 'Court case successfully committed with attachments.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database operation error: ' . $e->getMessage()]);
        }
        break;

    case 'get_court_case':
        try {
            $stmt = $pdo->prepare("SELECT * FROM court_cases WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($record) {
                echo json_encode(['success' => true, 'data' => $record]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Litigation profile record not found inside database directory.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database transaction failed: ' . $e->getMessage()]);
        }
        break;

case 'update_court_case':
        try {
            $id = $_POST['id'];

            // 1. Fetch current file paths from DB
            $stmt = $pdo->prepare("SELECT file_attachment_path FROM court_cases WHERE id = ?");
            $stmt->execute([$id]);
            $existingPaths = json_decode($stmt->fetchColumn(), true) ?: [];

            // 2. Handle new uploads (appended to existing array)
            if (!empty($_FILES['court_case_files']['name'][0])) {
                $uploadDir = '../uploads/court_cases/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                foreach ($_FILES['court_case_files']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['court_case_files']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileName = time() . '_' . basename($_FILES['court_case_files']['name'][$key]);
                        if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                            $existingPaths[] = '/uploads/court_cases/' . $fileName;
                        }
                    }
                }
            }

            // 3. Update the registry with metadata AND the new JSON file list
            $stmt = $pdo->prepare("UPDATE court_cases SET 
                case_parties = ?, group_company_id = ?, case_number = ?, court_id = ?, 
                assigned_officer_id = ?, counsel_name = ?, instructing_attorney = ?, 
                case_description = ?, next_hearing_date = ?, next_step_date = ?, 
                next_step_description = ?, cabinet_id = ?, linked_agreement_id = ?, 
                pa_ref_number = ?, ecf_ref_number = ?, case_status = ?, internal_comments = ?,
                file_attachment_path = ? 
                WHERE id = ?");
            
            $stmt->execute([
                $_POST['case_parties'], $_POST['group_company_id'], $_POST['case_number'], $_POST['court_id'],
                $_POST['assigned_officer_id'], $_POST['counsel_name'], $_POST['instructing_attorney'],
                $_POST['case_description'], 
                !empty($_POST['next_hearing_date']) ? $_POST['next_hearing_date'] : null,
                !empty($_POST['next_step_date']) ? $_POST['next_step_date'] : null,
                $_POST['next_step_description'], $_POST['cabinet_id'],
                !empty($_POST['linked_agreement_id']) ? $_POST['linked_agreement_id'] : null,
                $_POST['pa_ref_number'], $_POST['ecf_ref_number'], $_POST['case_status'],
                $_POST['internal_comments'], json_encode($existingPaths), $id
            ]);

            recordAuditLog($pdo, 'UPDATE', 'Court Cases', $id, "Revised case registry: " . $_POST['case_number']);

            echo json_encode(['success' => true, 'message' => 'Litigation records and attachments updated.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
        }
        break;

    case 'delete_court_case':
        try {
            $info = $pdo->prepare("SELECT case_number FROM court_cases WHERE id = ?");
            $info->execute([$_POST['id']]);
            $caseNum = $info->fetchColumn() ?: 'Unknown';

            $stmt = $pdo->prepare("DELETE FROM court_cases WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            
            recordAuditLog($pdo, 'DELETE', 'Court Cases', $_POST['id'], "Permanently cleared litigation profile index: " . $caseNum);

            echo json_encode(['success' => true, 'message' => 'Litigation profile permanently deleted from the registry.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Purge operation blocked: Active relational constraints exist.']);
        }
        break;

case 'remove_court_file':
    try {
        $id = $_POST['id'];
        $indexToRemove = (int)$_POST['file_index'];
        $targetTable = 'court_cases';

        $stmt = $pdo->prepare("SELECT file_attachment_path FROM {$targetTable} WHERE id = ?");
        $stmt->execute([$id]);
        $files = json_decode($stmt->fetchColumn(), true) ?: [];

        if (isset($files[$indexToRemove])) {
            $relativePath = ltrim($files[$indexToRemove], '/');
            $baseDir = realpath(__DIR__ . '/../');
            $fullPath = $baseDir . DIRECTORY_SEPARATOR . $relativePath;

            // 1. Delete physical file
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // 2. CRITICAL: Remove from array AND Update Database
            array_splice($files, $indexToRemove, 1);
            $stmt = $pdo->prepare("UPDATE {$targetTable} SET file_attachment_path = ? WHERE id = ?");
            $stmt->execute([json_encode($files), $id]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Index error.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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

case 'fetch_paginated_data':
    $table = $_POST['table'] ?? '';
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = 6; // Keep your limit
    $offset = ($page - 1) * $limit;

    if ($table === 'court_cases') {
        $query = "SELECT cc.*, gc.company_name, cr.room_name, u.full_name AS officer_name 
                  FROM court_cases cc
                  LEFT JOIN group_companies gc ON cc.group_company_id = gc.id
                  LEFT JOIN court_rooms cr ON cc.court_id = cr.id
                  LEFT JOIN users u ON cc.assigned_officer_id = u.id
                  ORDER BY cc.id DESC LIMIT ? OFFSET ?";
    } 
    elseif ($table === 'agreements') {
        $query = "SELECT a.*, gc.company_name, ac.category_name, u.full_name AS officer_name, cb.cabinet_location 
                  FROM agreements a
                  LEFT JOIN group_companies gc ON a.group_company_id = gc.id
                  LEFT JOIN agreement_categories ac ON a.category_id = ac.id
                  LEFT JOIN users u ON a.assigned_officer_id = u.id
                  LEFT JOIN archive_cabinets cb ON a.cabinet_id = cb.id
                  ORDER BY a.id DESC LIMIT ? OFFSET ?";
    } 
    else {
        // Fallback for tables without specific joins
        $query = "SELECT * FROM {$table} ORDER BY id DESC LIMIT ? OFFSET ?";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$limit, $offset]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    break;
}
?>