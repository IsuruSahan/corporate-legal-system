<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

// Ensure request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

// ==========================================
// ACTION 1: MARK NOTIFICATIONS AS READ
// ==========================================
if ($action === 'mark_notifications_read') {
    $userRole = $_SESSION['user_role'] ?? 'Viewer';
    $userId   = $_SESSION['user_id'] ?? 0;

    try {
        $stmt = $pdo->prepare("
            UPDATE system_notifications 
            SET is_read = 1 
            WHERE (target_role = 'All' OR target_role = :role OR user_id = :uid)
        ");
        $stmt->execute(['role' => $userRole, 'uid' => $userId]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// ACTION 2: SAVE NOTIFICATION SETTINGS (ADMIN ONLY)
// ==========================================
if ($action === 'save_notification_settings') {

    // 1. Strict Admin Clearance Gate
    if (($_SESSION['user_role'] ?? '') !== 'Admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized clearance gate']);
        exit;
    }

    // 2. Sanitize & Validate Inputs
    $hearingNoticeDays  = intval($_POST['court_hearing_notice_days'] ?? 7);
    $nextStepNoticeDays = intval($_POST['court_next_step_notice_days'] ?? 5);

    if ($hearingNoticeDays < 1 || $hearingNoticeDays > 60 || $nextStepNoticeDays < 1 || $nextStepNoticeDays > 60) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid notice window between 1 and 60 days.']);
        exit;
    }

    // 3. Database Updates
    try {
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value) 
        VALUES (:key, :val_ins) 
        ON DUPLICATE KEY UPDATE setting_value = :val_upd
    ");
    
    // 1. Save Hearing Advance Notice Days
    $stmt->execute([
        ':key'     => 'court_hearing_notice_days',
        ':val_ins' => $hearingNoticeDays,
        ':val_upd' => $hearingNoticeDays
    ]);
    
    // 2. Save Next Step Advance Notice Days
    $stmt->execute([
        ':key'     => 'court_next_step_notice_days',
        ':val_ins' => $nextStepNoticeDays,
        ':val_upd' => $nextStepNoticeDays
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Notification lead times updated successfully!'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
    exit;
}

// Fallback for unexpected actions
echo json_encode(['success' => false, 'message' => 'Invalid action requested']);
exit;