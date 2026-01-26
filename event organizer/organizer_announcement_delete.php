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
    echo "<script>alert('Announcement not found or you do not have permission to delete it.'); window.location.href='organizer_announcement.php';</script>";
    exit();
}

$announcement = $result->fetch_assoc();

// ========================================
// STEP 4: Handle delete confirmation
// ========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    $delete_query = "DELETE FROM announcements WHERE announcement_id = '$announcement_id'";
    
    if ($conn->query($delete_query)) {
        echo "<script>alert('Announcement deleted successfully!'); window.location.href='organizer_announcement.php';</script>";
        exit();
    } else {
        $error_message = "Failed to delete announcement. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Announcement</title>
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
                <h1>Delete Announcement</h1>
            </div>
            
            <!-- Delete confirmation container -->
            <div class="container container-narrow">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <div class="confirmation-box" style="background: #fff0f0; border-color: #ff4444;">
                    <div class="confirmation-icon">🗑️</div>
                    <h3>Are you sure you want to delete this announcement?</h3>
                    
                    <div class="confirmation-message">
                        <p>This action cannot be undone. The announcement will be permanently deleted.</p>
                    </div>
                    
                    <div class="event-summary">
                        <p><strong>Title:</strong> <?php echo htmlspecialchars($announcement['title']); ?></p>
                        <p><strong>Created:</strong> <?php echo date('d/m/Y H:i', strtotime($announcement['created_at'])); ?></p>
                        <p><strong>End Date:</strong> <?php echo date('d/m/Y H:i', strtotime($announcement['end_date'])); ?></p>
                        <p><strong>Message Preview:</strong></p>
                        <p class="text-small"><?php echo substr(htmlspecialchars($announcement['message']), 0, 150) . (strlen($announcement['message']) > 150 ? '...' : ''); ?></p>
                    </div>
                    
                    <form method="POST" class="confirmation-form">
                        <div class="flex gap-15 flex-center">
                            <a href="organizer_announcement.php" class="action-btn">Cancel</a>
                            <button type="submit" name="confirm_delete" class="action-btn" style="background: #e53e3e; border-color: #e53e3e;">Delete Announcement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
