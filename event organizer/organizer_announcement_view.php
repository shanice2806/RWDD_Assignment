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
// STEP 2: Get announcement ID
// ========================================
if (!isset($_GET['id'])) {
    header("Location: organizer_announcement.php");
    exit();
}

$announcement_id = $_GET['id'];

// ========================================
// STEP 3: Get announcement and verify ownership
// ========================================
$query = "SELECT * FROM announcements WHERE announcement_id = '$announcement_id' AND created_by = '$user_id'";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    echo "<script>alert('Announcement not found or you do not have permission to view it.'); window.location.href='organizer_announcement.php';</script>";
    exit();
}

$announcement = $result->fetch_assoc();

// Determine status
$status_label = 'Draft';
$status_class = 'badge-info';

if ($announcement['is_active'] == 1) {
    $status_label = 'Published';
    $status_class = 'badge-success';
} else if (strtotime($announcement['end_date']) < time()) {
    $status_label = 'Expired';
    $status_class = 'badge-danger';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Announcement</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
    <?php include 'organizer_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'organizer_sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Page header -->
            <div class="page-header">
                <h1>Announcement Details</h1>
            </div>
            
            <!-- Announcement details container -->
            <div class="container container-narrow">
                <div class="announcement-detail">
                    <div class="detail-item">
                        <strong>Title:</strong>
                        <p><?php echo htmlspecialchars($announcement['title']); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Status:</strong>
                        <p><span class="badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span></p>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Start Date:</strong>
                        <p><?php echo date('d/m/Y H:i', strtotime($announcement['start_date'])); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <strong>End Date:</strong>
                        <p><?php echo date('d/m/Y H:i', strtotime($announcement['end_date'])); ?></p>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Message:</strong>
                        <div class="announcement-message">
                            <?php echo nl2br(htmlspecialchars($announcement['message'])); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Created At:</strong>
                        <p><?php echo date('d/m/Y H:i', strtotime($announcement['created_at'])); ?></p>
                    </div>
                </div>
                
                <!-- Action buttons -->
                <div class="flex gap-15 flex-center mt-20">
                    <a href="organizer_announcement.php" class="action-btn">Back to Announcements</a>
                    <a href="organizer_announcement_edit.php?id=<?php echo $announcement_id; ?>" class="action-btn">Edit Announcement</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
