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
  <title>Collaboration & Feedback - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <!-- Main content -->
  <div class="main-content">
    <div class="event-management-container">
      <h1 class="page-title">💬 Collaboration & Feedback Hub</h1>

      <!-- Quick Stats -->
      <div class="stats-bar">
        <?php
        // Fetch real data from database
        $total_cohosts = $conn->query("SELECT COUNT(*) AS total FROM co_host WHERE cohost_status='active'")->fetch_assoc()['total'] ?? 0;
        $total_feedback = $conn->query("SELECT COUNT(*) AS total FROM feedback")->fetch_assoc()['total'] ?? 0;
        $avg_rating = $conn->query("SELECT COALESCE(AVG(feedback_rating), 0) AS avg FROM feedback")->fetch_assoc()['avg'] ?? 0;
        $avg_rating = number_format($avg_rating, 1);
        ?>
        <div class="stat-card">
          <div class="stat-number"><?= $total_cohosts ?></div>
          <div class="stat-label">Active Co-Hosts</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $total_feedback ?></div>
          <div class="stat-label">Feedback Items</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $avg_rating ?></div>
          <div class="stat-label">Avg Rating</div>
        </div>
      </div>

      <!-- Section 1: Co-Host Tools -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">👥</span>
          <div>
            <h2 class="section-title">Co-Host Tools</h2>
            <p class="section-description">Invite and manage co-hosts for your events</p>
          </div>
        </div>
        <div class="action-grid">
          <a href="organizer_invite_cohost.php" class="action-btn">➕ Invite Co-Host</a>
          <a href="organizer_assign_cohost_roles.php" class="action-btn">🎯 Assign Roles</a>
        </div>
        <div class="submenu">
          <div class="submenu-title">💡 Quick Tips</div>
          <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555; line-height: 1.6;">
            <li>Invite other organizers to co-host your events</li>
            <li>Assign roles like organizer, moderator, or coordinator</li>
            <li>Set permissions for what each co-host can do</li>
            <li>Manage co-host access and responsibilities</li>
          </ul>
        </div>
      </div>

      <!-- Section 2: Feedback Management -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">⭐</span>
          <div>
            <h2 class="section-title">Feedback & Ratings</h2>
            <p class="section-description">Collect and analyze participant feedback</p>
          </div>
        </div>
        <div class="action-grid">
          <a href="organizer_rating_summary.php" class="action-btn">📊 Rating Summary</a>
          <a href="organizer_comment_list.php" class="action-btn">💬 View Comments</a>
        </div>
        <div class="submenu">
          <div class="submenu-title">💡 What You'll See</div>
          <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555; line-height: 1.6;">
            <li><strong>Rating Summary:</strong> Overall ratings, average scores, and star ratings</li>
            <li><strong>Comments:</strong> Participant feedback and suggestions for improvement</li>
            <li><strong>Export:</strong> Download feedback data for analysis</li>
            <li><strong>Insights:</strong> View trends and common feedback themes</li>
          </ul>
        </div>
      </div>

      <!-- Collaboration Best Practices -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">🤝</span>
          <div>
            <h2 class="section-title">Collaboration Best Practices</h2>
            <p class="section-description">Tips for effective event collaboration</p>
          </div>
        </div>
        <div class="action-grid">
          <div style="grid-column: 1/-1; padding: 15px; background: #f0f8ff; border-radius: 8px; border-left: 4px solid var(--color-secondary);">
            <p style="margin: 0 0 10px 0; font-size: 14px; color: #333; font-weight: 600;">✨ Tips for Great Collaboration:</p>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555; line-height: 1.8;">
              <li>Clearly define roles and responsibilities for each co-host</li>
              <li>Regularly communicate about event planning and updates</li>
              <li>Encourage co-hosts to provide feedback and suggestions</li>
              <li>Review participant ratings and comments together</li>
              <li>Use feedback to improve future events and collaboration</li>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
