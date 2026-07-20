<?php
// 1. Load config FIRST to ensure BASE_URL is defined
require_once __DIR__ . '/config/database.php';

// 2. Initialize session state context safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Clear operational permissions variables (except explicit_logout flag if needed)
$_SESSION = array();

// Set the explicit logout flag AFTER clearing the array so it actually persists
$_SESSION['explicit_logout'] = true;

// 4. Wipe active tracking cookie out of browser memory
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 5. Destroy active server data channel execution
session_destroy();

// 6. Kill browser history cache blocks instantly
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 7. Hard direct forward to login entry gateway
header("Location: " . BASE_URL . "login.php");
exit;