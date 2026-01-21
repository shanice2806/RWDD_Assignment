<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protect access: only logged-in organizers
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

include '../connect.php';

/* Quick stats */
$total_events = $conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'];
$total_pending = $conn->query("SELECT COUNT(*) AS total FROM events WHERE event_status='Pending'")->fetch_assoc()['total'];
$total_registrations = $conn->query("SELECT COUNT(*) AS total FROM registrations")->fetch_assoc()['total'];

/* Latest approved event for impact */
$impact_event = $conn->query("
    SELECT event_id, event_title 
    FROM events 
    WHERE event_status='Approved' 
    ORDER BY event_date_time DESC 
    LIMIT 1
")->fetch_assoc();

$impact_attendance = 0;
$impact_recyclables = 0;

if ($impact_event) {
    $eventId = $impact_event['event_id'];

    $attRow = $conn->query("SELECT COUNT(*) AS total_attendance FROM attendance WHERE event_id = '$eventId'")->fetch_assoc();
    $impact_attendance = $attRow['total_attendance'];

    $recRow = $conn->query("SELECT COALESCE(SUM(weight_kg),0) AS total_recyclables FROM recycling_log WHERE event_id = '$eventId' AND status='VALID'")->fetch_assoc();
    $impact_recyclables = $recRow['total_recyclables'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Organizer Dashboard</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>



  <!-- Main content -->
  <div class="main-content">
    <main class="dashboard">

      <!-- Quick View Cards -->
      <section class="card-grid">
        <div class="card">
          <div class="icon">📅</div>
          <p>Total Events</p>
          <h3><?= $total_events ?></h3>
          <a href="organizer_event_view.php" class="btn">View</a>
        </div>
        <div class="card">
          <div class="icon">⏳</div>
          <p>Pending Approvals</p>
          <h3><?= $total_pending ?></h3>
          <a href="organizer_event_view.php?filter=pending" class="btn">Review</a>
        </div>
        <div class="card">
          <div class="icon">📝</div>
          <p>Registrations</p>
          <h3><?= $total_registrations ?></h3>
          <a href="organizer_registration_list.php" class="btn">View</a>
        </div>
      </section>

      <!-- Event List Table -->
      <section>
        <h2>Event List</h2>
        <table class="eco-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Date</th>
              <th>Status</th>
              <th>Location</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $events = $conn->query("SELECT event_id, event_title, event_date_time, event_status, event_location FROM events ORDER BY event_date_time DESC");
          if ($events && $events->num_rows > 0) {
            while ($row = $events->fetch_assoc()) {
              echo "<tr>
                <td>".htmlspecialchars($row['event_title'])."</td>
                <td>".htmlspecialchars($row['event_date_time'])."</td>
                <td>".htmlspecialchars($row['event_status'])."</td>
                <td>".htmlspecialchars($row['event_location'])."</td>
                <td>
                  <a class='action-btn' href='organizer_registration_list.php?event_id=".urlencode($row['event_id'])."'>Registrations</a>
                  <a class='action-btn' href='organizer_attendance_log.php?event_id=".urlencode($row['event_id'])."'>Attendance</a>
                </td>
              </tr>";
            }
          } else {
            echo "<tr><td colspan='5'>No events found.</td></tr>";
          }
          ?>
          </tbody>
        </table>
      </section>

      <!-- Event Impact -->
      <section class="event-impact">
        <h2>Event Impact <?= $impact_event ? '— ' . htmlspecialchars($impact_event['event_title']) : '' ?></h2>
        <?php if ($impact_event): ?>
          <p><strong>Attendance:</strong> <?= $impact_attendance ?> participants</p>
          <p><strong>Recyclables:</strong> <?= $impact_recyclables ?> kg collected</p>
          <a href="organizer_report.php?event_id=<?= urlencode($impact_event['event_id']) ?>" class="btn">View Full Report</a>
        <?php else: ?>
          <p>No approved events yet.</p>
        <?php endif; ?>
      </section>

    </main>
  </div>

  <?php include 'organizer_footer.php'; ?>
  <script src="organizer.js"></script>
</body>
</html>