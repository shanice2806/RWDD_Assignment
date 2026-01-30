<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// Check if user is an organizer
// ========================================
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    // Not an organizer? Send to login page
    header("Location: ../login/login.php");
    exit();
}

// Connect to database
include '../connect.php';

// Get event_id from URL (if provided)
// Example: organizer_registration_list.php?event_id=evt_001
$event_id = $_GET['event_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registrations</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <!-- Main content -->
    <div class="main-content">
    <div class="back-btn-container">
      <a href="organizer_registration_attendance_hub.php" class="action-btn"> Back</a>
    </div>    <main class="dashboard">

      <?php if (!$event_id): ?>
        <!-- ============================================ -->
        <!-- CASE 1: No event selected - Show all events -->
        <!-- ============================================ -->
        <h2>Event Registration Overview</h2>
        <table class="eco-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Date</th>
              <th>Status</th>
              <th>Location</th>
              <th>Registrations</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php
          // Get all events from database
          $query = "SELECT event_id, event_title, event_date_time, event_status, event_location, registration_status FROM events ORDER BY event_date_time DESC";
          $events = $conn->query($query);
          
          // Check if there are any events
          if ($events && $events->num_rows > 0) {
            // Loop through each event
            while ($row = $events->fetch_assoc()) {
              $eid = $row['event_id'];
              
              // Count how many people registered for this event
              $count_query = "SELECT COUNT(*) AS total FROM registrations WHERE event_id = '$eid'";
              $count_result = $conn->query($count_query);
              $count = $count_result->fetch_assoc()['total'];
              
              // Display event row
              echo "<tr>
                <td>".htmlspecialchars($row['event_title'])."</td>
                <td>".htmlspecialchars($row['event_date_time'])."</td>
                <td>".htmlspecialchars($row['event_status'])."</td>
                <td>".htmlspecialchars($row['event_location'])."</td>
                <td>$count</td>
                <td><a class='action-btn' href='organizer_registration_list.php?event_id=$eid'>View List</a></td>
              </tr>";
            }
          } else {
            echo "<tr><td colspan='6'>No events found.</td></tr>";
          }
          ?>
          </tbody>
        </table>

      <?php else:
        // ============================================
        // CASE 2: Event selected - Show registrations
        // ============================================
        
        // Get event details
        $event_query = "SELECT event_title, event_date_time, registration_status FROM events WHERE event_id = '$event_id'";
        $event_result = $conn->query($event_query);
        $event = $event_result->fetch_assoc();
        
        // Check if event exists
        if (!$event) {
          echo "<p>Event not found.</p>";
        } else {
          // Get all registrations with user names
          $registrations_query = "
            SELECT r.registration_id, r.user_id, u.name, r.registration_status
            FROM registrations r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.event_id = '$event_id'
            ORDER BY r.registration_id DESC
          ";
          $registrations = $conn->query($registrations_query);
          
          // Get count of registrations
          $reg_count = $registrations ? $registrations->num_rows : 0;
      ?>

        <h2>Registrations for <?= htmlspecialchars($event['event_title']) ?> (<?= htmlspecialchars($event['event_date_time']) ?>)</h2>
        
        <?php if (strtolower($event['registration_status']) !== 'open'): ?>
          <div class="alert alert-error" style="margin-bottom: 20px;">
            <strong>⚠️ Registration Status: CLOSED</strong><br>
            Registration for this event is currently closed. You can still view the <?= $reg_count ?> participant(s) who registered below.
            <?php if ($reg_count > 0): ?>
              <br><small>To allow new registrations, go to <a href="organizer_registration_control.php">Registration Control</a> to open registration again.</small>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-success" style="margin-bottom: 20px;">
            <strong>✓ Registration Status: OPEN</strong><br>
            Registration is currently open. Total registrations: <?= $reg_count ?>
          </div>
        <?php endif; ?>

        <table class="eco-table">
          <thead>
            <tr>
              <th>Registration ID</th>
              <th>Name</th>
              <th>User ID</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($registrations && $registrations->num_rows > 0): ?>
            <?php while ($row = $registrations->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['registration_id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['user_id']) ?></td>
                <td><?= htmlspecialchars($row['registration_status']) ?></td>
                <td>
                  <a href="view_participant.php?reg_id=<?= urlencode($row['registration_id']) ?>">View</a> /
                  <a href="remove_registration.php?reg_id=<?= urlencode($row['registration_id']) ?>" onclick="return confirm('Remove this registration?')">Remove</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="5">No registrations found for this event.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>

        <a href="organizer_registration_list.php" class="action-btn">← Back to Event List</a>

      <?php } endif; ?>

    </main>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
