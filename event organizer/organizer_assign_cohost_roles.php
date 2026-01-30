<?php
session_start();

// Check if user is an organizer
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

include '../connect.php';

$organizer_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$selected_event_id = $_POST['event_id'] ?? ($_GET['event_id'] ?? '');

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $cohost_id = $_POST['cohost_id'] ?? '';
    $new_role = trim($_POST['new_role'] ?? '');
    $event_id = $_POST['event_id'] ?? '';
    
    if (empty($cohost_id) || empty($new_role)) {
        $error_message = "Please select a co-host and role.";
    } else {
        // Update co-host role
        $update_query = "UPDATE co_host SET cohost_role = '$new_role' WHERE cohost_id = '$cohost_id'";
        
        if ($conn->query($update_query)) {
            // Get co-host info for notification
            $cohost_info = "SELECT ch.user_id, ch.event_id, e.event_title, u.name 
                           FROM co_host ch
                           JOIN events e ON ch.event_id = e.event_id
                           JOIN users u ON ch.user_id = u.user_id
                           WHERE ch.cohost_id = '$cohost_id'";
            $info_result = $conn->query($cohost_info);
            $info = $info_result->fetch_assoc();
            
            // Generate notification
            $last_notif_query = "SELECT notification_id FROM notifications ORDER BY notification_id DESC LIMIT 1";
            $last_notif_result = $conn->query($last_notif_query);
            
            if ($last_notif_result && $last_notif_result->num_rows > 0) {
                $last_notif_id = $last_notif_result->fetch_assoc()['notification_id'];
                $notif_number = intval(substr($last_notif_id, 2)) + 1;
                $notification_id = 'n_' . str_pad($notif_number, 3, '0', STR_PAD_LEFT);
            } else {
                $notification_id = 'n_001';
            }
            
            $message = "Your co-host role has been updated to: $new_role for event: {$info['event_title']}";
            
            $notif_query = "INSERT INTO notifications 
                           (notification_id, event_id, message, audience_type, created_at, created_by, is_active) 
                           VALUES ('$notification_id', '{$info['event_id']}', '$message', '{$info['user_id']}', NOW(), '$organizer_id', 1)";
            $conn->query($notif_query);
            
            $success_message = "Role updated to '$new_role' for {$info['name']}. Notification sent.";
            $selected_event_id = $event_id;
        } else {
            $error_message = "Error updating role: " . $conn->error;
        }
    }
}

// Get events created by this organizer
$events_query = "SELECT event_id, event_title, event_date_time 
                FROM events 
                WHERE event_created_by = '$organizer_id' 
                ORDER BY event_date_time DESC";
$events_result = $conn->query($events_query);

// Get co-hosts for selected event
$cohosts = [];
if (!empty($selected_event_id)) {
    $cohosts_query = "SELECT ch.cohost_id, ch.user_id, ch.cohost_role, ch.cohost_status, u.name
                     FROM co_host ch
                     JOIN users u ON ch.user_id = u.user_id
                     WHERE ch.event_id = '$selected_event_id' AND ch.cohost_status = 'active'
                     ORDER BY u.name ASC";
    $cohosts_result = $conn->query($cohosts_query);
    if ($cohosts_result) {
        while ($cohost = $cohosts_result->fetch_assoc()) {
            $cohosts[] = $cohost;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Co-Host Roles - Organizer</title>
    <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
    <?php include 'organizer_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'organizer_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="back-btn-container">
                <a href="organizer_collaboration_feedback_hub.php" class="action-btn">← Back</a>
            </div>
            
            <div class="container container-narrow">
                <h1>Assign Co-Host Roles</h1>
                <p>Update roles for existing co-hosts</p>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= $success_message ?></div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?= $error_message ?></div>
                <?php endif; ?>
                
                <form method="POST" class="form-container">
                    <div class="form-group">
                        <label class="form-label">Select Event: <span class="required">*</span></label>
                        <select name="event_id" class="form-control" required onchange="this.form.submit()">
                            <option value="">-- Select Event --</option>
                            <?php 
                            $events_result->data_seek(0);
                            while ($event = $events_result->fetch_assoc()): 
                            ?>
                                <option value="<?= $event['event_id'] ?>" 
                                        <?= $selected_event_id == $event['event_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($event['event_title']) ?> 
                                    (<?= date('M d, Y', strtotime($event['event_date_time'])) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
                
                <?php if (!empty($cohosts)): ?>
                    <form method="POST" class="form-container">
                        <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Co-Host: <span class="required">*</span></label>
                            <select name="cohost_id" class="form-control" required>
                                <option value="">-- Select Co-Host --</option>
                                <?php foreach ($cohosts as $cohost): ?>
                                    <option value="<?= $cohost['cohost_id'] ?>">
                                        <?= htmlspecialchars($cohost['name']) ?> (<?= $cohost['user_id'] ?>) 
                                        - Current: <?= $cohost['cohost_role'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">New Role: <span class="required">*</span></label>
                            <select name="new_role" class="form-control" required>
                                <option value="">-- Select Role --</option>
                                <option value="Full Access">Full Access</option>
                                <option value="Attendance Manager">Attendance Manager</option>
                                <option value="Editor">Editor</option>
                                <option value="Coordinator">Coordinator</option>
                                <option value="Moderator">Moderator</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_role" class="action-btn">Update Role</button>
                        </div>
                    </form>
                <?php elseif (!empty($selected_event_id)): ?>
                    <div class="alert">No active co-hosts found for this event.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'organizer_footer.php'; ?>
</body>
</html>
