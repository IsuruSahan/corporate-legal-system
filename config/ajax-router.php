<?php
require_once __DIR__ . '/database.php';

// Force clean JSON headers
header('Content-Type: application/json');

// Guardrail: Enforce asynchronous request verification
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction vector.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'save_agreement':
        if ($_SESSION['user_role'] === 'Viewer') {
            echo json_encode(['success' => false, 'message' => 'Privilege violation: Read-only scope cannot alter state.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO agreements (group_company_id, title, party_b, assigned_officer_id, category_id, physical_ref_no, cabinet_id, effective_date, expiry_date, initial_status, internal_comments, pa_ref_number, ecf_ref_number) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $_POST['group_company_id'], $_POST['title'], $_POST['party_b'], $_POST['assigned_officer_id'],
                $_POST['category_id'], $_POST['physical_ref_no'], $_POST['cabinet_id'], $_POST['effective_date'],
                $_POST['expiry_date'], $_POST['initial_status'], $_POST['internal_comments'], 
                $_POST['pa_ref_number'], $_POST['ecf_ref_number']
            ]);

            echo json_encode(['success' => true, 'message' => 'Agreement successfully indexed into the secure vault.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database constraint violation: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown application action routing.']);
        break;
}