<?php
session_start();


if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

include '../connect.php';

$user_id = $_SESSION['user_id'];


$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_announcement'])) {
    $title = $_POST['title'];
    $message = $_POST['message'];
    $end_date_input = $_POST['end_date'];
    
    // Validate inputs
    if (empty($title) || empty($message) || empty($end_date_input)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $id_query = "SELECT announcement_id FROM announcements ORDER BY announcement_id DESC LIMIT 1";
        $id_result = $conn->query($id_query);
        
        if ($id_result->num_rows > 0) {
            $last_id = $id_result->fetch_assoc()['announcement_id'];
            $number = intval(substr($last_id, 2)) + 1;
            $new_id = 'a_' . str_pad($number, 3, '0', STR_PAD_LEFT);
        } else {
            $new_id = 'a_001';
        }
        
        $start_date = date('Y-m-d H:i:s');
        $created_at = date('Y-m-d H:i:s');
        
        $end_date = date('Y-m-d H:i:s', strtotime($end_date_input));
        
        $insert_query = "INSERT INTO announcements (announcement_id, title, message, start_date, end_date, is_active, created_at, created_by) 
                        VALUES ('$new_id', '$title', '$message', '$start_date', '$end_date', 1, '$created_at', '$user_id')";
        
        if ($conn->query($insert_query)) {
            echo "<script>alert('Announcement posted successfully!'); window.location.href='organizer_announcement.php';</script>";
            exit();
        } else {
            $error_message = "Failed to post announcement. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Announcement</title>
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
    </div>            <!-- Page header -->
            <div class="page-header flex-between">
                <h1>Post Announcement</h1>
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
                        <input type="text" name="title" class="form-control" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>
                    
                    <!-- End Date -->
                    <div class="form-group">
                        <label class="form-label">End Date: <span class="required">*</span></label>
                        <input type="datetime-local" name="end_date" class="form-control" required value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : ''; ?>">
                        <small class="form-text">Select when this announcement should expire</small>
                    </div>
                    
                    <!-- Message -->
                    <div class="form-group">
                        <label class="form-label">Message: <span class="required">*</span></label>
                        <textarea name="message" class="form-control" rows="8" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="form-actions flex gap-15 flex-center">
                        <button type="button" class="action-btn" onclick="previewAnnouncement()">Preview</button>
                        <button type="submit" name="post_announcement" class="action-btn">Post Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div id="previewModal" class="poster-modal" onclick="closePreview()">
        <div class="modal-preview-content" onclick="event.stopPropagation()">
            <span class="modal-close" onclick="closePreview()">&times;</span>
            <div class="preview-container">
                <h2>Announcement Preview</h2>
                <div class="preview-box">
                    <h3 id="previewTitle"></h3>
                    <p><strong>Expires:</strong> <span id="previewEndDate"></span></p>
                    <div class="preview-message" id="previewMessage"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function previewAnnouncement() {
            // Get form values
            var title = document.querySelector('input[name="title"]').value;
            var endDate = document.querySelector('input[name="end_date"]').value;
            var message = document.querySelector('textarea[name="message"]').value;
            
            // Check if fields are filled
            if (!title || !endDate || !message) {
                alert('Please fill in all fields before previewing.');
                return;
            }
            
            // Format end date
            var dateObj = new Date(endDate);
            var formattedDate = dateObj.toLocaleDateString('en-GB') + ' ' + dateObj.toLocaleTimeString('en-GB', {hour: '2-digit', minute: '2-digit'});
            
            // Set preview content
            document.getElementById('previewTitle').textContent = title;
            document.getElementById('previewEndDate').textContent = formattedDate;
            document.getElementById('previewMessage').textContent = message;
            
            // Show modal
            document.getElementById('previewModal').style.display = 'block';
        }
        
        function closePreview() {
            document.getElementById('previewModal').style.display = 'none';
        }
    </script>
</body>
</html>
