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
$selected_event_id = $_GET['event_id'] ?? '';

// Fetch all events for dropdown
$events_query = "SELECT event_id, event_title FROM events WHERE event_created_by = ? ORDER BY event_date_time DESC";
$events_stmt = $conn->prepare($events_query);
$events_stmt->bind_param("s", $user_id);
$events_stmt->execute();
$events_result = $events_stmt->get_result();

// Fetch media based on filter
$media = [];
if (!empty($selected_event_id)) {
    // Show media for selected event only
    $media_sql = "SELECT em.*, e.event_title 
                  FROM event_media em
                  JOIN events e ON em.event_id = e.event_id
                  WHERE e.event_created_by = ? AND em.event_id = ?
                  ORDER BY em.event_media_id DESC";
    
    $media_stmt = $conn->prepare($media_sql);
    $media_stmt->bind_param("ss", $user_id, $selected_event_id);
} else {
    // Show all media from all events
    $media_sql = "SELECT em.*, e.event_title 
                  FROM event_media em
                  JOIN events e ON em.event_id = e.event_id
                  WHERE e.event_created_by = ?
                  ORDER BY em.event_media_id DESC";
    
    $media_stmt = $conn->prepare($media_sql);
    $media_stmt->bind_param("s", $user_id);
}

$media_stmt->execute();
$media_result = $media_stmt->get_result();

while ($row = $media_result->fetch_assoc()) {
    $media[] = $row;
}

$media_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Media Gallery - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
  <style>
    .media-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .media-item {
      background: #e9ecef;
      border-radius: 8px;
      overflow: hidden;
      aspect-ratio: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .media-item img,
    .media-item video {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .media-placeholder {
      font-size: 80px;
      color: #adb5bd;
    }
    
    .media-caption {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: rgba(0,0,0,0.7);
      color: white;
      padding: 10px;
      font-size: 12px;
      text-align: center;
    }
  </style>
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <div class="main-content">
    <div class="event-management-container">
      <div class="page-header">
        <h1 class="page-title">Event Media Gallery</h1>
        <a href="organizer_archive_showcase_hub.php" class="action-btn">Back</a>
      </div>

      <div class="management-section">
        <label for="event-select" style="display: inline-block; margin-right: 15px; font-size: 16px; font-weight: 500;">Select Event:</label>
        <select id="event-select" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; min-width: 300px; cursor: pointer;" onchange="window.location.href='organizer_event_media_gallery.php?event_id=' + this.value">
          <option value="">-- All Events --</option>
          <?php
          if ($events_result && $events_result->num_rows > 0) {
              while ($event = $events_result->fetch_assoc()) {
                  $selected = ($event['event_id'] == $selected_event_id) ? 'selected' : '';
                  echo '<option value="' . htmlspecialchars($event['event_id']) . '" ' . $selected . '>';
                  echo htmlspecialchars($event['event_title']);
                  echo '</option>';
              }
          }
          ?>
        </select>
      </div>

      <?php if (count($media) > 0): ?>
        <div class="media-grid">
          <?php foreach ($media as $item): 
            $media_path = '../' . ltrim($item['event_media_file_path'], '/');
            $file_extension = strtolower(pathinfo($media_path, PATHINFO_EXTENSION));
            $video_extensions = ['mp4', 'mov', 'avi', 'webm'];
            $is_video = in_array($file_extension, $video_extensions);
          ?>
            <div class="media-item">
              <?php if ($is_video): ?>
                <video>
                  <source src="<?= htmlspecialchars($media_path) ?>" type="video/<?= $file_extension ?>">
                </video>
              <?php elseif (file_exists($media_path)): ?>
                <img src="<?= htmlspecialchars($media_path) ?>" alt="Event Media">
              <?php else: ?>
                <div class="media-placeholder">🖼️</div>
              <?php endif; ?>
              
              <div class="media-caption">
                <?= htmlspecialchars($item['event_title']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="management-section" style="text-align: center; padding: 40px; color: #6c757d; font-size: 16px;">
          No media found.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
