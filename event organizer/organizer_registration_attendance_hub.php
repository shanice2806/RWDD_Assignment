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
  <title>Registration, Attendance & Volunteers - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <!-- Main content -->
  <div class="main-content">
    <div class="event-management-container">
      <h1 class="page-title">📝 Registration, Attendance & Volunteers Hub</h1>

      <!-- Quick Stats -->
      <div class="stats-bar">
        <?php
        $total_registrations = $conn->query("SELECT COUNT(*) AS total FROM registrations")->fetch_assoc()['total'];
        $total_attendance = $conn->query("SELECT COUNT(*) AS total FROM attendance")->fetch_assoc()['total'];
        $total_volunteers = $conn->query("SELECT COUNT(*) AS total FROM volunteers")->fetch_assoc()['total'] ?? 0;
        ?>
        <div class="stat-card">
          <div class="stat-number"><?= $total_registrations ?></div>
          <div class="stat-label">Total Registrations</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $total_attendance ?></div>
          <div class="stat-label">Attendance Records</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $total_volunteers ?></div>
          <div class="stat-label">Active Volunteers</div>
        </div>
      </div>

      <!-- Section 1: Registration Control -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">📋</span>
          <div>
            <h2 class="section-title">Registration Control</h2>
            <p class="section-description">Manage event registration status and view participants</p>
          </div>
        </div>
        <div class="action-grid">
          <a href="organizer_registration_control.php" class="action-btn primary">⚙️ Open/Close Registration</a>
          <a href="organizer_registration_list.php" class="action-btn">👥 View Registration List</a>
        </div>
        <div class="submenu">
          <div class="submenu-title">💡 Quick Tips</div>
          <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555; line-height: 1.6;">
            <li>Open registration to allow participants to sign up for your event</li>
            <li>Close registration when you reach capacity or after a deadline</li>
            <li>View all registered participants and their details in one place</li>
          </ul>
        </div>
      </div>

      <!-- Section 2: Attendance Management -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">✅</span>
          <div>
            <h2 class="section-title">Attendance Management</h2>
            <p class="section-description">Track participant attendance via QR code or manual entry</p>
          </div>
        </div>
        <div class="action-grid">
          <a href="organizer_event_qr.php" class="action-btn">📱 QR Code Attendance</a>
          <a href="organizer_manual_attendance.php" class="action-btn">✏️ Manual Marking</a>
          <a href="organizer_attendance_view.php" class="action-btn">📊 View Attendance List</a>
        </div>
        <div class="submenu">
          <div class="submenu-title">💡 Quick Tips</div>
          <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555; line-height: 1.6;">
            <li>Generate QR codes for quick check-in at event start</li>
            <li>Manually mark attendance if participants forgot QR code</li>
            <li>Export attendance records for reports and analysis</li>
          </ul>
        </div>
      </div>

      <!-- Section 3: Volunteer Management -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">👨‍💼</span>
          <div>
            <h2 class="section-title">Volunteer Management</h2>
            <p class="section-description">Recruit, assign tasks, and reward volunteers</p>
          </div>
        </div>
        <div class="action-grid">
          <a href="organizer_recruit_volunteers.php" class="action-btn">➕ Recruit Volunteers</a>
          <a href="organizer_assign_volunteer_task.php" class="action-btn">🎯 Assign Tasks</a>
          <a href="organizer_volunteer_hours.php" class="action-btn">⏱️ Log Hours</a>
          <a href="organizer_award_badges.php" class="action-btn">🏅 Award Badges</a>
          <a href="organizer_volunteer_list.php" class="action-btn">📋 View Volunteer List</a>
        </div>
        <div class="submenu">
          <div class="submenu-title">💡 Quick Tips</div>
          <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555; line-height: 1.6;">
            <li>Recruit volunteers with different skill interests</li>
            <li>Assign specific tasks and time slots to volunteers</li>
            <li>Track volunteer hours for recognition and future events</li>
            <li>Award badges to motivate and celebrate volunteer contributions</li>
          </ul>
        </div>
      </div>

    </div>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
