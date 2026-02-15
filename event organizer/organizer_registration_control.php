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
$success_message = "";
$error_message = "";

// ========================================
// Handle form submission
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_status'])) {
    $event_id = $_POST['event_id'];
    $new_status = $_POST['registration_status'];
    
    // Update registration status
    $query = "UPDATE events SET registration_status = '$new_status' WHERE event_id = '$event_id' AND
     (event_created_by = '$user_id' OR event_id IN (SELECT event_id FROM co_host WHERE user_id = '$user_id' AND cohost_status = 'active'))";
    
    if ($conn->query($query)) {
        $success_message = "Registration status updated to $new_status!";
    } else {
        $error_message = "Failed to update registration status.";
    }
}

// Get selected event
$selected_event = null;
$current_status = "Open";

if (isset($_POST['event_id'])) {
    $event_id = $_POST['event_id'];
    $event_query = "SELECT event_id, event_title, registration_status FROM events WHERE event_id = '$event_id' AND (event_created_by = '$user_id' 
    OR event_id IN (SELECT event_id FROM co_host WHERE user_id = '$user_id' AND cohost_status = 'active'))";
    $event_result = $conn->query($event_query);
    
    if ($event_result->num_rows > 0) {
        $selected_event = $event_result->fetch_assoc();
        $current_status = $selected_event['registration_status'];
        if (empty($current_status)) {
            $current_status = "Open";
        }
    }
}

// ========================================
// Get events created by this organizer OR where they are co-host
// ========================================
$events_query = "SELECT DISTINCT e.event_id, e.event_title, e.registration_status 
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
  <title>Registration Control - ReLife Hub</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

    <div class="main-content">
    <div class="back-btn-container">
      <a href="organizer_registration_attendance_hub.php" class="action-btn"> Back</a>
    </div>    <main class="dashboard">
      
      <div class="registration-control-container">
        
        <h1>Registration Control</h1>

        <?php if ($success_message): ?>
          <div class="alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
          <div class="alert-error"><?= $error_message ?></div>
        <?php endif; ?>

        <form method="POST" class="reg-control-form">
          
          <!-- Event Selection -->
          <div class="form-group">
            <label for="event_id">Select Event:</label>
            <select name="event_id" id="event_id" required onchange="this.form.submit()">
              <option value="">-- Choose an event --</option>
              <?php
              if ($events_result && $events_result->num_rows > 0) {
                while ($event = $events_result->fetch_assoc()) {
                  $selected = ($selected_event && $selected_event['event_id'] == $event['event_id']) ? 'selected' : '';
                  $status_label = $event['registration_status'] ? $event['registration_status'] : 'Open';
                  echo "<option value='" . $event['event_id'] . "' $selected>" . $event['event_title'] . " - " . $status_label . "</option>";
                }
              } else {
                echo "<option value=''>No events found</option>";
              }
              ?>
            </select>
          </div>

          <?php if ($selected_event): ?>
            <!-- Registration Status Buttons -->
            <div class="form-group">
              <label>Registration Status:</label>
              <div class="status-buttons">
                <button type="button" class="status-btn <?= $current_status === 'Open' ? 'active' : '' ?>" onclick="selectStatus('Open')">Open</button>
                <button type="button" class="status-btn status-close <?= $current_status === 'Closed' ? 'active' : '' ?>" onclick="selectStatus('Closed')">Close</button>
              </div>
            </div>

            <input type="hidden" name="registration_status" id="registration_status" value="<?= $current_status ?>">

            <!-- Current Status Display -->
            <div class="form-group">
              <label>Registration Status:</label>
              <div class="status-display" id="statusDisplay"><?= $current_status ?></div>
            </div>

            <!-- Submit Button -->
            <button type="submit" name="save_status" class="action-btn">Save Changes</button>
          <?php endif; ?>
        </form>
      </div>

    </main>
  </div>

  <?php include 'organizer_footer.php'; ?>

  <script>
    function selectStatus(status) {
      document.getElementById('registration_status').value = status;
      document.getElementById('statusDisplay').textContent = status;
      
      var buttons = document.querySelectorAll('.status-btn');
      buttons[0].classList.remove('active');
      buttons[1].classList.remove('active');
      
      if (status === 'Open') {
        buttons[0].classList.add('active');
      } else {
        buttons[1].classList.add('active');
      }
    }
  </script>
</body>
</html>
