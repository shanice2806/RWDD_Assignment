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
// STEP 2: Get event ID from URL
// ========================================
if (!isset($_GET['id'])) {
    // No event ID provided, redirect to selection page
    header("Location: organizer_event_select_archive.php");
    exit();
}

$event_id = $_GET['id'];

// ========================================
// STEP 3: Check if user is allowed to archive this event
// ========================================
// User can archive if they created the event OR they are an active cohost
$check_query = "SELECT e.* FROM events e
                LEFT JOIN co_host ch ON e.event_id = ch.event_id
                WHERE e.event_id = '$event_id' 
                AND (e.event_created_by = '$user_id' OR (ch.user_id = '$user_id' AND ch.cohost_status = 'active'))";
$check_result = $conn->query($check_query);

if ($check_result->num_rows == 0) {
    // User doesn't have permission to archive this event
    echo "<script>alert('You do not have permission to archive this event.'); window.location.href='organizer_event_select_archive.php';</script>";
    exit();
}

// Get event data
$event = $check_result->fetch_assoc();

// ========================================
// STEP 4: Handle archive confirmation
// ========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_archive'])) {
    // Update event status to 'Archived'
    $archive_query = "UPDATE events SET event_status = 'Archived' WHERE event_id = '$event_id'";
    
    if ($conn->query($archive_query)) {
        echo "<script>alert('Event archived successfully!'); window.location.href='organizer_event_view.php';</script>";
        exit();
    } else {
        $error_message = "Failed to archive event. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Event - <?php echo htmlspecialchars($event['event_title']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
    <?php include 'organizer_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'organizer_sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Page title -->
            <div class="page-header">
                <h1><?php echo htmlspecialchars($event['event_title']); ?></h1>
            </div>
            
            <!-- Archive confirmation container -->
            <div class="container container-narrow">
                <h2 class="text-center mb-20">Archive Event Confirmation</h2>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <div class="confirmation-box">
                    <div class="confirmation-icon">⚠️</div>
                    <h3>Are you sure you want to archive this event?</h3>
                    
                    <div class="confirmation-message">
                        <p>Archived events will be moved to the archive section and cannot be edited.</p>
                        <p>You can still view its details and media.</p>
                    </div>
                    
                    <div class="event-summary">
                        <p><strong>Event:</strong> <?php echo htmlspecialchars($event['event_title']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('M d, Y - h:i A', strtotime($event['event_date_time'])); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($event['event_location']); ?></p>
                        <p><strong>Current Status:</strong> 
                            <span class="badge badge-<?php 
                                $status_lower = strtolower($event['event_status']);
                                if ($status_lower == 'approved') echo 'success';
                                else if ($status_lower == 'pending') echo 'warning';
                                else echo 'info';
                            ?>">
                                <?php echo htmlspecialchars($event['event_status']); ?>
                            </span>
                        </p>
                    </div>
                    
                    <form method="POST" class="confirmation-form">
                        <div class="flex gap-15 flex-center">
                            <a href="organizer_event_select_archive.php" class="action-btn">Cancel</a>
                            <button type="submit" name="confirm_archive" class="action-btn">Archive Event</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
