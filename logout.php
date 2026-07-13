<?php
// Initialize session state context safely before any data manipulation
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Establish the explicit intent flag BEFORE flushing profile tokens
$_SESSION['explicit_logout'] = true;

// 2. Clear out all operational permissions variables completely
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['user_role']);

// 3. Force-save the session state modifications right now
session_write_close();

// 4. Hard direct forward to the login entry gateway
header("Location: /corporate-legal-system/login.php");
exit;
?>