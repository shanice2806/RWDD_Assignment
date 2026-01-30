<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protect access
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

include '../connect.php';

$user_id = $_SESSION["user_id"];
$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_recap'])) {
    $event_id = $_POST['event_id'] ?? '';
    $recap_title = $_POST['recap_title'] ?? '';
    $recap_summary = $_POST['recap_summary'] ?? '';
    $impact_stats = $_POST['impact_stats'] ?? '';
    
    if (!empty($event_id) && !empty($recap_summary)) {
        // Generate archive ID
        $archive_query = "SELECT event_archive_id FROM event_archive ORDER BY event_archive_id DESC LIMIT 1";
        $archive_result = $conn->query($archive_query);
        
        if ($archive_result && $archive_result->num_rows > 0) {
            $last_archive = $archive_result->fetch_assoc();
            $last_id = $last_archive['event_archive_id'];
            $archive_number = intval(substr($last_id, 4)) + 1;
            $event_archive_id = 'att_' . str_pad($archive_number, 3, '0', STR_PAD_LEFT);
        } else {
            $event_archive_id = 'att_001';
        }
        
        // Handle media upload
        $media_id = null;
        if (isset($_FILES['recap_media']) && $_FILES['recap_media']['error'] == UPLOAD_ERR_OK) {
            $file = $_FILES['recap_media'];
            $upload_folder = '../images/events/';
            
            if (!file_exists($upload_folder)) {
                mkdir($upload_folder, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi');
            
            if (in_array($file_extension, $allowed_types)) {
                if ($file['size'] <= 10 * 1024 * 1024) { // 10MB limit
                    // Generate media ID
                    $media_query = "SELECT event_media_id FROM event_media ORDER BY event_media_id DESC LIMIT 1";
                    $media_result = $conn->query($media_query);
                    
                    if ($media_result && $media_result->num_rows > 0) {
                        $last_media = $media_result->fetch_assoc();
                        $last_media_id = $last_media['event_media_id'];
                        $media_number = intval(substr($last_media_id, 4)) + 1;
                        $media_id = 'emd_' . str_pad($media_number, 3, '0', STR_PAD_LEFT);
                    } else {
                        $media_id = 'emd_001';
                    }
                    
                    $new_filename = $event_id . '_recap_' . time() . '.' . $file_extension;
                    $full_path = $upload_folder . $new_filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $full_path)) {
                        // Save media to database
                        $media_insert = "INSERT INTO event_media (event_media_id, event_id, event_media_file_path, event_media_uploaded_by, event_media_is_hidden) 
                                       VALUES (?, ?, ?, ?, 1)";
                        $media_stmt = $conn->prepare($media_insert);
                        $file_path = 'images/events/' . $new_filename;
                        $media_stmt->bind_param("ssss", $media_id, $event_id, $file_path, $user_id);
                        $media_stmt->execute();
                        $media_stmt->close();
                    } else {
                        $error_message = "Failed to upload media file.";
                    }
                } else {
                    $error_message = "File size exceeds 10MB limit.";
                }
            } else {
                $error_message = "Invalid file type. Only images and videos are allowed.";
            }
        }
        
        // Insert archive record if no upload errors
        if (empty($error_message)) {
            $insert_archive = "INSERT INTO event_archive (event_archive_id, event_id, event_archive_summary, event_archive_impact_stats, event_archive_photos_videos) 
                             VALUES (?, ?, ?, ?, ?)";
            $archive_stmt = $conn->prepare($insert_archive);
            $archive_stmt->bind_param("sssss", $event_archive_id, $event_id, $recap_summary, $impact_stats, $media_id);
            
            if ($archive_stmt->execute()) {
                $success_message = "Event recap posted successfully!";
                header("refresh:2;url=organizer_archive_showcase_hub.php");
            } else {
                $error_message = "Failed to post recap.";
            }
            $archive_stmt->close();
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Fetch events for dropdown
$events_query = "SELECT event_id, event_title FROM events WHERE event_created_by = ? ORDER BY event_date_time DESC";
$events_stmt = $conn->prepare($events_query);
$events_stmt->bind_param("s", $user_id);
$events_stmt->execute();
$events_result = $events_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Post Recap Summary - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <div class="main-content">
    <div class="event-management-container">
      <div class="page-header">
        <h1 class="page-title">Post Recap Summary</h1>
        <a href="organizer_archive_showcase_hub.php" class="action-btn">Back</a>
      </div>

      <?php if ($success_message): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
          <?= htmlspecialchars($success_message) ?>
        </div>
      <?php endif; ?>

      <?php if ($error_message): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
          <?= htmlspecialchars($error_message) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" enctype="multipart/form-data">
        <div class="management-section">
          <label for="event_id" style="display: block; margin-bottom: 8px; font-weight: 500;">Select Event:</label>
          <select name="event_id" id="event_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
            <option value="">-- Choose an Event --</option>
            <?php
            if ($events_result && $events_result->num_rows > 0) {
                while ($event = $events_result->fetch_assoc()) {
                    echo '<option value="' . htmlspecialchars($event['event_id']) . '">';
                    echo htmlspecialchars($event['event_title']);
                    echo '</option>';
                }
            }
            ?>
          </select>
        </div>

        <div class="management-section">
          <label for="recap_title" style="display: block; margin-bottom: 8px; font-weight: 500;">Recap Title:</label>
          <input type="text" name="recap_title" id="recap_title" 
                 style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;"
                 placeholder="e.g., Beach Cleanup 2025 Recap">
        </div>

        <div class="management-section">
          <label for="recap_summary" style="display: block; margin-bottom: 8px; font-weight: 500;">Summary:</label>
          <textarea name="recap_summary" id="recap_summary" rows="6" required
                    style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; font-family: inherit; resize: vertical;"
                    placeholder="Describe what happened at the event, key highlights, and participant contributions..."></textarea>
        </div>

        <div class="management-section">
          <label for="impact_stats" style="display: block; margin-bottom: 8px; font-weight: 500;">Impact Statistics (Optional):</label>
          <textarea name="impact_stats" id="impact_stats" rows="3"
                    style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; font-family: inherit; resize: vertical;"
                    placeholder="e.g., 50kg plastics collected, 20kg paper collected"></textarea>
        </div>

        <div class="management-section">
          <label for="recap_media" style="display: block; margin-bottom: 8px; font-weight: 500;">Upload Media:</label>
          <div style="background: #e9ecef; padding: 60px 20px; text-align: center; border-radius: 8px; border: 2px dashed #adb5bd;">
            <div style="font-size: 48px; margin-bottom: 10px; color: #6c757d;">🖼️</div>
            <input type="file" name="recap_media" id="recap_media" accept="image/*,video/*" 
                   style="display: block; margin: 15px auto 0; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: white; cursor: pointer;">
            <p style="margin-top: 10px; font-size: 13px; color: #6c757d;">Max file size: 10MB. Supported: JPG, PNG, GIF, MP4, MOV, AVI</p>
          </div>
        </div>

        <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
          <button type="button" class="btn-outline" style="padding: 14px 32px; font-size: 15px;">Preview</button>
          <button type="submit" name="post_recap" class="action-btn" style="padding: 14px 32px; font-size: 15px;">Post Recap</button>
        </div>
      </form>
    </div>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
