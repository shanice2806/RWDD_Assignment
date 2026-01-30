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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invite_cohost'])) {
    $event_id = $_POST['event_id'] ?? '';
    $cohost_name = trim($_POST['cohost_name'] ?? '');
    $cohost_id = trim($_POST['cohost_id'] ?? '');
    $cohost_role = trim($_POST['cohost_role'] ?? '');
    $role_description = trim($_POST['role_description'] ?? '');
    
    if (empty($event_id)) {
        $error_message = "Please select an event.";
    } elseif (empty($cohost_name)) {
        $error_message = "Please enter co-host name.";
    } elseif (empty($cohost_id)) {
        $error_message = "Please enter co-host ID.";
    } elseif (empty($cohost_role)) {
        $error_message = "Please select a role.";
    } else {
        // Check if the cohost_id is a valid organizer
        $check_user = "SELECT user_id, name FROM users 
                      WHERE user_id = '$cohost_id' 
                      AND role = 'Event Organizer' 
                      AND name = '$cohost_name'";
        $user_result = $conn->query($check_user);
        
        if ($user_result && $user_result->num_rows > 0) {
            // Check if user is trying to invite themselves
            if ($cohost_id === $organizer_id) {
                $error_message = "You cannot invite yourself as a co-host.";
            } else {
                // Check if already a co-host for this event
                $check_cohost = "SELECT * FROM co_host 
                                WHERE event_id = '$event_id' 
                                AND user_id = '$cohost_id' 
                                AND cohost_status != 'removed'";
                $cohost_result = $conn->query($check_cohost);
                
                if ($cohost_result && $cohost_result->num_rows > 0) {
                    $error_message = "This organizer is already a co-host for this event.";
                } else {
                    // Generate sequential cohost ID
                    $last_id_query = "SELECT cohost_id FROM co_host ORDER BY cohost_id DESC LIMIT 1";
                    $last_id_result = $conn->query($last_id_query);
                    
                    if ($last_id_result && $last_id_result->num_rows > 0) {
                        $last_id = $last_id_result->fetch_assoc()['cohost_id'];
                        $number = intval(substr($last_id, 3)) + 1;
                        $new_cohost_id = 'ch_' . str_pad($number, 3, '0', STR_PAD_LEFT);
                    } else {
                        $new_cohost_id = 'ch_001';
                    }
                    
                    // Insert into co_host table
                    $insert_query = "INSERT INTO co_host 
                                   (cohost_id, event_id, user_id, cohost_role, cohost_status, cohost_date_added) 
                                   VALUES ('$new_cohost_id', '$event_id', '$cohost_id', '$cohost_role', 'active', NOW())";
                    
                    if ($conn->query($insert_query)) {
                        // Get event title for notification
                        $event_query = "SELECT event_title FROM events WHERE event_id = '$event_id'";
                        $event_result = $conn->query($event_query);
                        $event_title = $event_result->fetch_assoc()['event_title'];
                        
                        // Generate sequential notification ID
                        $last_notif_query = "SELECT notification_id FROM notifications ORDER BY notification_id DESC LIMIT 1";
                        $last_notif_result = $conn->query($last_notif_query);
                        
                        if ($last_notif_result && $last_notif_result->num_rows > 0) {
                            $last_notif_id = $last_notif_result->fetch_assoc()['notification_id'];
                            $notif_number = intval(substr($last_notif_id, 2)) + 1;
                            $notification_id = 'n_' . str_pad($notif_number, 3, '0', STR_PAD_LEFT);
                        } else {
                            $notification_id = 'n_001';
                        }
                        
                        // Create notification message
                        $message = "You have been invited and assigned as the co-host of event: $event_title! ";
                        $message .= "Role: $cohost_role. ";
                        if (!empty($role_description)) {
                            $message .= "Description: $role_description";
                        }
                        
                        // Escape message for SQL
                        $message = $conn->real_escape_string($message);
                        
                        // Insert notification - audience_type is the specific user_id
                        $notif_query = "INSERT INTO notifications 
                                       (notification_id, event_id, message, audience_type, created_at, created_by, is_active) 
                                       VALUES ('$notification_id', '$event_id', '$message', '$cohost_id', NOW(), '$organizer_id', 1)";
                        
                        if ($conn->query($notif_query)) {
                            $success_message = "Co-host '$cohost_name' has been successfully invited and assigned as $cohost_role! A notification has been sent.";
                        } else {
                            $error_message = "Co-host added but notification failed: " . $conn->error;
                        }
                    } else {
                        $error_message = "Error adding co-host: " . $conn->error;
                    }
                }
            }
        } else {
            $error_message = "User not found or is not an Event Organizer. Please verify the name and ID match an existing organizer.";
        }
    }
}

// Get events created by this organizer
$events_query = "SELECT event_id, event_title, event_date_time 
                FROM events 
                WHERE event_created_by = '$organizer_id' 
                AND event_status IN ('Pending', 'Approved')
                ORDER BY event_date_time DESC";
$events_result = $conn->query($events_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invite & Assign Co-Host - Organizer</title>
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
                <h1>Invite & Assign Co-Host</h1>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= $success_message ?></div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?= $error_message ?></div>
                <?php endif; ?>
                
                <form method="POST" class="form-container">
                    <div class="form-group">
                        <label class="form-label">Select Event: <span class="required">*</span></label>
                        <select name="event_id" class="form-control" required>
                            <option value="">-- Select Event --</option>
                            <?php while ($event = $events_result->fetch_assoc()): ?>
                                <option value="<?= $event['event_id'] ?>">
                                    <?= htmlspecialchars($event['event_title']) ?> 
                                    (<?= date('M d, Y', strtotime($event['event_date_time'])) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-section">
                        <h3>Co-Host Information</h3>
                        
                        <div class="form-group">
                            <label class="form-label">Co-Host Name: <span class="required">*</span></label>
                            <input type="text" name="cohost_name" class="form-control" required 
                                   placeholder="Enter full name (must match existing organizer)">
                            <small class="form-text">Must be a registered Event Organizer</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Co-Host ID: <span class="required">*</span></label>
                            <input type="text" name="cohost_id" class="form-control" required 
                                   placeholder="e.g., TP000002" pattern="TP\d{6}">
                            <small class="form-text">Format: TP followed by 6 digits</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Assign Role: <span class="required">*</span></label>
                            <select name="cohost_role" class="form-control" required>
                                <option value="">-- Select Role --</option>
                                <option value="Full Access">Full Access</option>
                                <option value="Attendance Manager">Attendance Manager</option>
                                <option value="Editor">Editor</option>
                                <option value="Coordinator">Coordinator</option>
                                <option value="Moderator">Moderator</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Role Description (Optional):</label>
                            <textarea name="role_description" class="form-control" rows="4" 
                                      placeholder="Describe the responsibilities and permissions for this co-host role..."></textarea>
                            <small class="form-text">This will be included in the notification sent to the co-host</small>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="invite_cohost" class="action-btn">Send Invitation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'organizer_footer.php'; ?>
</body>
</html>
