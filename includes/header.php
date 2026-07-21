<?php
// 1. Daily Milestone & Countdown Auto-Execution
if (!isset($_SESSION['last_milestone_check']) || $_SESSION['last_milestone_check'] !== date('Y-m-d')) {
    if (file_exists(__DIR__ . '/check_hearings.php')) {
        require_once __DIR__ . '/check_hearings.php';
        $_SESSION['last_milestone_check'] = date('Y-m-d');
    }
}

// 2. Fetch Unread Notifications for Header Badge
$unreadCount = 0;
$unreadNotifications = [];
if (isset($pdo) && isset($_SESSION['user_role'])) {
    $userRole = $_SESSION['user_role'];
    $userId = $_SESSION['user_id'] ?? 0;

    $stmtNotif = $pdo->prepare("
        SELECT * FROM system_notifications 
        WHERE (target_role = 'All' OR target_role = :role OR user_id = :uid)
        ORDER BY is_read ASC, created_at DESC 
        LIMIT 10
    ");
    $stmtNotif->execute(['role' => $userRole, 'uid' => $userId]);
    $unreadNotifications = $stmtNotif->fetchAll();

    foreach ($unreadNotifications as $n) {
        if ($n['is_read'] == 0) $unreadCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1440, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " | Legal" : "Legal Portal"; ?></title>

    <!-- Global JS Constant for API endpoints -->
    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
    </script>

    <!-- Absolute root routing paths for assets -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <script src="<?php echo BASE_URL; ?>assets/js/pagination.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/app.js?v=<?php echo time(); ?>"></script>

    <style>
        /* Header Notification Bell & Dropdown Styles */
        .notif-wrapper {
            position: relative;
            margin-right: 12px;
        }
        .notif-bell-btn {
            background: #F4F7F6;
            border: 1px solid var(--border-color, #E2E8F0);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            transition: background 0.2s;
        }
        .notif-bell-btn:hover {
            background: #E2E8F0;
        }
        .notif-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #E53E3E;
            color: #FFFFFF;
            font-size: 10px;
            font-weight: 800;
            height: 18px;
            min-width: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            border: 2px solid #FFFFFF;
        }
        .notif-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 48px;
            width: 360px;
            background: #FFFFFF;
            border: 1px solid var(--border-color, #E2E8F0);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow: hidden;
        }
        .notif-dropdown.active {
            display: block;
        }
        .notif-header {
            padding: 12px 16px;
            background: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            font-weight: 700;
            color: #1E293B;
        }
        .notif-list {
            max-height: 320px;
            overflow-y: auto;
        }
        .notif-item {
            padding: 12px 16px;
            border-bottom: 1px solid #F1F5F9;
            transition: background 0.15s;
        }
        .notif-item.unread {
            background: #F0F9FF;
        }
        .notif-item:hover {
            background: #F8FAFC;
        }
        .notif-title {
            font-size: 12px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 2px;
        }
        .notif-msg {
            font-size: 11px;
            color: #475569;
            line-height: 1.4;
        }
        .notif-time {
            font-size: 10px;
            color: #94A3B8;
            margin-top: 4px;
        }
    </style>
</head>
<body>
<div class="app-container">
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <div class="main-workspace">
        <header class="top-header">
            <div class="header-title-block">
                <div class="breadcrumb"><?php echo isset($breadcrumb) ? $breadcrumb : 'SYSTEM'; ?></div>
                <h1><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
            </div>
            
            <div class="header-actions-block" style="display: flex; align-items: center;">
                
                <!-- NOTIFICATION BELL DROPDOWN -->
                <div class="notif-wrapper">
                    <button class="notif-bell-btn" id="notifBellBtn" title="System Alerts">
                        🔔
                        <?php if ($unreadCount > 0): ?>
                            <span class="notif-badge" id="notifBadgeCount"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </button>

                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">
                            <span>System Notifications</span>
                            <?php if ($unreadCount > 0): ?>
                                <span style="font-size: 11px; color: #0284C7; cursor: pointer;" onclick="markAllNotificationsRead()">Mark all as read</span>
                            <?php endif; ?>
                        </div>
                        <div class="notif-list">
                            <?php if (!empty($unreadNotifications)): ?>
                                <?php foreach ($unreadNotifications as $notif): ?>
                                    <div class="notif-item <?php echo ($notif['is_read'] == 0) ? 'unread' : ''; ?>">
                                        <div class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                        <div class="notif-msg"><?php echo htmlspecialchars($notif['message']); ?></div>
                                        <div class="notif-time"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding: 20px; text-align: center; font-size: 12px; color: #94A3B8;">
                                    No notifications found.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (isset($header_btn_link) && isset($header_btn_text) && isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'Viewer'): ?>
                    <a href="<?php echo $header_btn_link; ?>" class="btn btn-primary btn-tall"><?php echo $header_btn_text; ?></a>
                <?php endif; ?>
                
                <div class="user-profile-badge">
                    <div>
                        <div class="user-meta-name"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Guest'; ?></div>
                        <div class="user-meta-role">
                            <?php 
                            if (isset($_SESSION['user_role'])) {
                                if ($_SESSION['user_role'] === 'Admin') echo 'Legal Admin';
                                elseif ($_SESSION['user_role'] === 'Staff') echo 'Legal Staff (Editor)';
                                else echo 'Read-Only Auditing Scope';
                            } else {
                                echo 'Unauthenticated';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="avatar-circle"></div>
                </div>

            </div>
        </header>

<script>
// Toggle Notification Dropdown
document.addEventListener('DOMContentLoaded', function() {
    const bellBtn = document.getElementById('notifBellBtn');
    const dropdown = document.getElementById('notifDropdown');

    if (bellBtn && dropdown) {
        bellBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    }
});

// Mark all as read function
function markAllNotificationsRead() {
    const endpoint = (typeof BASE_URL !== 'undefined') ? BASE_URL + 'includes/settings_handler.php' : '../includes/settings_handler.php';
    const fd = new FormData();
    fd.append('action', 'mark_notifications_read');

    fetch(endpoint, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('notifBadgeCount');
            if (badge) badge.remove();
            document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
        }
    });
}
</script>