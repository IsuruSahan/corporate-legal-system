<?php
// Force database initialization
require_once __DIR__ . '/config/database.php';

echo "<h2>System Database Initialization Seeder</h2>";

try {
    // 1. Verify/Create the password column if you haven't already
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS password VARCHAR(255) NULL AFTER email");
    echo "✓ User schema validation complete.<br>";

    // 2. Clear out any previous conflicting dummy users
    $pdo->exec("DELETE FROM users WHERE email = 'akib.ahmad@benholdings.com'");

    // 3. Hash the initial secret key matching modern security constraints
    $default_password = 'admin123_secure';
    $hashed_password = password_hash($default_password, PASSWORD_BCRYPT);

    // 4. Inject the primary Administrative User profile
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, role, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'Akib Ahmad', 
        'akib.ahmad@benholdings.com', 
        'Admin', 
        $hashed_password
    ]);

    echo "<div style='background: #DCFCE7; color: #15803D; padding: 16px; border-radius: 8px; margin-top: 20px; font-family: sans-serif;'>";
    echo "<strong>Success! Master Admin user has been injected into the system database matrix.</strong><br><br>";
    echo "<strong>Email:</strong> akib.ahmad@benholdings.com<br>";
    echo "<strong>Password:</strong> admin123_secure<br><br>";
    echo "<em>Please delete this file (seed.php) immediately after running it for security purposes.</em>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='background: #FEE2E2; color: #B91C1C; padding: 16px; border-radius: 8px; margin-top: 20px; font-family: sans-serif;'>";
    echo "<strong>Database Operation Failure:</strong><br>" . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>