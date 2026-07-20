<?php
// Initialize safe application session context
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. DYNAMIC BASE_URL AUTO-DETECTOR
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];

    // Environment-aware project root detection
    if ($host === 'localhost' || $host === '127.0.0.1') {
        // Localhost subfolder root
        define('BASE_URL', $protocol . $host . '/corporate-legal-system/');
    } else {
        // Production live domain root
        define('BASE_URL', $protocol . $host . '/');
    }
}

// 2. DATABASE CONFIGURATION
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Change this if your local MySQL has a root password
define('DB_NAME', 'benlegal_db');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database Connection Failure: " . $e->getMessage());
}
?>