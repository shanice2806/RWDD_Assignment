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
// Only organizers can see this page
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    // Not an organizer? Redirect to login
    header("Location: ../login/login.php");
    exit();
}

// Connect to database
include '../connect.php';

// ========================================
// Get statistics for dashboard
// ========================================

// Get current user's ID
$user_id = $_SESSION['user_id'];

// Count total events created by this organizer OR where they are an active cohost
$query1 = "SELECT COUNT(DISTINCT e.event_id) AS total 
           FROM events e
           LEFT JOIN co_host ch ON e.event_id = ch.event_id
           WHERE (e.event_created_by = '$user_id' OR (ch.user_id = '$user_id' AND ch.cohost_status = 'active'))";
$result1 = $conn->query($query1);
$total_events = $result1->fetch_assoc()['total'];

// Count pending events created by this organizer OR where they are an active cohost
$query2 = "SELECT COUNT(DISTINCT e.event_id) AS total 
           FROM events e
           LEFT JOIN co_host ch ON e.event_id = ch.event_id
           WHERE (e.event_created_by = '$user_id' OR (ch.user_id = '$user_id' AND ch.cohost_status = 'active'))
           AND LOWER(e.event_status)='pending'";
$result2 = $conn->query($query2);
$total_pending = $result2->fetch_assoc()['total'];

// Count total registrations for events created by this organizer OR where they are an active cohost
$query3 = "SELECT COUNT(DISTINCT r.registration_id) AS total 
           FROM registrations r 
           INNER JOIN events e ON r.event_id = e.event_id 
           LEFT JOIN co_host ch ON e.event_id = ch.event_id
           WHERE (e.event_created_by = '$user_id' OR (ch.user_id = '$user_id' AND ch.cohost_status = 'active'))";
$result3 = $conn->query($query3);
$total_registrations = $result3->fetch_assoc()['total'];

// ========================================
// Get latest event for impact section
// ========================================
$query4 = "SELECT event_id, event_title FROM events WHERE event_status='Approved' ORDER BY event_date_time DESC LIMIT 1";
$result4 = $conn->query($query4);
$impact_event = $result4->fetch_assoc();

// Set default values
$impact_attendance = 0;
$impact_recyclables = 0;

// If there's an event, get its impact data
if ($impact_event) {
    $event_id = $impact_event['event_id'];

    // Count attendance for this event
    $query5 = "SELECT COUNT(*) AS total_attendance FROM attendance WHERE event_id = '$event_id'";
    $result5 = $conn->query($query5);
    $impact_attendance = $result5->fetch_assoc()['total_attendance'];

    // Sum recyclables weight for this event
    $query6 = "SELECT COALESCE(SUM(weight_kg),0) AS total_recyclables FROM recycling_log WHERE event_id = '$event_id' AND status='VALID'";
    $result6 = $conn->query($query6);
    $impact_recyclables = $result6->fetch_assoc()['total_recyclables'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
</body>
</html>