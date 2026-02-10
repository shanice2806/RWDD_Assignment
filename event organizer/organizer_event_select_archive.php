<?php
// ========================================
// Start session and check login
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
//  Get events user can archive
// ========================================
$query = "SELECT DISTINCT e.event_id, e.event_title, e.event_date_time, e.event_status, e.event_location 
          FROM events e
          LEFT JOIN co_host ch ON e.event_id = ch.event_id
          WHERE (e.event_created_by = '$user_id' OR (ch.user_id = '$user_id' AND ch.cohost_status = 'active'))
          AND LOWER(e.event_status) != 'archived'
          ORDER BY e.event_date_time DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Event to Archive</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
    <?php include 'organizer_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'organizer_sidebar.php'; ?>
        
          <div class="main-content">
    <div class="back-btn-container">
      <a href="organizer_event_management.php" class="action-btn"> Back</a>
    </div>            <!-- Page title -->
            <div class="page-header">
                <h1>Select Event to Archive</h1>
                <p>Choose an event from the list below to archive</p>
            </div>
            
            <!-- Events grid -->
            <div class="events-grid">
                <?php
                if ($result->num_rows > 0) {
                    // Loop through each event
                    while ($event = $result->fetch_assoc()) {
                        ?>
                        <div class="card">
                            <h3 class="card-title"><?php echo htmlspecialchars($event['event_title']); ?></h3>
                            
                            <div class="event-card-info">
                                <p><strong>Date:</strong> <?php echo date('M d, Y - h:i A', strtotime($event['event_date_time'])); ?></p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($event['event_location']); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge badge-<?php 
                                        $status_lower = strtolower($event['event_status']);
                                        if ($status_lower == 'approved') echo 'success';
                                        else if ($status_lower == 'pending') echo 'warning';
                                        else if ($status_lower == 'draft') echo 'info';
                                        else echo 'danger';
                                    ?>">
                                        <?php echo htmlspecialchars($event['event_status']); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="event-card-actions">
                                <a href="organizer_event_archive.php?id=<?php echo $event['event_id']; ?>" class="action-btn">📦 Archive This Event</a>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="no-events">
                        <p>You don't have any events to archive.</p>
                        <a href="organizer_event_create.php" class="action-btn">Create New Event</a>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <!-- Back button -->
            <div class="text-center mt-20">
                <a href="organizer_event_management.php" class="action-btn">← Back to Event Management</a>
            </div>
        </div>
    </div>
</body>
</html>
