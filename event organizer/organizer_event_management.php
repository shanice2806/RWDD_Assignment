<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protect access
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

include '../connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Management - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <!-- Main content -->
  <div class="main-content">
    <div class="event-management-container">
      <h1 class="page-title">📅 Event Management Hub</h1>

      <!-- Quick Stats -->
      <div class="stats-bar">
        <?php
        $total_events = $conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'];
        $pending_events = $conn->query("SELECT COUNT(*) AS total FROM events WHERE event_status='Pending'")->fetch_assoc()['total'];
        $approved_events = $conn->query("SELECT COUNT(*) AS total FROM events WHERE event_status='Approved'")->fetch_assoc()['total'];
        ?>
        <div class="stat-card">
          <div class="stat-number"><?= $total_events ?></div>
          <div class="stat-label">Total Events</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $pending_events ?></div>
          <div class="stat-label">Pending Approval</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $approved_events ?></div>
          <div class="stat-label">Approved Events</div>
        </div>
      </div>

      <!-- Section 1: Create Event -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">✏️</span>
          <div>
            <h2 class="section-title">Create New Event</h2>
            <p class="section-description">Plan and set up a new sustainability event</p>
          </div>
        </div>
        <a href="organizer_event_create.php" class="action-btn">+ Create Event</a>
      </div>

      <!-- Section 2: Manage Events -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">🛠️</span>
          <div>
            <h2 class="section-title">Manage Events</h2>
            <p class="section-description">View, edit, and archive your events</p>
          </div>
        </div>
        <div class="action-grid">
          <a href="organizer_event_view.php" class="action-btn">👁️ View All Events</a>
          <a href="organizer_event_select_edit.php" class="action-btn">✏️ Edit Events</a>
          <a href="organizer_event_select_archive.php" class="action-btn">📦 Archive Events</a>
        </div>
        <div class="submenu">
          <div class="submenu-title">💡 Quick Tips</div>
          <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555; line-height: 1.6;">
            <li>View all your events in one place and filter by status</li>
            <li>Edit event details, date, time, and location at any time</li>
            <li>Archive past events to keep your dashboard organized</li>
          </ul>
        </div>
      </div>

      <!-- Section 3: Announcements & Recaps -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">📢</span>
          <div>
            <h2 class="section-title">Announcements & Recaps</h2>
            <p class="section-description">Communicate updates and summarize event outcomes</p>
          </div>
        </div>
        <div class="action-grid">
          <a href="organizer_announcement.php" class="action-btn">📄 View Announcements</a>
          <a href="organizer_announcement_create.php" class="action-btn">➕ Post Announcement</a>
          <a href="organizer_notification_send.php" class="action-btn">🔔 Send Notification</a>
          <a href="organizer_recap_summary.php" class="action-btn">📊 Post Recap</a>
        </div>
        <div class="submenu">
          <div class="submenu-title">💡 Quick Tips</div>
          <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555; line-height: 1.6;">
            <li>Share important updates and changes with registered participants</li>
            <li>Post event recaps with photos, attendance stats, and impact metrics</li>
            <li>Send notifications to alert users about announcements</li>
          </ul>
        </div>
      </div>

      <!-- Additional Features -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">⚙️</span>
          <div>
            <h2 class="section-title">Additional Tools</h2>
            <p class="section-description">Access specialized features</p>
          </div>
        </div>
        <div class="action-grid">
          <a href="organizer_registration_list.php" class="action-btn">📋 Registrations</a>
          <a href="organizer_attendance_log.php" class="action-btn">✅ Attendance</a>
          <a href="organizer_recycling_log.php" class="action-btn">♻️ Sustainability Tracking</a>
        </div>
      </div>
    </div>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
