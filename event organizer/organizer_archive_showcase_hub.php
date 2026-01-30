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
  <title>Archive & Showcase - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <!-- Main content -->
    <div class="main-content">
    <div class="event-management-container">
      <h1 class="page-title">📁 Archive & Showcase Hub</h1>

      <!-- Quick Stats -->
      <div class="stats-bar">
        <?php
        // Fetch real data from database
        $archived_events = $conn->query("SELECT COUNT(*) AS total FROM events WHERE event_status='Archived'")->fetch_assoc()['total'] ?? 0;
        $total_media = $conn->query("SELECT COUNT(*) AS total FROM event_media")->fetch_assoc()['total'] ?? 0;
        // Determine featured events count safely — some schemas don't have the same column name
        $featured_events = 0;
        $featuredCols = ['is_featured','event_is_featured','featured','event_featured'];
        foreach ($featuredCols as $col) {
          $colCheck = $conn->query("SHOW COLUMNS FROM events LIKE '" . $conn->real_escape_string($col) . "'");
          if ($colCheck && $colCheck->num_rows > 0) {
            $cntRes = $conn->query("SELECT COUNT(*) AS total FROM events WHERE " . $col . "=1");
            $featured_events = $cntRes ? (int)($cntRes->fetch_assoc()['total'] ?? 0) : 0;
            break;
          }
        }
        ?>
        <div class="stat-card">
          <div class="stat-number"><?= $archived_events ?></div>
          <div class="stat-label">Archived Events</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $total_media ?></div>
          <div class="stat-label">Media Files</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $featured_events ?></div>
          <div class="stat-label">Featured Events</div>
        </div>
      </div>

      <!-- Section 1: Event Archive -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">📦</span>
          <div>
            <h2 class="section-title">Event Archive</h2>
            <p class="section-description">Access and manage your archived events</p>
          </div>
        </div>
        <div class="action-grid">
          <a href="organizer_past_events.php" class="action-btn">📋 Past Events List</a>
          <a href="organizer_event_media_gallery.php" class="action-btn">🖼️ Photos & Videos</a>
        </div>
        <div class="submenu">
          <div class="submenu-title">💡 Quick Tips</div>
          <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555; line-height: 1.6;">
            <li>View all past events with filters by date and event type</li>
            <li>Browse event photos and videos in the media gallery</li>
            <li>View event details and recaps for each archived event</li>
            <li>Download media files or share event memories</li>
          </ul>
        </div>
          <!-- Recent archived events preview -->
          <div style="margin-top:16px;">
            <h3 style="margin:0 0 10px 0;">Recent Archived Events</h3>
            <?php
            $archivedList = $conn->query(
                "SELECT ea.event_id, e.event_title, ea.event_archive_summary, ea.event_archive_impact_stats,
                       (SELECT COUNT(*) FROM event_media em WHERE em.event_id = ea.event_id AND em.event_media_is_hidden = 0) AS media_count
                 FROM event_archive ea
                 LEFT JOIN events e ON ea.event_id = e.event_id
                 ORDER BY ea.event_archive_id DESC
                 LIMIT 5"
            );

            if ($archivedList && $archivedList->num_rows > 0) {
                while ($row = $archivedList->fetch_assoc()) {
                    $title = htmlspecialchars($row['event_title'] ?? $row['event_id']);
                    $summary = htmlspecialchars($row['event_archive_summary'] ?? '');
                    $impact = htmlspecialchars($row['event_archive_impact_stats'] ?? '');
                    $mediaCount = (int)($row['media_count'] ?? 0);
                    $eid = urlencode($row['event_id']);
                    echo "<div style='background:#fff;padding:12px;border-radius:8px;margin-bottom:10px;box-shadow:0 1px 4px rgba(0,0,0,0.06);'>";
                    echo "<strong style='display:block;margin-bottom:6px;'>$title</strong>";
                    if ($summary) echo "<div style='font-size:13px;color:#555;margin-bottom:6px;'>".$summary."</div>";
                    if ($impact) echo "<div style='font-size:13px;color:#333;margin-bottom:8px;'><em>Impact:</em> ".$impact."</div>";
                    echo "<div style='display:flex;gap:8px;align-items:center;'>";
                    echo "<a class='action-btn' href='organizer_event_view.php?event_id=$eid'>View Details</a>";
                    echo "<a class='action-btn' href='organizer_event_media_gallery.php?event_id=$eid'>View Media ($mediaCount)</a>";
                    echo "</div></div>";
                }
            } else {
                echo "<div style='color:#666;font-size:14px;'>No archived events found.</div>";
            }
            ?>
          </div>
      </div>

      <!-- Archive Statistics -->
      <div class="management-section">
        <div class="section-header">
          <span class="section-icon">📊</span>
          <div>
            <h2 class="section-title">Archive Overview</h2>
            <p class="section-description">Your event history at a glance</p>
          </div>
        </div>
        <div class="action-grid">
          <div style="grid-column: 1/-1; padding: 15px; background: #f0f8ff; border-radius: 8px; border-left: 4px solid var(--color-secondary);">
            <p style="margin: 0 0 12px 0; font-size: 14px; color: #333; font-weight: 600;">📈 Your Archive Statistics:</p>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555; line-height: 1.8;">
              <li><strong>Total Archived Events:</strong> Keep your dashboard organized by archiving completed events</li>
              <li><strong>Media Library:</strong> Store photos, videos, and event documentation for future reference</li>
              <li><strong>Featured Highlights:</strong> Mark your best events for showcase and celebration</li>
              <li><strong>Legacy Building:</strong> Create a lasting record of your event organizing journey</li>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
