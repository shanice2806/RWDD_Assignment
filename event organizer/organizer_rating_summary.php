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

// Get selected event
$selected_event_id = $_GET['event_id'] ?? '';

// Fetch all events for dropdown
$events_query = "SELECT event_id, event_title FROM events ORDER BY event_date_time DESC";
$events_result = $conn->query($events_query);

// Initialize variables
$event_data = null;
$avg_rating = 0;
$total_ratings = 0;
$rating_breakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$event_image = '';

if ($selected_event_id) {
    // Fetch event details
    $event_stmt = $conn->prepare("SELECT event_title FROM events WHERE event_id = ?");
    $event_stmt->bind_param("s", $selected_event_id);
    $event_stmt->execute();
    $event_data = $event_stmt->get_result()->fetch_assoc();
    $event_stmt->close();
    
    if ($event_data) {
        // Get event image from event_media table
        $media_stmt = $conn->prepare("SELECT event_media_file_path FROM event_media WHERE event_id = ? LIMIT 1");
        $media_stmt->bind_param("s", $selected_event_id);
        $media_stmt->execute();
        $media_result = $media_stmt->get_result();
        if ($media_result->num_rows > 0) {
            $db_path = $media_result->fetch_assoc()['event_media_file_path'] ?? '';
            // Remove leading slash and add ../
            $event_image = '../' . ltrim($db_path, '/');
        }
        $media_stmt->close();
        
        // Calculate average rating and total count
        $rating_stmt = $conn->prepare("SELECT AVG(feedback_rating) as avg_rating, COUNT(*) as total FROM feedback WHERE event_id = ?");
        $rating_stmt->bind_param("s", $selected_event_id);
        $rating_stmt->execute();
        $rating_result = $rating_stmt->get_result()->fetch_assoc();
        $avg_rating = round($rating_result['avg_rating'] ?? 0, 1);
        $total_ratings = $rating_result['total'] ?? 0;
        $rating_stmt->close();
        
        // Get rating breakdown
        $breakdown_stmt = $conn->prepare("SELECT feedback_rating, COUNT(*) as count FROM feedback WHERE event_id = ? GROUP BY feedback_rating");
        $breakdown_stmt->bind_param("s", $selected_event_id);
        $breakdown_stmt->execute();
        $breakdown_result = $breakdown_stmt->get_result();
        while ($row = $breakdown_result->fetch_assoc()) {
            $rating_breakdown[$row['feedback_rating']] = $row['count'];
        }
        $breakdown_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rating Summary - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <div class="main-content">
    <div class="event-management-container">
      <div class="page-header">
        <h1 class="page-title">Rating Summary</h1>
        <a href="organizer_collaboration_feedback_hub.php" class="action-btn">Back</a>
      </div>

      <div class="management-section">
        <label for="event-select" style="display: inline-block; margin-right: 15px; font-size: 16px; font-weight: 500;">Select Event:</label>
        <select id="event-select" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; min-width: 300px; cursor: pointer;" onchange="window.location.href='organizer_rating_summary.php?event_id=' + this.value">
          <option value="">-- Choose an Event --</option>
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

      <?php if ($selected_event_id && $event_data): ?>
        <div class="management-section" style="background: #e9ecef; padding: 40px; text-align: center; min-height: 300px; display: flex; align-items: center; justify-content: center;">
          <?php if (!empty($event_image) && file_exists($event_image)): ?>
            <img src="<?= htmlspecialchars($event_image) ?>" alt="Event Image" style="max-width: 100%; max-height: 400px; border-radius: 8px; object-fit: contain;">
          <?php else: ?>
            <div style="font-size: 120px; color: #adb5bd;">🖼️</div>
          <?php endif; ?>
        </div>

        <div class="management-section">
          <div style="display: flex; align-items: center; margin-bottom: 15px; font-size: 16px;">
            <label style="font-weight: 600; margin-right: 15px; min-width: 120px;">Overall Rating:</label>
            <div style="display: flex; gap: 5px; font-size: 24px;">
              <?php
              $full_stars = floor($avg_rating);
              $empty_stars = 5 - ceil($avg_rating);
              
              for ($i = 0; $i < $full_stars; $i++) {
                  echo '<span style="color: #ffc107;">★</span>';
              }
              
              if ($avg_rating - $full_stars >= 0.5) {
                  echo '<span style="color: #ffc107;">★</span>';
              } elseif ($avg_rating - $full_stars > 0) {
                  echo '<span style="color: #ffc107;">★</span>';
              }
              
              for ($i = 0; $i < $empty_stars; $i++) {
                  echo '<span style="color: #dee2e6;">★</span>';
              }
              ?>
            </div>
          </div>
          
          <div style="margin-bottom: 10px; font-size: 16px;">
            <label style="font-weight: 600; margin-right: 10px; min-width: 120px; display: inline-block;">Average Score:</label>
            <span><?= $avg_rating ?> / 5</span>
          </div>
          
          <div style="margin-bottom: 10px; font-size: 16px;">
            <label style="font-weight: 600; margin-right: 10px; min-width: 120px; display: inline-block;">Total Ratings:</label>
            <span><?= $total_ratings ?></span>
          </div>
        </div>

        <button class="btn-outline" onclick="toggleBreakdown()" style="margin-bottom: 20px;">View Detailed Breakdown</button>

        <div class="management-section" id="breakdown" style="display: none;">
          <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 20px;">Rating Distribution</h3>
          
          <?php
          for ($rating = 5; $rating >= 1; $rating--) {
              $count = $rating_breakdown[$rating];
              $percentage = $total_ratings > 0 ? ($count / $total_ratings) * 100 : 0;
              ?>
              <div style="display: flex; align-items: center; margin-bottom: 12px;">
                <div style="min-width: 80px; font-size: 14px;"><?= $rating ?> ★</div>
                <div style="flex: 1; background: #e9ecef; height: 24px; border-radius: 4px; margin: 0 15px; overflow: hidden;">
                  <div style="height: 100%; background: #ffc107; width: <?= $percentage ?>%; transition: width 0.5s ease;"></div>
                </div>
                <div style="min-width: 50px; text-align: right; font-size: 14px; font-weight: 500;"><?= $count ?></div>
              </div>
              <?php
          }
          ?>
        </div>
        
      <?php elseif ($selected_event_id): ?>
        <div class="management-section" style="text-align: center; padding: 40px; color: #6c757d; font-size: 16px;">
          No event found with the selected ID.
        </div>
      <?php else: ?>
        <div class="management-section" style="text-align: center; padding: 40px; color: #6c757d; font-size: 16px;">
          Please select an event to view its rating summary.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function toggleBreakdown() {
      const breakdown = document.getElementById('breakdown');
      if (breakdown.style.display === 'none') {
        breakdown.style.display = 'block';
        event.target.textContent = 'Hide Detailed Breakdown';
      } else {
        breakdown.style.display = 'none';
        event.target.textContent = 'View Detailed Breakdown';
      }
    }
  </script>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
