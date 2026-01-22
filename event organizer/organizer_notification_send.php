<?php
// ========================================
// STEP 1: Start session and check login
// ========================================
session_start();

// Only event organizers can access this page
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

// Connect to database
include '../connect.php';

// Get current user's ID
$user_id = $_SESSION['user_id'];

// ========================================
// STEP 2: Get events for dropdown
// ========================================
// Get events created by user OR where user is active cohost
$events_query = "SELECT DISTINCT e.event_id, e.event_title 
                FROM events e
                LEFT JOIN co_host ch ON e.event_id = ch.event_id
                WHERE (e.event_created_by = '$user_id' OR (ch.user_id = '$user_id' AND ch.cohost_status = 'active'))
                AND e.event_status != 'Archived'
                ORDER BY e.event_title";
$events_result = $conn->query($events_query);

// ========================================
// STEP 3: Handle form submission
// ========================================
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_notification'])) {
    $event_id = $_POST['event_id'];
    $message = trim($_POST['message']);
    $audience_type = $_POST['audience_type']; // 'participants' or 'volunteers'
    
    // Validate inputs
    if (empty($event_id) || empty($message)) {
        $error_message = "Please select an event and enter a notification message.";
    } else {
        // Generate new notification ID
        $id_query = "SELECT notification_id FROM notifications ORDER BY notification_id DESC LIMIT 1";
        $id_result = $conn->query($id_query);
        
        if ($id_result->num_rows > 0) {
            $last_id = $id_result->fetch_assoc()['notification_id'];
            $num = (int)substr($last_id, 2) + 1;
            $new_id = 'n_' . str_pad($num, 3, '0', STR_PAD_LEFT);
        } else {
            $new_id = 'n_001';
        }
        
        // Get recipient count based on audience type
        $recipient_count = 0;
        
        if ($audience_type == 'participants') {
            // Count registered or checked-in participants
            $count_query = "SELECT COUNT(DISTINCT user_id) as count FROM registrations 
                           WHERE event_id = '$event_id' 
                           AND registration_status IN ('Registered', 'Checked-in')";
            $count_result = $conn->query($count_query);
            $recipient_count = $count_result->fetch_assoc()['count'];
            
        } else if ($audience_type == 'volunteers') {
            // Count volunteers
            $count_query = "SELECT COUNT(DISTINCT user_id) as count FROM volunteers 
                           WHERE event_id = '$event_id'";
            $count_result = $conn->query($count_query);
            $recipient_count = $count_result->fetch_assoc()['count'];
        }
        
        // Insert notification
        $created_at = date('Y-m-d H:i:s');
        
        $insert_query = "INSERT INTO notifications (notification_id, event_id, message, audience_type, created_at, created_by, is_active) 
                        VALUES ('$new_id', '$event_id', '$message', '$audience_type', '$created_at', '$user_id', 1)";
        
        if ($conn->query($insert_query)) {
            $success_message = "Notification sent successfully to $recipient_count " . ($audience_type == 'volunteers' ? 'volunteers' : 'participants') . "!";
        } else {
            $error_message = "Failed to send notification. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notification</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
    <?php include 'organizer_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'organizer_sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Page header -->
            <div class="page-header flex-between">
                <h1>Send Notification</h1>
                <a href="organizer_dashboard.php" class="btn-secondary">Back</a>
            </div>
            
            <!-- Form container -->
            <div class="container container-narrow">
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <form method="POST" class="notification-form">
                    <!-- Select Event -->
                    <div class="form-group">
                        <label class="form-label">Select Event: <span class="required">*</span></label>
                        <select name="event_id" class="form-control" required>
                            <option value="">-- Choose an event --</option>
                            <?php 
                            if ($events_result->num_rows > 0) {
                                while ($event = $events_result->fetch_assoc()) {
                                    $selected = (isset($_POST['event_id']) && $_POST['event_id'] == $event['event_id']) ? 'selected' : '';
                                    echo "<option value='" . $event['event_id'] . "' $selected>" . htmlspecialchars($event['event_title']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Notification Message -->
                    <div class="form-group">
                        <label class="form-label">Notification Message: <span class="required">*</span></label>
                        <textarea name="message" class="form-control" rows="5" required placeholder="Check-in opens in 10 minutes!"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Target Audience -->
                    <div class="form-group">
                        <label class="form-label">Target Audience: <span class="required">*</span></label>
                        <div class="audience-buttons">
                            <button type="button" class="audience-btn <?php echo (!isset($_POST['audience_type']) || $_POST['audience_type'] == 'participants') ? 'active' : ''; ?>" onclick="selectAudience('participants')">
                                All Participants
                            </button>
                            <button type="button" class="audience-btn <?php echo (isset($_POST['audience_type']) && $_POST['audience_type'] == 'volunteers') ? 'active' : ''; ?>" onclick="selectAudience('volunteers')">
                                Volunteers Only
                            </button>
                        </div>
                        <input type="hidden" name="audience_type" id="audience_type" value="<?php echo isset($_POST['audience_type']) ? $_POST['audience_type'] : 'participants'; ?>" required>
                    </div>
                    
                    <!-- Info box -->
                    <div class="info-box mb-20">
                        <p><strong>Participants:</strong> All users who registered or checked-in for the event</p>
                        <p><strong>Volunteers:</strong> Only users assigned as volunteers for the event</p>
                    </div>
                    
                    <!-- Submit button -->
                    <div class="text-center">
                        <button type="submit" name="send_notification" class="btn-primary btn-large">Send Notification</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Function to handle audience selection
        function selectAudience(type) {
            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.audience-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            event.target.classList.add('active');
            
            // Set hidden input value
            document.getElementById('audience_type').value = type;
        }
    </script>
</body>
</html>
