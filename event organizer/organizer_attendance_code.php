<?php
// Turn on error messages (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// Check if user is an organizer
// ========================================
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

// Connect to database
include '../connect.php';

$user_id = $_SESSION['user_id'];

// Get selected event
$selected_event = null;
$attendance_code = "";

if (isset($_POST['event_id'])) {
    $event_id = $_POST['event_id'];
    $event_query = "SELECT event_id, event_title, event_attendance_code FROM events WHERE event_id = '$event_id' AND (event_created_by = '$user_id' OR event_id IN (SELECT event_id FROM co_host WHERE user_id = '$user_id' AND cohost_status = 'active'))";
    $event_result = $conn->query($event_query);
    
    if ($event_result->num_rows > 0) {
        $selected_event = $event_result->fetch_assoc();
        $attendance_code = $selected_event['event_attendance_code'];
    }
}

// Get events created by this organizer OR where they are co-host
$events_query = "SELECT DISTINCT e.event_id, e.event_title, e.event_attendance_code 
                 FROM events e
                 LEFT JOIN co_host ch ON e.event_id = ch.event_id
                 WHERE (e.event_created_by = '$user_id' OR (ch.user_id = '$user_id' AND ch.cohost_status = 'active'))
                 AND LOWER(e.event_status) = 'approved' 
                 ORDER BY e.event_date_time DESC";
$events_result = $conn->query($events_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Code Attendance - ReLife Hub</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

    <div class="main-content">
    <div class="back-btn-container">
      <button onclick="history.back()" class="action-btn"> Back</button>
    </div>    <main class="dashboard">
      
      <div class="attendance-code-container">
        
        <h1>Code Attendance</h1>

        <form method="POST" class="attendance-form">
          
          <!-- Event Selection -->
          <div class="form-group">
            <label for="event_id">Select Event:</label>
            <select name="event_id" id="event_id" required onchange="this.form.submit()">
              <option value="">-- Choose an event --</option>
              <?php
              if ($events_result && $events_result->num_rows > 0) {
                while ($event = $events_result->fetch_assoc()) {
                  $selected = ($selected_event && $selected_event['event_id'] == $event['event_id']) ? 'selected' : '';
                  echo "<option value='" . $event['event_id'] . "' $selected>" . $event['event_title'] . "</option>";
                }
              } else {
                echo "<option value=''>No events found</option>";
              }
              ?>
            </select>
          </div>

          <?php if ($selected_event): ?>
            <!-- Attendance Code Display -->
            <div class="code-display-area">
              <div class="attendance-code-box">
                <?= htmlspecialchars($attendance_code) ?>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
              <a href="organizer_attendance_manual.php?event_id=<?= $selected_event['event_id'] ?>" class="action-btn">Take Attendance Manually</a>
              <a href="organizer_attendance_view.php?event_id=<?= $selected_event['event_id'] ?>" class="action-btn">View Attendance List</a>
            </div>
          <?php endif; ?>
        </form>
      </div>

    </main>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
