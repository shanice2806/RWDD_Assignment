<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to database
include '../connect.php';

// ========================================
// Check if user is an organizer
// ========================================
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    // Not an organizer? Send to login page
    header("Location: ../login/login.php");
    exit();
}

// Get the logged in user's ID
$user_id = $_SESSION['user_id'];

// ========================================
// Get event ID from URL
// ========================================
// Example: organizer_event_detail.php?event_id=evt_001
if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];
} else {
    // No event ID provided? Go back to event list
    header("Location: organizer_event_view.php");
    exit();
}

// ========================================
// Get event details from database
// ========================================
$query = "SELECT * FROM events WHERE event_id = '$event_id' AND event_created_by = '$user_id' LIMIT 1";
$result = mysqli_query($conn, $query);

// Check if event exists
if (!$result || mysqli_num_rows($result) == 0) {
    // Event not found or doesn't belong to this organizer
    echo "<script>alert('Event not found.'); window.location.href='organizer_event_view.php';</script>";
    exit();
}

// Get event data
$event = mysqli_fetch_assoc($result);

// ========================================
// Get event poster/media
// ========================================
$media_query = "SELECT * FROM event_media WHERE event_id = '$event_id' AND event_media_is_hidden = 0";
$media_result = mysqli_query($conn, $media_query);

// Format date and time nicely
$date_formatted = date('F d, Y', strtotime($event['event_date_time']));
$time_formatted = date('g:i A', strtotime($event['event_date_time']));

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($event['event_title']); ?> - Event Details</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

    <div class="main-content">
    <div class="back-btn-container">
      <a href="organizer_archive_showcase_hub.php" class="action-btn"> Back</a>
    </div>    <div class="event-detail-container">
      
      <!-- ============================== -->
      <!-- Event Title -->
      <!-- ============================== -->
      <h1><?php echo htmlspecialchars($event['event_title']); ?></h1>
      
      <!-- ============================== -->
      <!-- Event Poster and Details Section -->
      <!-- ============================== -->
      <div class="detail-grid">
        
        <!-- Left Side: Event Poster -->
        <div class="detail-poster-section">
          <h3>Event Poster</h3>
          <?php
          // Check if event has poster
          if ($media_result && mysqli_num_rows($media_result) > 0) {
              $media = mysqli_fetch_assoc($media_result);
              $db_path = ltrim($media['event_media_file_path'], '/');
              $poster_path = '../' . $db_path;
              ?>
              <div class="detail-poster" onclick="openPosterModal('<?php echo htmlspecialchars($poster_path); ?>')">
                <img src="<?php echo htmlspecialchars($poster_path); ?>" alt="Event Poster">
              </div>
              <?php
          } else {
              ?>
              <div class="detail-poster no-poster">
                <p>No poster uploaded</p>
              </div>
              <?php
          }
          ?>
        </div>
        
        <!-- Right Side: Event Details -->
        <div class="detail-info-section">
          <h3>Event Details</h3>
          
          <div class="detail-item">
            <strong>Event Type:</strong>
            <p><?php echo htmlspecialchars($event['event_type']); ?></p>
          </div>
          
          <div class="detail-item">
            <strong>Date:</strong>
            <p>📅 <?php echo $date_formatted; ?></p>
          </div>
          
          <div class="detail-item">
            <strong>Time:</strong>
            <p>🕐 <?php echo $time_formatted; ?></p>
          </div>
          
          <div class="detail-item">
            <strong>Location:</strong>
            <p>📍 <?php echo htmlspecialchars($event['event_location']); ?></p>
          </div>
          
          <div class="detail-item">
            <strong>Status:</strong>
            <p>
              <?php
              $status_class = '';
              $event_status_lower = strtolower($event['event_status']);
              
              if ($event_status_lower == 'approved') {
                  $status_class = 'status-approved';
              } else if ($event_status_lower == 'pending') {
                  $status_class = 'status-pending';
              } else if ($event_status_lower == 'draft') {
                  $status_class = 'status-draft';
              } else if ($event_status_lower == 'rejected') {
                  $status_class = 'status-rejected';
              }
              ?>
              <span class="event-status <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($event['event_status']); ?>
              </span>
            </p>
          </div>
          
          <div class="detail-item">
            <strong>Registration Status:</strong>
            <p><?php echo htmlspecialchars($event['registration_status']); ?></p>
          </div>
          
          <div class="detail-item">
            <strong>Description:</strong>
            <p><?php echo nl2br(htmlspecialchars($event['event_description'])); ?></p>
          </div>
        </div>
      </div>
      
      <!-- ============================== -->
      <!-- Action Buttons -->
      <!-- ============================== -->
      <div class="detail-actions">
        <a href="organizer_event_edit.php?id=<?php echo $event_id; ?>" class="action-btn">Edit Event</a>
        <a href="organizer_event_archive.php?id=<?php echo $event_id; ?>" class="action-btn">Archive Event</a>
        <a href="organizer_event_view.php" class="action-btn btn-outline">Back to Events</a>
      </div>
      
    </div>
  </div>
  
  <!-- ============================== -->
  <!-- Poster Modal (Full Screen View) -->
  <!-- ============================== -->
  <div id="posterModal" class="poster-modal" onclick="closePosterModal()">
    <span class="modal-close">&times;</span>
    <img class="modal-content" id="modalImage">
  </div>
  
  <footer>
    © 2026 ReLife Hub
  </footer>
  
  <script>
  // Open poster in full screen
  function openPosterModal(imagePath) {
    var modal = document.getElementById("posterModal");
    var modalImg = document.getElementById("modalImage");
    
    // Show modal
    modal.style.display = "block";
  
    modalImg.src = imagePath;
  }
  
  function closePosterModal() {
    var modal = document.getElementById("posterModal");
    modal.style.display = "none";
  }
  </script>
</body>
</html>
