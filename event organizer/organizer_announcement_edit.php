<?php
// ========================================
//  Start session and check login
// ========================================
session_start();

if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}


include '../connect.php';

$user_id = $_SESSION['user_id'];

// ========================================
//  Get announcement ID
// ========================================
if (!isset($_GET['id'])) {
    header("Location: organizer_announcement.php");
    exit();
}

$announcement_id = $_GET['id'];

// ========================================
//  Get announcement and verify ownership
// ========================================
$query = "SELECT * FROM announcements WHERE announcement_id = '$announcement_id' AND created_by = '$user_id'";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    echo "<script>alert('Announcement not found or you do not have permission to edit it.'); window.location.href='organizer_announcement.php';</script>";
    exit();
}

$announcement = $result->fetch_assoc();

// ========================================
//  Handle form submission
// ========================================
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_announcement'])) {
    $title = $_POST['title'];
    $message = $_POST['message'];
    $end_date_input = $_POST['end_date'];
    
    // Validate inputs
    if (empty($title) || empty($message) || empty($end_date_input)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Convert end_date to MySQL format
        $end_date = date('Y-m-d H:i:s', strtotime($end_date_input));
        
        $update_query = "UPDATE announcements SET title = '$title', message = '$message', end_date = '$end_date' 
                        WHERE announcement_id = '$announcement_id'";
        
        if ($conn->query($update_query)) {
            echo "<script>alert('Announcement updated successfully!'); window.location.href='organizer_announcement.php';</script>";
            exit();
        } else {
            $error_message = "Failed to update announcement. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Announcement</title>
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
                <h1>Edit Announcement</h1>
                <a href="organizer_announcement.php" class="action-btn">Back</a>
            </div>
            
            <!-- Form container -->
            <div class="container container-narrow">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <form method="POST" class="announcement-form">
                    <!-- Title -->
                    <div class="form-group">
                        <label class="form-label">Title: <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($announcement['title']); ?>">
                    </div>
                    
                    <!-- End Date -->
                    <div class="form-group">
                        <label class="form-label">End Date: <span class="required">*</span></label>
                        <input type="datetime-local" name="end_date" class="form-control" required value="<?php echo date('Y-m-d\TH:i', strtotime($announcement['end_date'])); ?>">
                        <small class="form-text">Select when this announcement should expire</small>
                    </div>
                    
                    <!-- Message -->
                    <div class="form-group">
                        <label class="form-label">Message: <span class="required">*</span></label>
                        <textarea name="message" class="form-control" rows="8" required><?php echo htmlspecialchars($announcement['message']); ?></textarea>
                    </div>
                    
                    <!-- Info box -->
                    <div class="info-box mb-20">
                        <p><strong>Start Date:</strong> <?php echo date('d/m/Y H:i', strtotime($announcement['start_date'])); ?></p>
                        <p><strong>Created At:</strong> <?php echo date('d/m/Y H:i', strtotime($announcement['created_at'])); ?></p>
                        <p class="text-small mt-10">Note: Start date cannot be changed as it represents when the announcement was first posted.</p>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="form-actions flex gap-15 flex-center">
                        <a href="organizer_announcement.php" class="action-btn">Cancel</a>
                        <button type="submit" name="update_announcement" class="action-btn">Update Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
