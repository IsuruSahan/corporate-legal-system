<?php
// Initialize safe application session context
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. DYNAMIC BASE_URL AUTO-DETECTOR
// Automatically computes relative web paths whether running on localhost or a production server
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $dirName = dirname($scriptName);
    
    // Normalize path to prevent trailing slashes
    $projectDir = ($dirName === '/' || $dirName === '\\') ? '' : $dirName;
    
    // Ensure we capture the base web folder correctly if inside subdirectories
    if (strpos($projectDir, '/config') !== false) {
        $projectDir = str_replace('/config', '', $projectDir);
    }
    
    define('BASE_URL', $protocol . $host . $projectDir . '/');
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

// 3. ROLE ACCESSIBILITY SIMULATOR CONFIG
// Defaulting to Legal Admin (Akib Ahmad) context as seen in master mocks
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Akib Ahmad';
    $_SESSION['user_email'] = 'akib.ahmad@benholdings.com';
    $_SESSION['user_role'] = 'Admin'; 
}
?>