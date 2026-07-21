<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

/**
 * Sends a system notification email using PHPMailer & AWS SES.
 * 
 * @param string|array $to_emails Recipient email or array of recipient emails
 * @param string $subject Email subject line
 * @param string $htmlBody HTML content for the email body
 * @return bool True on success, false on failure
 */
function sendSystemEmail($to_emails, $subject, $htmlBody) {
    $mail = new PHPMailer(true);

    try {
        // --- 1. SERVER SETTINGS ---
        $mail->isSMTP();
        $mail->Host       = 'email-smtp.eu-west-2.amazonaws.com';
        $mail->SMTPAuth   = true;
        
        $mail->Username   = 'AKIAUBUYVUFLWGBH3D4O';
        $mail->Password   = 'BOVgWmn3DIXxRhMDbWYJMhVz+pPoudi2Kh2f7fyhyWYC';
        
        // --- 2. ENCRYPTION & PORT ---
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port       = 465; 

        // --- 3. SSL OPTIONS ---
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            )
        );

        // --- 4. SENDER ---
        $mail->setFrom('ams-notifications@benholdingslk.com', 'BenLegal Portal');

        // --- 5. RECIPIENTS ---
        if (is_array($to_emails)) {
            foreach ($to_emails as $email) {
                if (!empty(trim($email))) {
                    $mail->addBCC(trim($email));
                }
            }
        } else {
            if (!empty(trim($to_emails))) {
                $mail->addAddress(trim($to_emails));
            }
        }

        // --- 6. CONTENT FORMATTING ---
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border-left: 5px solid #0056b3; background: #f8fafc;'>
                <h2 style='color: #0056b3; margin-top: 0;'>BenLegal System Notification</h2>
                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 15px 0;'>
                $htmlBody
                <p style='font-size: 11px; color: #94a3b8; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 10px;'>
                    This is an automated system notification from BenLegal Corporate Legal System.
                </p>
            </div>";

        return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}