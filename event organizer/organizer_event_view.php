<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


include '../connect.php';

// ========================================
// Check if user is an organizer
// ========================================
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}


$user_id = $_SESSION['user_id'];

// ========================================
// Get filter from URL (if any)
// ========================================
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
} else {
    $filter = 'all';
}

// ========================================
// Build SQL query based on filter
// ========================================
// Get current user's ID
$user_id = $_SESSION['user_id'];

$query = "SELECT DISTINCT e.event_id, e.event_title, e.event_date_time, e.event_status, e.event_location 
          FROM events e
          LEFT JOIN co_host ch ON e.event_id = ch.event_id
          WHERE (e.event_created_by = '$user_id' OR (ch.user_id = '$user_id' AND ch.cohost_status = 'active'))";


if ($filter != 'all') {

    $query = $query . " AND LOWER(e.event_status) = LOWER('$filter')";
}


$query = $query . " ORDER BY event_date_time DESC";


$result = mysqli_query($conn, $query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View All Events - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

    <div class="main-content">
    <div class="back-btn-container">
      <a href="organizer_event_management.php" class="action-btn"> Back</a>
    </div>    <div class="view-events-container">
      <h1>View All Events</h1>
      
      <!-- ============================== -->
      <!-- Filter Dropdown -->
      <!-- ============================== -->
      <div class="filter-section">
        <label for="status-filter">Filter by Status:</label>
        <select id="status-filter" class="form-control" onchange="window.location.href='organizer_event_view.php?filter=' + this.value">
          <option value="all" <?php echo ($filter == 'all') ? 'selected' : ''; ?>>All Events</option>
          <option value="draft" <?php echo ($filter == 'draft') ? 'selected' : ''; ?>>Draft</option>
          <option value="pending" <?php echo ($filter == 'pending') ? 'selected' : ''; ?>>Pending Approval</option>
          <option value="Approved" <?php echo ($filter == 'Approved') ? 'selected' : ''; ?>>Approved</option>
          <option value="Rejected" <?php echo ($filter == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
        </select>
      </div>
      
      <!-- ============================== -->
      <!-- Events List -->
      <!-- ============================== -->
      <div class="events-grid">
        <?php
        // Check if there are any events
        if ($result && mysqli_num_rows($result) > 0) {
            while ($event = mysqli_fetch_assoc($result)) {
                $event_id = $event['event_id'];
                

                $media_query = "SELECT event_media_file_path FROM event_media WHERE event_id = '$event_id' LIMIT 1";
                $media_result = mysqli_query($conn, $media_query);
                
                // Set default poster path
                $poster_path = '../images/default-poster.jpg';
                
                // Check event has a poster or not
                if ($media_result && mysqli_num_rows($media_result) > 0) {
                    $media = mysqli_fetch_assoc($media_result);
                    
                  
                    $db_path = $media['event_media_file_path'];
                    
                    $db_path = ltrim($db_path, '/');
                    $poster_path = '../' . $db_path;
                }
                
              
                $date_formatted = date('M d, Y g:i A', strtotime($event['event_date_time']));
                
                // Decide which color badge to show based on status
                $status_class = '';
                $event_status_lower = strtolower($event['event_status']);
                
                if ($event_status_lower == 'approved') {
                    $status_class = 'status-approved';  // Green
                } else if ($event_status_lower == 'pending') {
                    $status_class = 'status-pending';   // Yellow
                } else if ($event_status_lower == 'draft') {
                    $status_class = 'status-draft';     // Gray
                } else if ($event_status_lower == 'rejected') {
                    $status_class = 'status-rejected';  // Red
                }
                ?>
                
                <!-- Event Card -->
                <a href="organizer_event_detail.php?event_id=<?php echo $event_id; ?>" class="event-card-link">
                  <div class="event-card">
                    <div class="event-poster">
                      <img src="<?php echo htmlspecialchars($poster_path); ?>" alt="Event Poster">
                    </div>
                    
                    <div class="event-details">
                      <h3><?php echo htmlspecialchars($event['event_title']); ?></h3>
                      <p class="event-date">📅 <?php echo $date_formatted; ?></p>
                      <p class="event-location">📍 <?php echo htmlspecialchars($event['event_location']); ?></p>
                      <span class="event-status <?php echo $status_class; ?>">
                        <?php echo htmlspecialchars($event['event_status']); ?>
                      </span>
                    </div>
                  </div>
                </a>
                
                <?php
            }
        } else {
            // No events found
            echo '<div class="no-events">
                    <p>No events found.</p>
                    <a href="organizer_event_create.php" class="action-btn">Create New Event</a>
                  </div>';
        }
        ?>
      </div>
    </div>
  </div>
  
  <footer>
    © 2026 ReLife Hub
  </footer>

  <script>
  // Optional: Add any JavaScript for interactive features
  </script>
</body>
</html>
