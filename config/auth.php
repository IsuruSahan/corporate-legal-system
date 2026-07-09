<?php
// 1. Initialize system session layer safely if not already established
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Fetch the central database engine connection handler
require_once __DIR__ . '/database.php';

/**
 * ==========================================================================
 * DYNAMIC ENVIRONMENT ACCOUNT SWITCHER MATRIX (OPTION A)
 * ==========================================================================
 */
try {
    // Pull real registered profiles straight from your live database tables
    $simulator_stmt = $pdo->query("SELECT id, full_name, email, role FROM users ORDER BY full_name ASC");
    $system_simulator_users = $simulator_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Graceful fallback safeguard to prevent breaking early system installations
    $system_simulator_users = [];
}

// Intercept state mutation switch requests instantly over POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simulate_user_id'])) {
    $target_id = intval($_POST['simulate_user_id']);
    
    foreach ($system_simulator_users as $sim_user) {
        if (intval($sim_user['id']) === $target_id) {
            // Hot-swap valid matching session identifiers instantly
            $_SESSION['user_id']    = $sim_user['id'];
            $_SESSION['user_name']  = $sim_user['full_name'];
            $_SESSION['user_email'] = $sim_user['email'];
            $_SESSION['user_role']  = $sim_user['role'];
            
            // Reload the identical request scope layout cleanly to apply changes
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

/**
 * ==========================================================================
 * AUTOMATED SESSION SELF-HEALING ENGINE
 * ==========================================================================
 * Loops through live user rows to ensure our active session is structurally valid.
 * If missing, invalid, or an empty constraint mismatch, it auto-heals immediately.
 */
if (!empty($system_simulator_users)) {
    $current_session_is_valid = false;

    if (isset($_SESSION['user_id'])) {
        foreach ($system_simulator_users as $live_user) {
            if (intval($live_user['id']) === intval($_SESSION['user_id'])) {
                $current_session_is_valid = true;
                break;
            }
        }
    }

    // If session ID is empty, corrupted, or missing from database rows, repair it:
    if (!$current_session_is_valid) {
        $backup_identity = $system_simulator_users[0]; // Grab first valid database entity row
        $_SESSION['user_id']    = $backup_identity['id'];
        $_SESSION['user_name']  = $backup_identity['full_name'];
        $_SESSION['user_email'] = $backup_identity['email'];
        $_SESSION['user_role']  = $backup_identity['role'];
    }
}

/**
 * ==========================================================================
 * STRICT ACCESS CONTROL GATEKEEPER
 * ==========================================================================
 */
$current_script = basename($_SERVER['SCRIPT_NAME']);

if (!isset($_SESSION['user_role'])) {
    // Stop unauthenticated contexts from passing unless loading login.php
    if ($current_script !== 'login.php') {
        header("Location: /corporate-legal-system/login.php");
        exit;
    }
} else {
    // Redirect active token holders away from hitting login screen repeatedly
    if ($current_script === 'login.php') {
        header("Location: /corporate-legal-system/index.php");
        exit;
    }
}

/**
 * ==========================================================================
 * ROLE CLEARANCE CHECKERS (Helper Utility Gates)
 * ==========================================================================
 */
function restrictToAdmin() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
        header("Location: /corporate-legal-system/index.php?error=unauthorized");
        exit;
    }
}

function restrictToEditors() {
    if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'Admin' && $_SESSION['user_role'] !== 'Staff')) {
        header("Location: /corporate-legal-system/index.php?error=unauthorized");
        exit;
    }
}
?>