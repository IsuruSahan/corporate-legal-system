<?php
// 1. Initialize system session layer safely if not already established
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Fetch the central database engine connection handler
require_once __DIR__ . '/database.php';

/**
 * ==========================================================================
 * ACCESS CONTROL GATEKEEPER (PRODUCTION ENFORCEMENT)
 * ==========================================================================
 * Automatically routes all anonymous traffic down to the login gateway.
 */
$current_script = basename($_SERVER['SCRIPT_NAME']);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    if ($current_script !== 'login.php') {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
} else {
    if ($current_script === 'login.php') {
        header("Location: " . BASE_URL . "index.php");
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