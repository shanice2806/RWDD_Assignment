<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protect access: only logged-in organizers
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

include '../connect.php';

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
    <main class="dashboard">

      <?php if (!$event_id): ?>
        <!-- Show list of events -->
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
          $events = $conn->query("SELECT event_id, event_title, event_date_time, event_status, event_location, registration_status FROM events ORDER BY event_date_time DESC");
          if ($events && $events->num_rows > 0) {
            while ($row = $events->fetch_assoc()) {
              $eid = $row['event_id'];
              $count = $conn->query("SELECT COUNT(*) AS total FROM registrations WHERE event_id = '$eid'")->fetch_assoc()['total'];
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
        $event = $conn->query("SELECT event_title, event_date_time, registration_status FROM events WHERE event_id = '$event_id'")->fetch_assoc();
        if (!$event) {
          echo "<p>Event not found.</p>";
        } elseif (strtolower($event['registration_status']) !== 'open') {
          echo "<p style='color:#b00020; font-weight:600;'>Registration for this event is not open yet.</p>";
          echo "<a class='btn' href='organizer_event_view.php'>Open Registration</a>";
        } else {
          // Join users table to get participant name
          $registrations = $conn->query("
            SELECT r.registration_id, r.user_id, u.name, r.registration_status
            FROM registrations r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.event_id = '$event_id'
            ORDER BY r.registration_id DESC
          ");
      ?>

        <h2>Registrations for <?= htmlspecialchars($event['event_title']) ?> (<?= htmlspecialchars($event['event_date_time']) ?>)</h2>

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

        <a href="organizer_registration_list.php" class="btn">← Back to Event List</a>

      <?php } endif; ?>

    </main>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
