<?php
// 1. Initialize session and system connection dependencies
require_once __DIR__ . '/config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * REDIRECTION BYPASS SECURITY GUARD
 * Only forward to index.php if the user has an active, authenticated role 
 * AND they have not explicitly chosen to terminate their session.
 */
if (isset($_SESSION['user_role']) && !isset($_SESSION['explicit_logout'])) {
    header("Location: index.php");
    exit;
}

$error = '';

// 2. Intercept Authentication Request Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            // Match email record directly inside active users database directory 
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Validate match using modern standard crypt hashes
            if ($user && password_verify($password, $user['password'])) {
                
                // CRITICAL: Clear the explicit logout flag since they are intentionally logging back in
                if (isset($_SESSION['explicit_logout'])) {
                    unset($_SESSION['explicit_logout']);
                }

                // Populate structural context permissions variables
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];

                header("Location: index.php");
                exit;
            } else {
                $error = "Access Denied: Invalid corporate identifier or secret token credentials.";
            }
        } catch (PDOException $e) {
            $error = "System Engine Exception: Unable to verify credentials at this moment.";
        }
    } else {
        $error = "Please provide both your corporate email and security password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BenLegal Access Portal</title>
    <link rel="stylesheet" href="/corporate-legal-system/assets/css/style.css">
    <style>
        /* Standalone overlay workspace layout alignment rules */
        .login-frame-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: var(--bg-system);
            width: 100%;
        }
        .login-card-shield {
            background: var(--surface-white);
            width: 400px;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>

<div class="login-frame-container">
    <div class="login-card-shield">
        <div style="font-size: 26px; font-weight: 700; color: var(--primary-brand); margin-bottom: 6px; text-align: center;">BenLegal</div>
        <div style="font-size: 13px; color: var(--text-muted); font-weight: 500; text-align: center; margin-bottom: 32px;">Corporate Records Security Gateway</div>

        <?php if (!empty($error)): ?>
            <div style="background: var(--bg-unlinked); color: var(--text-unlinked); padding: 12px; border-radius: 8px; font-size: 12px; font-weight: 600; margin-bottom: 20px; line-height: 1.4; text-align: left;">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" autocomplete="off">
            <div class="form-group-row">
                <label class="field-label-text">Corporate Email ID</label>
                <input type="email" name="email" class="form-field-input" style="height: 40px;" placeholder="name@benholdings.com" required>
            </div>

            <div class="form-group-row" style="margin-bottom: 32px;">
                <label class="field-label-text">Access Password</label>
                <input type="password" name="password" class="form-field-input" style="height: 40px;" placeholder="••••••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-tall" style="width: 100%; font-size: 14px; font-weight: 700; letter-spacing: 0.3px; height: 42px;">
                Request Token Clearance
            </button>
        </form>
    </div>
</div>

</body>
</html>