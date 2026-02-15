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
// Handle attendance submission
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $event_id = $_POST['event_id'];
    $attendance_data = $_POST['attendance'];
    
    foreach ($attendance_data as $user_id_participant => $status) {
        if ($status === 'Present') {
            // Check if attendance already exists
            $check_query = "SELECT attendance_id FROM attendance WHERE event_id = '$event_id' AND user_id = '$user_id_participant'";
            $check_result = $conn->query($check_query);
            
            if ($check_result->num_rows == 0) {
                // Insert new attendance record
                $current_time = date('Y-m-d H:i:s');
                $insert_query = "INSERT INTO attendance (event_id, user_id, attendance_check_in_time, attendance_method) VALUES 
                ('$event_id', '$user_id_participant', '$current_time', 'Manual')";
                $conn->query($insert_query);
            }
        }
    }
    
    $success_message = "Attendance saved successfully!";
}

// Get selected event
$selected_event = null;
$participants = [];

if (isset($_POST['event_id']) || isset($_GET['event_id'])) {
    $event_id = isset($_POST['event_id']) ? $_POST['event_id'] : $_GET['event_id'];
    
    $event_query = "SELECT event_id, event_title FROM events WHERE event_id = '$event_id' AND (event_created_by = '$user_id' 
    OR event_id IN (SELECT event_id FROM co_host WHERE user_id = '$user_id' AND cohost_status = 'active'))";
    $event_result = $conn->query($event_query);
    
    if ($event_result->num_rows > 0) {
        $selected_event = $event_result->fetch_assoc();
        
        // Get registered participants for this event
        $participants_query = "SELECT r.user_id, u.name, 
                              (SELECT attendance_id FROM attendance WHERE event_id = '$event_id' AND user_id = r.user_id LIMIT 1) AS attended
                              FROM registrations r 
                              JOIN users u ON r.user_id = u.user_id 
                              WHERE r.event_id = '$event_id' AND r.registration_status = 'Registered'
                              ORDER BY u.name";
        $participants_result = $conn->query($participants_query);
        
        while ($participant = $participants_result->fetch_assoc()) {
            $participants[] = $participant;
        }
    }
}

// Get events created by this organizer OR where they are co-host
$events_query = "SELECT DISTINCT e.event_id, e.event_title 
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
  <title>Manual Attendance - ReLife Hub</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

    <div class="main-content">
    <div class="back-btn-container">
      <a href="organizer_registration_attendance_hub.php" class="action-btn"> Back</a>
    </div>    <main class="dashboard">
      
      <div class="manual-attendance-container">
        <a href="organizer_registration_attendance_hub.php" class="action-btn">← Back</a>
        
        <h1>Manual Attendance</h1>

        <?php if ($success_message): ?>
          <div class="alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
          <div class="alert-error"><?= $error_message ?></div>
        <?php endif; ?>

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

          <?php if ($selected_event && count($participants) > 0): ?>
            <!-- Participant List -->
            <h2>Participant List</h2>
            
            <table class="participant-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>ID</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($participants as $participant): ?>
                  <tr>
                    <td><?= htmlspecialchars($participant['name']) ?></td>
                    <td><?= htmlspecialchars($participant['user_id']) ?></td>
                    <td>
                      <label class="attendance-radio">
                        <input type="radio" name="attendance[<?= $participant['user_id'] ?>]" value="Present" 
                               <?= $participant['attended'] ? 'checked' : '' ?>>
                        Present
                      </label>
                      <label class="attendance-radio">
                        <input type="radio" name="attendance[<?= $participant['user_id'] ?>]" value="Absent" 
                               <?= !$participant['attended'] ? 'checked' : '' ?>>
                        Absent
                      </label>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <!-- Submit Button -->
            <button type="submit" name="save_attendance" class="action-btn">Save Attendance</button>

          <?php elseif ($selected_event && count($participants) == 0): ?>
            <p class="no-data">No registered participants for this event.</p>
          <?php endif; ?>
        </form>

        <?php if ($selected_event): ?>
          <!-- Action Buttons -->
          <div class="action-buttons">
            <a href="organizer_attendance_code.php?event_id=<?= $selected_event['event_id'] ?>" class="action-btn">Show Code</a>
            <a href="organizer_attendance_view.php?event_id=<?= $selected_event['event_id'] ?>" class="action-btn">View Attendance List</a>
          </div>
        <?php endif; ?>
      </div>

    </main>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
