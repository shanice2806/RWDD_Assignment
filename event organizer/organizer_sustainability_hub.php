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
  <title>Sustainability Tracking - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <!-- Main content -->
    <div class="main-content">
    <div class="event-management-container">
      <h1 class="page-title">🌱 Sustainability Tracking Hub</h1>

      <!-- Quick Stats -->
      <div class="stats-bar">
        <?php
        $total_recyclables = $conn->query("SELECT COALESCE(SUM(weight_kg), 0) AS total FROM recycling_log WHERE status='VALID'")->fetch_assoc()['total'];
        $total_logs = $conn->query("SELECT COUNT(*) AS total FROM recycling_log WHERE status='VALID'")->fetch_assoc()['total'];
        $total_events = $conn->query("SELECT COUNT(*) AS total FROM events WHERE event_status='Approved'")->fetch_assoc()['total'];
        ?>
        <div class="stat-card">
          <div class="stat-number"><?= number_format($total_recyclables, 1) ?> kg</div>
          <div class="stat-label">Total Recyclables</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $total_logs ?></div>
          <div class="stat-label">Recycling Logs</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $total_events ?></div>
          <div class="stat-label">Active Events</div>
        </div>
      </div>

      <!-- Section 1: Recyclables Logging -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">♻️</span>
          <div>
            <h2 class="section-title">Recyclables Logging</h2>
            <p class="section-description">Manually log recyclables collected during events</p>
          </div>
        </div>
        <a href="organizer_recycling_log.php" class="action-btn">📝 Log Recyclables</a>
        <div class="submenu">
          <div class="submenu-title">💡 Quick Tips</div>
          <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555; line-height: 1.6;">
            <li>Log different types of recyclables (plastic, paper, metal, etc.)</li>
            <li>Record the weight in kilograms for accurate tracking</li>
            <li>Include organizer name and event details for reference</li>
            <li>View recent logs to track collection progress</li>
          </ul>
        </div>
      </div>

      <!-- Section 2: Event Impact -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">📊</span>
          <div>
            <h2 class="section-title">Event Impact Dashboard</h2>
            <p class="section-description">View comprehensive sustainability metrics for your events</p>
          </div>
        </div>
        <a href="organizer_event_impact.php" class="action-btn">📈 View Event Impact</a>
        <div class="submenu">
          <div class="submenu-title">💡 What You'll See</div>
          <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555; line-height: 1.6;">
            <li><strong>Attendance Summary:</strong> Total participants and volunteer count</li>
            <li><strong>Recyclables Summary:</strong> Breakdown by material type and weight</li>
            <li><strong>Impact Metrics:</strong> View full reports with detailed analytics</li>
            <li><strong>Notes & Highlights:</strong> Add observations about the event's sustainability performance</li>
          </ul>
        </div>
      </div>

      <!-- Environmental Goal Section -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">🎯</span>
          <div>
            <h2 class="section-title">Sustainability Goals</h2>
            <p class="section-description">Track progress toward environmental objectives</p>
          </div>
        </div>
        <div class="action-grid">
          <div style="grid-column: 1/-1; padding: 15px; background: #f0f8ff; border-radius: 8px; border-left: 4px solid var(--color-secondary);">
            <p style="margin: 0; font-size: 14px; color: #333; line-height: 1.6;">
              📌 <strong>Your Contribution:</strong> Every kilogram of recyclables logged brings us closer to our environmental goals. 
              Keep tracking to maximize the positive impact of your sustainability events!
            </p>
          </div>
        </div>
      </div>

    </div>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
