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
$attendance_list = [];

if (isset($_POST['event_id']) || isset($_GET['event_id'])) {
    $event_id = isset($_POST['event_id']) ? $_POST['event_id'] : $_GET['event_id'];
    
    $event_query = "SELECT event_id, event_title FROM events WHERE event_id = '$event_id' AND (event_created_by = '$user_id' OR event_id IN (SELECT event_id FROM co_host WHERE user_id = '$user_id' AND cohost_status = 'active'))";
    $event_result = $conn->query($event_query);
    
    if ($event_result->num_rows > 0) {
        $selected_event = $event_result->fetch_assoc();
        
        // Get attendance list for this event
        $attendance_query = "SELECT u.name, u.user_id, a.attendance_check_in_time 
                            FROM attendance a 
                            JOIN users u ON a.user_id = u.user_id 
                            WHERE a.event_id = '$event_id'
                            ORDER BY u.name";
        $attendance_result = $conn->query($attendance_query);
        
        while ($record = $attendance_result->fetch_assoc()) {
            $attendance_list[] = $record;
        }
        
        // Also get registered users who didn't attend
        $absent_query = "SELECT u.name, u.user_id 
                        FROM registrations r 
                        JOIN users u ON r.user_id = u.user_id 
                        WHERE r.event_id = '$event_id' 
                        AND r.registration_status = 'Registered'
                        AND r.user_id NOT IN (SELECT user_id FROM attendance WHERE event_id = '$event_id')
                        ORDER BY u.name";
        $absent_result = $conn->query($absent_query);
        
        while ($record = $absent_result->fetch_assoc()) {
            $record['attendance_check_in_time'] = null;
            $attendance_list[] = $record;
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
  <title>Attendance List - ReLife Hub</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <div class="main-content">
    <main class="dashboard">
      
      <div class="attendance-list-container">
        <h1>Attendance List</h1>

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

          <?php if ($selected_event && count($attendance_list) > 0): ?>
            <!-- Attendance Table -->
            <table class="attendance-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>ID</th>
                  <th>Absent/Present</th>
                  <th>Time Stamp</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($attendance_list as $record): ?>
                  <tr>
                    <td><?= htmlspecialchars($record['name']) ?></td>
                    <td><?= htmlspecialchars($record['user_id']) ?></td>
                    <td class="<?= $record['attendance_check_in_time'] ? 'status-present' : 'status-absent' ?>">
                      <?= $record['attendance_check_in_time'] ? 'Present' : 'Absent' ?>
                    </td>
                    <td><?= $record['attendance_check_in_time'] ? date('d/m/y H:i', strtotime($record['attendance_check_in_time'])) : '-' ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <!-- Export Button -->
            <button type="button" onclick="exportToCSV()" class="action-btn">Export List</button>

          <?php elseif ($selected_event && count($attendance_list) == 0): ?>
            <p class="no-data">No attendance records for this event.</p>
          <?php endif; ?>
        </form>

        <?php if ($selected_event): ?>
          <!-- Back Button -->
          <div class="back-button-container">
            <a href="organizer_registration_attendance_hub.php" class="action-btn">Back</a>
          </div>
        <?php endif; ?>
      </div>

    </main>
  </div>

  <?php include 'organizer_footer.php'; ?>

  <script>
    function exportToCSV() {
      var table = document.querySelector('.attendance-table');
      var rows = table.querySelectorAll('tr');
      var csv = [];
      
      for (var i = 0; i < rows.length; i++) {
        var row = [];
        var cols = rows[i].querySelectorAll('td, th');
        
        for (var j = 0; j < cols.length; j++) {
          row.push(cols[j].innerText);
        }
        
        csv.push(row.join(','));
      }
      
      var csvContent = csv.join('\n');
      var blob = new Blob([csvContent], { type: 'text/csv' });
      var url = window.URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      a.download = 'attendance_list.csv';
      a.click();
    }
  </script>
</body>
</html>

<style>
/* Attendance List Styles */
.attendance-list-container {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  max-width: 900px;
  margin: 0 auto;
}

.attendance-list-container h1 {
  margin-bottom: 2rem;
}

.attendance-table {
  width: 100%;
  border-collapse: collapse;
  margin: 2rem 0;
}

.attendance-table th {
  background: #f5f5f5;
  padding: 1rem;
  text-align: left;
  border: 1px solid #ddd;
  font-weight: bold;
}

.attendance-table td {
  padding: 1rem;
  border: 1px solid #ddd;
}

.attendance-table tbody tr:hover {
  background: #f9f9f9;
}

.status-present {
  color: #2e7d32;
  font-weight: bold;
}

.status-absent {
  color: #d32f2f;
  font-weight: bold;
}

.back-button-container {
  margin-top: 2rem;
  text-align: right;
}

.back-button-container .btn {
  padding: 0.75rem 2rem;
}
</style>
