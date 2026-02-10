<?php


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

// ========================================
// Get statistics for dashboard
// ========================================

$user_id = $_SESSION['user_id'];

$query1 = "SELECT COUNT(DISTINCT e.event_id) AS total 
           FROM events e
           LEFT JOIN co_host ch ON e.event_id = ch.event_id
           WHERE (e.event_created_by = '$user_id' OR (ch.user_id = '$user_id' AND ch.cohost_status = 'active'))";
$result1 = $conn->query($query1);
$total_events = $result1->fetch_assoc()['total'];


$query2 = "SELECT COUNT(DISTINCT e.event_id) AS total 
           FROM events e
           LEFT JOIN co_host ch ON e.event_id = ch.event_id
           WHERE (e.event_created_by = '$user_id' OR (ch.user_id = '$user_id' AND ch.cohost_status = 'active'))
           AND LOWER(e.event_status)='pending'";
$result2 = $conn->query($query2);
$total_pending = $result2->fetch_assoc()['total'];


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

$impact_attendance = 0;
$impact_recyclables = 0;


if ($impact_event) {
    $event_id = $impact_event['event_id'];

    // Count attendance 
    $query5 = "SELECT COUNT(*) AS total_attendance FROM attendance WHERE event_id = '$event_id'";
    $result5 = $conn->query($query5);
    $impact_attendance = $result5->fetch_assoc()['total_attendance'];

    // Sum recyclables weight 
    $query6 = "SELECT COALESCE(SUM(weight_kg),0) AS total_recyclables FROM recycling_log WHERE event_id = '$event_id' AND status='VALID'";
    $result6 = $conn->query($query6);
    $impact_recyclables = $result6->fetch_assoc()['total_recyclables'];
}


$notifications = [];
$notif_query = "SELECT n.notification_id, n.event_id, n.message, n.created_at, e.event_title
                FROM notifications n
                LEFT JOIN events e ON n.event_id = e.event_id
                WHERE n.audience_type = '$user_id' AND n.is_active = 1
                ORDER BY n.created_at DESC
                LIMIT 5";
$notif_result = $conn->query($notif_query);
if ($notif_result) {
    while ($notif = $notif_result->fetch_assoc()) {
        $notifications[] = $notif;
    }
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

      <!-- Notifications Section -->
      <?php if (!empty($notifications)): ?>
      <section>
        <h2>🔔 Notifications</h2>
        <p style="color: #6c757d; font-size: 14px; margin-bottom: 15px;">Latest updates for you (showing up to 5)</p>
        
        <?php foreach ($notifications as $notif): ?>
          <div class="alert" style="margin-bottom: 10px; border-left: 4px solid #3ba99c;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
              <strong>Event: <?= htmlspecialchars($notif['event_title'] ?? 'N/A') ?></strong>
              <span style="color: #6c757d; font-size: 13px;"><?= date('d M Y, h:i A', strtotime($notif['created_at'])) ?></span>
            </div>
            <div><?= htmlspecialchars($notif['message']) ?></div>
          </div>
        <?php endforeach; ?>
      </section>
      <?php endif; ?>

      <!-- Quick View Cards -->
      <section class="card-grid">
        <div class="card">
          <div class="icon">📅</div>
          <p>Total Events</p>
          <h3><?= $total_events ?></h3>
          <a href="organizer_event_view.php" class="action-btn">View</a>
        </div>
        <div class="card">
          <div class="icon">⏳</div>
          <p>Pending Approvals</p>
          <h3><?= $total_pending ?></h3>
          <a href="organizer_event_view.php?filter=pending" class="action-btn">Review</a>
        </div>
        <div class="card">
          <div class="icon">📝</div>
          <p>Registrations</p>
          <h3><?= $total_registrations ?></h3>
          <a href="organizer_registration_list.php" class="action-btn">View</a>
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
          <a href="organizer_report.php?event_id=<?= urlencode($impact_event['event_id']) ?>" class="action-btn">View Full Report</a>
        <?php else: ?>
          <p>No approved events yet.</p>
        <?php endif; ?>
      </section>

    </main>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
