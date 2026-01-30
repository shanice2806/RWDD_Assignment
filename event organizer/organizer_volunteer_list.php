<?php
session_start();

// Check if user is an organizer
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

include '../connect.php';

$user_id = $_SESSION['user_id'];
$selected_event_id = $_POST['event_id'] ?? ($_GET['event_id'] ?? '');

// Fetch events created by this organizer
$events_query = "SELECT event_id, event_title, event_date_time FROM events WHERE event_created_by = '$user_id' ORDER BY event_date_time DESC";
$events_result = $conn->query($events_query);

// Fetch volunteers for selected event
$volunteers = [];
if (!empty($selected_event_id)) {
    $volunteers_query = "SELECT 
                            v.volunteer_id, 
                            v.volunteer_task, 
                            v.volunteer_hours_logged,
                            v.time_slot,
                            u.user_id,
                            u.name, 
                            u.email,
                            u.eco_points
                        FROM volunteers v 
                        JOIN users u ON v.user_id = u.user_id 
                        WHERE v.event_id = '$selected_event_id' 
                        ORDER BY u.name ASC";
    $volunteers_result = $conn->query($volunteers_query);
    
    if ($volunteers_result) {
        while ($row = $volunteers_result->fetch_assoc()) {
            $volunteers[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Volunteer List - Event Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <div class="main-content">
    <div class="back-btn-container">
      <a href="organizer_registration_attendance_hub.php" class="action-btn">← Back</a>
    </div>
    <div class="container">
      <h1>Volunteer List</h1>

      <!-- Event Selection -->
      <form method="POST" action="" class="announcement-form">
        <div class="form-group">
          <label class="form-label">Select Event:</label>
          <select name="event_id" class="form-control" onchange="this.form.submit()">
            <option value="">-- Select Event --</option>
            <?php 
            if ($events_result && $events_result->num_rows > 0):
              $events_result->data_seek(0);
              while ($event = $events_result->fetch_assoc()):
                $selected = ($event['event_id'] == $selected_event_id) ? 'selected' : '';
            ?>
              <option value="<?= $event['event_id'] ?>" <?= $selected ?>>
                <?= htmlspecialchars($event['event_title']) ?> - <?= date('M d, Y', strtotime($event['event_date_time'])) ?>
              </option>
            <?php 
              endwhile;
            endif;
            ?>
          </select>
        </div>
      </form>

      <?php if (!empty($selected_event_id) && count($volunteers) > 0): ?>
      <!-- Volunteer Table -->
      <table class="participant-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>ID</th>
            <th>Task</th>
            <th>Contact Info</th>
            <th>Hours Logged</th>
            <th>Eco-Points Awarded</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($volunteers as $volunteer): ?>
          <tr>
            <td><?= htmlspecialchars($volunteer['name']) ?></td>
            <td><?= htmlspecialchars($volunteer['user_id']) ?></td>
            <td><?= htmlspecialchars($volunteer['volunteer_task'] ?: 'Not assigned') ?></td>
            <td><?= htmlspecialchars($volunteer['email']) ?></td>
            <td><?= number_format($volunteer['volunteer_hours_logged'], 2) ?></td>
            <td><?= $volunteer['eco_points'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php elseif (!empty($selected_event_id)): ?>
      <div class="alert alert-info">No volunteers found for this event.</div>
      <?php endif; ?>
    </div>
  </div>

<?php include 'organizer_footer.php'; ?>
</body>
</html>
