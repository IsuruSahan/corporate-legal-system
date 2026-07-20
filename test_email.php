<?php
// Enable full error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Update these paths if your PHPMailer files are located elsewhere (e.g., inside includes/ or vendor/)
require_once __DIR__ . '/includes/PHPMailer/Exception.php';
require_once __DIR__ . '/includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/includes/PHPMailer/SMTP.php';

// --- ENTER YOUR TEST RECIPIENT EMAIL HERE ---
$testRecipient = '12isurukumarasiri@gmail.com'; 

$mail = new PHPMailer(true);

try {
    // 1. Enable Verbose SMTP Debug Output
    $mail->SMTPDebug = 2; // Output raw SMTP conversation
    $mail->Debugoutput = 'html';

    // 2. Server Settings (AWS SES)
    $mail->isSMTP();
    $mail->Host       = 'email-smtp.eu-west-2.amazonaws.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'AKIAUBUYVUFLWGBH3D4O';
    $mail->Password   = 'BOVgWmn3DIXxRhMDbWYJMhVz+pPoudi2Kh2f7fyhyWYC';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port       = 465;

    // SSL Peer Verification options
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        )
    );

    // 3. Sender & Recipient
    $mail->setFrom('ams-notifications@benholdingslk.com', 'BenLegal Portal Test');
    $mail->addAddress($testRecipient);

    // 4. Content
    $mail->isHTML(true);
    $mail->Subject = 'BenLegal System - AWS SES SMTP Connection Test';
    $mail->Body    = '
        <div style="font-family: Arial, sans-serif; padding: 20px; border-left: 4px solid #0056b3; background: #f8fafc;">
            <h3 style="color: #0056b3; margin-top: 0;">SMTP Test Successful!</h3>
            <p>This email confirms that the AWS SES SMTP credentials are active and communicating properly with the BenLegal Corporate Legal System.</p>
            <small style="color: #64748b;">Timestamp: ' . date('Y-m-d H:i:s') . '</small>
        </div>';

    if ($mail->send()) {
        echo "<h2 style='color: green;'>✓ Email sent successfully to {$testRecipient}!</h2>";
    }
} catch (Exception $e) {
    echo "<h2 style='color: red;'>✕ Email sending failed!</h2>";
    echo "<b>Mailer Error:</b> " . $mail->ErrorInfo;
}
?>