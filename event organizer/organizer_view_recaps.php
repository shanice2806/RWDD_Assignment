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
$event_id = $_GET['event_id'] ?? '';

// Fetch event details
$event_data = null;
if (!empty($event_id)) {
    $event_stmt = $conn->prepare("SELECT event_title, event_date_time, event_status FROM events WHERE event_id = ? AND event_created_by = ?");
    $event_stmt->bind_param("ss", $event_id, $user_id);
    $event_stmt->execute();
    $event_data = $event_stmt->get_result()->fetch_assoc();
    $event_stmt->close();
}

// Fetch recaps for this event
$recaps = [];
if (!empty($event_id)) {
    $recap_sql = "SELECT ea.*, 
                  em.event_media_file_path 
                  FROM event_archive ea
                  LEFT JOIN event_media em ON ea.event_archive_photos_videos = em.event_media_id
                  WHERE ea.event_id = ?
                  ORDER BY ea.event_archive_id DESC";
    
    $recap_stmt = $conn->prepare($recap_sql);
    $recap_stmt->bind_param("s", $event_id);
    $recap_stmt->execute();
    $recap_result = $recap_stmt->get_result();
    
    while ($row = $recap_result->fetch_assoc()) {
        $recaps[] = $row;
    }
    
    $recap_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Recaps - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
  <style>
    .recap-card {
      background: white;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    }
    
    .recap-summary {
      font-size: 15px;
      color: #333;
      line-height: 1.8;
      margin-bottom: 20px;
    }
    
    .recap-stats {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 6px;
      margin-bottom: 20px;
      border-left: 4px solid #3ba99c;
    }
    
    .recap-stats-title {
      font-weight: 600;
      color: #3ba99c;
      margin-bottom: 10px;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .recap-stats-content {
      font-size: 14px;
      color: #555;
      line-height: 1.6;
    }
    
    .recap-media-container {
      text-align: center;
      margin-top: 20px;
    }
    
    .recap-media-container img {
      max-width: 100%;
      max-height: 500px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .recap-media-container video {
      max-width: 100%;
      max-height: 500px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <div class="main-content">
    <div class="event-management-container">
      <div class="page-header">
        <h1 class="page-title">Event Recaps</h1>
        <a href="organizer_past_events.php" class="action-btn">Back</a>
      </div>

      <?php if ($event_data): ?>
        <div class="management-section">
          <h2 style="font-size: 20px; margin-bottom: 10px; color: #1e3a34;"><?= htmlspecialchars($event_data['event_title']) ?></h2>
          <p style="color: #666; font-size: 14px;">Date: <?= date('F d, Y', strtotime($event_data['event_date_time'])) ?></p>
        </div>

        <?php if (count($recaps) > 0): ?>
          <?php foreach ($recaps as $recap): ?>
            <div class="recap-card">
              <div class="recap-summary">
                <?= nl2br(htmlspecialchars($recap['event_archive_summary'])) ?>
              </div>

              <?php if (!empty($recap['event_archive_impact_stats'])): ?>
                <div class="recap-stats">
                  <div class="recap-stats-title">Impact Statistics</div>
                  <div class="recap-stats-content">
                    <?= nl2br(htmlspecialchars($recap['event_archive_impact_stats'])) ?>
                  </div>
                </div>
              <?php endif; ?>

              <?php if (!empty($recap['event_media_file_path'])): 
                $media_path = '../' . ltrim($recap['event_media_file_path'], '/');
                $file_extension = strtolower(pathinfo($media_path, PATHINFO_EXTENSION));
                $video_extensions = ['mp4', 'mov', 'avi', 'webm'];
              ?>
                <div class="recap-media-container">
                  <?php if (in_array($file_extension, $video_extensions)): ?>
                    <video controls>
                      <source src="<?= htmlspecialchars($media_path) ?>" type="video/<?= $file_extension ?>">
                      Your browser does not support the video tag.
                    </video>
                  <?php else: ?>
                    <img src="<?= htmlspecialchars($media_path) ?>" alt="Event Recap Media">
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="management-section" style="text-align: center; padding: 40px; color: #6c757d; font-size: 16px;">
            No recaps posted for this event yet.
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="management-section" style="text-align: center; padding: 40px; color: #721c24; font-size: 16px; background: #f8d7da; border: 1px solid #f5c6cb;">
          Event not found or you don't have permission to view it.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
