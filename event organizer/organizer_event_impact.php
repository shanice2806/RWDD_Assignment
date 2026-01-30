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
$show_full_report = isset($_POST['view_report']) || isset($_GET['view_report']);

// Fetch events created by this organizer
$events_query = "SELECT event_id, event_title, event_date_time FROM events WHERE event_created_by = '$user_id' ORDER BY event_date_time DESC";
$events_result = $conn->query($events_query);

// Initialize impact data
$total_participants = 0;
$total_volunteers = 0;
$recyclables_by_material = [];
$total_recyclables_weight = 0;
$event_title = '';

if (!empty($selected_event_id)) {
    // Get event details
    $event_query = "SELECT event_title FROM events WHERE event_id = '$selected_event_id'";
    $event_result = $conn->query($event_query);
    if ($event_result && $event_result->num_rows > 0) {
        $event_title = $event_result->fetch_assoc()['event_title'];
    }
    
    // Count total participants (attendance)
    $attendance_query = "SELECT COUNT(DISTINCT user_id) AS total FROM attendance WHERE event_id = '$selected_event_id'";
    $attendance_result = $conn->query($attendance_query);
    if ($attendance_result) {
        $total_participants = $attendance_result->fetch_assoc()['total'];
    }
    
    // Count volunteers
    $volunteers_query = "SELECT COUNT(*) AS total FROM volunteers WHERE event_id = '$selected_event_id'";
    $volunteers_result = $conn->query($volunteers_query);
    if ($volunteers_result) {
        $total_volunteers = $volunteers_result->fetch_assoc()['total'];
    }
    
    // Get recyclables summary by material
    $recyclables_query = "SELECT m.materials_name, SUM(rl.weight_kg) AS total_weight
                         FROM recycling_log rl
                         JOIN materials m ON rl.material_id = m.material_id
                         WHERE rl.event_id = '$selected_event_id' AND rl.status = 'VALID'
                         GROUP BY m.material_id, m.materials_name
                         ORDER BY total_weight DESC";
    $recyclables_result = $conn->query($recyclables_query);
    
    if ($recyclables_result) {
        while ($row = $recyclables_result->fetch_assoc()) {
            $recyclables_by_material[] = $row;
            $total_recyclables_weight += $row['total_weight'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Impact - Event Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <div class="main-content">
    <div class="back-btn-container">
      <a href="organizer_sustainability_hub.php" class="action-btn">← Back</a>
    </div>
    <div class="container">
      <h1>Event Impact</h1>

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

      <?php if (!empty($selected_event_id)): ?>
      <!-- Attendance Summary -->
      <div class="announcement-form">
        <h2>Attendance Summary</h2>
        <div class="form-group">
          <p><strong>Total Participants:</strong> <?= $total_participants ?></p>
          <p><strong>Volunteers:</strong> <?= $total_volunteers ?></p>
        </div>
        <div class="form-actions">
          <a href="organizer_attendance_view.php?event_id=<?= $selected_event_id ?>" class="action-btn">View Attendance List</a>
          <a href="organizer_volunteer_list.php?event_id=<?= $selected_event_id ?>" class="action-btn">View Volunteers List</a>
        </div>
      </div>

      <!-- Recyclables Summary -->
      <div class="announcement-form">
        <h2>Recyclables Summary</h2>
        <?php if (count($recyclables_by_material) > 0): ?>
        <div class="form-group">
          <?php foreach ($recyclables_by_material as $material): ?>
          <p><strong><?= htmlspecialchars($material['materials_name']) ?>:</strong> <?= number_format($material['total_weight'], 2) ?> kg</p>
          <?php endforeach; ?>
          <hr>
          <p><strong>Total Weight:</strong> <?= number_format($total_recyclables_weight, 2) ?> kg</p>
        </div>
        <?php else: ?>
        <p class="form-text">No recyclables logged for this event yet.</p>
        <?php endif; ?>
      </div>

      <!-- View Full Report Button -->
      <?php if (!$show_full_report): ?>
      <div class="form-actions">
        <form method="POST" action="">
          <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
          <button type="submit" name="view_report" class="action-btn">View Full Report</button>
        </form>
      </div>
      <?php endif; ?>

      <!-- Full Report Details -->
      <?php if ($show_full_report): ?>
      <div class="announcement-form">
        <h2>Full Event Impact Report</h2>
        
        <div class="form-group">
          <h3>Event Details</h3>
          <p><strong>Event Name:</strong> <?= htmlspecialchars($event_title) ?></p>
          <p><strong>Event ID:</strong> <?= htmlspecialchars($selected_event_id) ?></p>
        </div>

        <div class="form-group">
          <h3>Participation Metrics</h3>
          <table class="participant-table">
            <thead>
              <tr>
                <th>Metric</th>
                <th>Count</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Total Participants</td>
                <td><?= $total_participants ?></td>
              </tr>
              <tr>
                <td>Registered Volunteers</td>
                <td><?= $total_volunteers ?></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="form-group">
          <h3>Environmental Impact</h3>
          <?php if (count($recyclables_by_material) > 0): ?>
          <table class="participant-table">
            <thead>
              <tr>
                <th>Material Type</th>
                <th>Weight (kg)</th>
                <th>Percentage</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recyclables_by_material as $material): 
                $percentage = $total_recyclables_weight > 0 ? ($material['total_weight'] / $total_recyclables_weight) * 100 : 0;
              ?>
              <tr>
                <td><?= htmlspecialchars($material['materials_name']) ?></td>
                <td><?= number_format($material['total_weight'], 2) ?></td>
                <td><?= number_format($percentage, 1) ?>%</td>
              </tr>
              <?php endforeach; ?>
              <tr style="font-weight: bold; background: #f0f0f0;">
                <td>Total Recyclables Collected</td>
                <td><?= number_format($total_recyclables_weight, 2) ?></td>
                <td>100%</td>
              </tr>
            </tbody>
          </table>
          <?php else: ?>
          <p class="form-text">No recyclables data available for this event.</p>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <h3>Impact Summary</h3>
          <div class="announcement-form">
            <p>🌍 <strong>Total Environmental Impact:</strong> <?= number_format($total_recyclables_weight, 2) ?> kg of materials diverted from landfills</p>
            <p>👥 <strong>Community Engagement:</strong> <?= $total_participants ?> participants contributed to sustainability goals</p>
            <p>🤝 <strong>Volunteer Contribution:</strong> <?= $total_volunteers ?> volunteers supported this event</p>
          </div>
        </div>

        <div class="form-actions">
          <form method="POST" action="">
            <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
            <button type="submit" class="action-btn">Hide Full Report</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <?php endif; ?>
    </div>
  </div>

<?php include 'organizer_footer.php'; ?>
</body>
</html>
