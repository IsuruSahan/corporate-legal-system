<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/mailer.php';

// Force local timezone consistency across CLI/Cron and Apache Web server
date_default_timezone_set('Asia/Colombo');
$pdo->exec("SET time_zone = '+05:30'");

if (!function_exists('processCourtMilestoneAlerts')) {
    function processCourtMilestoneAlerts() {
        global $pdo;

        // Dual-purpose logger: handles both CLI/Cron output and Browser DevTools console
        function logToConsole($status, $details, $error = null) {
            $isCli = (php_sapi_name() === 'cli');
            $logData = [
                'status'    => $status,
                'details'   => $details,
                'error'     => $error,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // 1. Always record in PHP system error log
            error_log("[BenLegal Mailer] " . json_encode($logData));

            // 2. Output to CLI Terminal or Browser Console
            if ($isCli) {
                echo "[" . date('Y-m-d H:i:s') . "] {$status}: " . json_encode($details) . ($error ? " - ERROR: $error" : "") . "\n";
            } else {
                $jsonString = json_encode($logData);
                echo "<script>console.log('📬 [BenLegal Mailer Debug]:', {$jsonString});</script>\n";
            }
        }

        // 1. Fetch Lead Time Settings
        $stmtSetting = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('court_hearing_notice_days', 'court_next_step_notice_days')");
        $settingsMap = $stmtSetting->fetchAll(PDO::FETCH_KEY_PAIR);

        $hearingNoticeDays  = intval($settingsMap['court_hearing_notice_days'] ?? 7);
        $nextStepNoticeDays = intval($settingsMap['court_next_step_notice_days'] ?? 5);

        // -------------------------------------------------------------
        // PART A: UPCOMING COURT HEARINGS
        // -------------------------------------------------------------
        $queryHearings = "
            SELECT cc.id, cc.case_number, cc.case_parties, cc.case_description, cc.next_hearing_date, cc.assigned_officer_id,
                   u.email as officer_email, u.full_name as officer_name,
                   cr.room_name as court_name, gc.company_name
            FROM court_cases cc
            LEFT JOIN users u ON cc.assigned_officer_id = u.id
            LEFT JOIN court_rooms cr ON cc.court_id = cr.id
            LEFT JOIN group_companies gc ON cc.group_company_id = gc.id
            WHERE cc.next_hearing_date IS NOT NULL 
              AND cc.next_hearing_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :h_days DAY)
              AND cc.case_status != 'Settled'
        ";

        $stmtH = $pdo->prepare($queryHearings);
        $stmtH->execute([':h_days' => $hearingNoticeDays]);
        $hearingCases = $stmtH->fetchAll();

        foreach ($hearingCases as $case) {
            $daysRem = (int) ((strtotime($case['next_hearing_date']) - strtotime(date('Y-m-d'))) / 86400);
            $timeText = ($daysRem === 0) ? "TODAY" : (($daysRem === 1) ? "TOMORROW" : "in {$daysRem} days");

            $title = "Upcoming Hearing: {$case['case_number']}";
            $message = "Hearing for {$case['case_parties']} ({$case['company_name']}) at {$case['court_name']} is scheduled {$timeText} ({$case['next_hearing_date']}).";

            $checkDup = $pdo->prepare("SELECT COUNT(*) FROM system_notifications WHERE title = :title AND DATE(created_at) = CURDATE()");
            $checkDup->execute([':title' => $title]);

            if ($checkDup->fetchColumn() == 0) {
                // Single consolidated notification for Admin & Assigned Officer visibility
                $insNotif = $pdo->prepare("
                    INSERT INTO system_notifications (user_id, target_role, title, message, category, priority) 
                    VALUES (:uid, 'Admin', :t, :m, 'Calendar', 'High')
                ");
                $insNotif->execute([
                    ':uid' => $case['assigned_officer_id'] ?? null, 
                    ':t'   => $title, 
                    ':m'   => $message
                ]);

                // Email Notification
                $recipients = getNotificationRecipients($case['officer_email']);
                $emailSubject = "HEARING ALERT: {$case['case_number']} - {$timeText}";
                
                $emailBody = "
                    <p>Dear Legal Team,</p>
                    <p>This is an advance reminder for an upcoming <strong>Court Hearing</strong>:</p>
                    <ul>
                        <li><strong>Case Ref:</strong> " . htmlspecialchars($case['case_number']) . "</li>
                        <li><strong>Parties:</strong> " . htmlspecialchars($case['case_parties']) . "</li>
                        <li><strong>Entity:</strong> " . htmlspecialchars($case['company_name']) . "</li>
                        <li><strong>Court Room:</strong> " . htmlspecialchars($case['court_name']) . "</li>
                        <li><strong>Hearing Date:</strong> <strong>{$case['next_hearing_date']} ({$timeText})</strong></li>
                        <li><strong>Case Brief:</strong> " . htmlspecialchars($case['case_description']) . "</li>
                        <li><strong>Assigned Officer:</strong> " . htmlspecialchars($case['officer_name']) . "</li>
                    </ul>";
                
                $mailSent = sendSystemEmail($recipients, $emailSubject, $emailBody);
                
                if ($mailSent) {
                    logToConsole("SUCCESS", [
                        'type' => 'Court Hearing Alert',
                        'case' => $case['case_number'],
                        'recipients' => $recipients,
                        'subject' => $emailSubject
                    ]);
                } else {
                    logToConsole("FAILED", [
                        'type' => 'Court Hearing Alert',
                        'case' => $case['case_number'],
                        'recipients' => $recipients
                    ], "Mailer failed to dispatch email via AWS SES. Check SMTP/PHPMailer configuration.");
                }
            } else {
                logToConsole("SKIPPED", [
                    'type' => 'Court Hearing Alert',
                    'case' => $case['case_number'],
                    'reason' => 'Notification already sent today.'
                ]);
            }
        }

        // -------------------------------------------------------------
        // PART B: UPCOMING NEXT STEP DEADLINES
        // -------------------------------------------------------------
        $querySteps = "
            SELECT cc.id, cc.case_number, cc.case_parties, cc.next_step_date, cc.next_step_description, cc.assigned_officer_id,
                   u.email as officer_email, u.full_name as officer_name, gc.company_name
            FROM court_cases cc
            LEFT JOIN users u ON cc.assigned_officer_id = u.id
            LEFT JOIN group_companies gc ON cc.group_company_id = gc.id
            WHERE cc.next_step_date IS NOT NULL 
              AND cc.next_step_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :s_days DAY)
              AND cc.case_status != 'Settled'
        ";

        $stmtS = $pdo->prepare($querySteps);
        $stmtS->execute([':s_days' => $nextStepNoticeDays]);
        $stepCases = $stmtS->fetchAll();

        foreach ($stepCases as $case) {
            $daysRem = (int) ((strtotime($case['next_step_date']) - strtotime(date('Y-m-d'))) / 86400);
            $timeText = ($daysRem === 0) ? "TODAY" : (($daysRem === 1) ? "TOMORROW" : "in {$daysRem} days");

            $title = "Action Deadline: {$case['case_number']}";
            $message = "Next Step '{$case['next_step_description']}' for {$case['case_parties']} is due {$timeText} ({$case['next_step_date']}).";

            $checkDup = $pdo->prepare("SELECT COUNT(*) FROM system_notifications WHERE title = :title AND DATE(created_at) = CURDATE()");
            $checkDup->execute([':title' => $title]);

            if ($checkDup->fetchColumn() == 0) {
                // Single consolidated notification for Admin & Assigned Officer visibility
                $insNotif = $pdo->prepare("
                    INSERT INTO system_notifications (user_id, target_role, title, message, category, priority) 
                    VALUES (:uid, 'Admin', :t, :m, 'Litigation', 'Medium')
                ");
                $insNotif->execute([
                    ':uid' => $case['assigned_officer_id'] ?? null, 
                    ':t'   => $title, 
                    ':m'   => $message
                ]);

                // Email Notification
                $recipients = getNotificationRecipients($case['officer_email']);
                $emailSubject = "ACTION DEADLINE: {$case['case_number']} - {$timeText}";
                
                $emailBody = "
                    <p>Dear Legal Team,</p>
                    <p>This is an advance reminder for an upcoming <strong>Litigation Procedural Step</strong>:</p>
                    <ul>
                        <li><strong>Case Ref:</strong> " . htmlspecialchars($case['case_number']) . "</li>
                        <li><strong>Parties:</strong> " . htmlspecialchars($case['case_parties']) . "</li>
                        <li><strong>Entity:</strong> " . htmlspecialchars($case['company_name']) . "</li>
                        <li><strong>Target Action Date:</strong> <strong>{$case['next_step_date']} ({$timeText})</strong></li>
                        <li><strong>Required Action:</strong> " . htmlspecialchars($case['next_step_description']) . "</li>
                        <li><strong>Assigned Officer:</strong> " . htmlspecialchars($case['officer_name']) . "</li>
                    </ul>";
                
                $mailSent = sendSystemEmail($recipients, $emailSubject, $emailBody);

                if ($mailSent) {
                    logToConsole("SUCCESS", [
                        'type' => 'Next Step Action Alert',
                        'case' => $case['case_number'],
                        'recipients' => $recipients,
                        'subject' => $emailSubject
                    ]);
                } else {
                    logToConsole("FAILED", [
                        'type' => 'Next Step Action Alert',
                        'case' => $case['case_number'],
                        'recipients' => $recipients
                    ], "Mailer failed to dispatch email via AWS SES. Check SMTP/PHPMailer configuration.");
                }
            } else {
                logToConsole("SKIPPED", [
                    'type' => 'Next Step Action Alert',
                    'case' => $case['case_number'],
                    'reason' => 'Notification already sent today.'
                ]);
            }
        }
    }
}

// Helper to gather emails (Assigned Officer + Admins)
if (!function_exists('getNotificationRecipients')) {
    function getNotificationRecipients($officerEmail) {
        global $pdo;
        $list = [];
        if (!empty($officerEmail)) $list[] = $officerEmail;

        $adminStmt = $pdo->query("SELECT email FROM users WHERE role = 'Admin'");
        while ($email = $adminStmt->fetchColumn()) {
            if ($email) $list[] = $email;
        }
        return array_unique($list);
    }
}

// Run processor
processCourtMilestoneAlerts();