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

$user_id = $_SESSION["user_id"];

// Get search and filter parameters
$search_query = $_GET['search'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';

// Build SQL query for past events
$sql = "SELECT e.*, 
        (SELECT event_media_file_path FROM event_media WHERE event_id = e.event_id AND event_media_is_hidden = 0 LIMIT 1) as event_poster,
        (SELECT COUNT(*) FROM event_archive WHERE event_id = e.event_id) as has_recap,
        (SELECT COUNT(*) FROM event_media WHERE event_id = e.event_id) as media_count
        FROM events e
        WHERE e.event_created_by = ? 
        AND LOWER(e.event_status) = 'archived'";

$params = [$user_id];
$types = "s";

// Add search filter
if (!empty($search_query)) {
    $sql .= " AND e.event_title LIKE ?";
    $params[] = '%' . $search_query . '%';
    $types .= "s";
}

// Add event type filter
if (!empty($filter_type)) {
    $sql .= " AND e.event_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

// Add date filter
if (!empty($filter_date)) {
    switch ($filter_date) {
        case 'this_week':
            $sql .= " AND e.event_date_time >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'this_month':
            $sql .= " AND e.event_date_time >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'last_3_months':
            $sql .= " AND e.event_date_time >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            break;
        case 'last_6_months':
            $sql .= " AND e.event_date_time >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
            break;
        case 'this_year':
            $sql .= " AND YEAR(e.event_date_time) = YEAR(NOW())";
            break;
    }
}

$sql .= " ORDER BY has_recap DESC, e.event_date_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$events_result = $stmt->get_result();

// Fetch unique event types for filter dropdown
$types_query = "SELECT DISTINCT event_type FROM events WHERE event_created_by = ? AND event_type IS NOT NULL ORDER BY event_type";
$types_stmt = $conn->prepare($types_query);
$types_stmt->bind_param("s", $user_id);
$types_stmt->execute();
$types_result = $types_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Past Events - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
  <style>
    .filter-row {
      display: flex;
      gap: 15px;
      align-items: center;
      margin-bottom: 25px;
    }
    
    .filter-row label {
      font-weight: 500;
      font-size: 15px;
    }
    
    .filter-row select {
      flex: 1;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
      cursor: pointer;
    }
    
    .event-card-wrapper {
      display: grid;
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .past-event-card {
      display: flex;
      gap: 20px;
      background: white;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    }
    
    .event-poster-box {
      width: 200px;
      height: 280px;
      background: #e9ecef;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      overflow: hidden;
    }
    
    .event-poster-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .event-poster-placeholder {
      font-size: 64px;
      color: #adb5bd;
    }
    
    .event-info-box {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    
    .event-info-text h3 {
      font-size: 18px;
      margin-bottom: 10px;
      color: #333;
    }
    
    .event-info-text p {
      margin: 5px 0;
      color: #666;
      font-size: 14px;
    }
    
    .event-card-buttons {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }
  </style>
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <div class="main-content">
    <div class="event-management-container">
      <div class="page-header">
        <h1 class="page-title">Past Events</h1>
        <a href="organizer_archive_showcase_hub.php" class="action-btn">Back</a>
      </div>

      <div class="management-section">
        <form method="GET" action="" style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
          <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" 
                 placeholder="Search events..." 
                 style="flex: 1; padding: 12px 15px; border: 1px solid #ddd; border-radius: 25px; font-size: 14px;">
          <button type="submit" class="search-btn" style="border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">🔍</button>
        </form>

        <div class="filter-row">
          <label>Filter:</label>
          <select name="filter_date" id="filter_date" onchange="applyFilters()">
            <option value="">Date</option>
            <option value="this_week" <?= $filter_date == 'this_week' ? 'selected' : '' ?>>This Week</option>
            <option value="this_month" <?= $filter_date == 'this_month' ? 'selected' : '' ?>>This Month</option>
            <option value="last_3_months" <?= $filter_date == 'last_3_months' ? 'selected' : '' ?>>Last 3 Months</option>
            <option value="last_6_months" <?= $filter_date == 'last_6_months' ? 'selected' : '' ?>>Last 6 Months</option>
            <option value="this_year" <?= $filter_date == 'this_year' ? 'selected' : '' ?>>This Year</option>
          </select>
          
          <select name="filter_type" id="filter_type" onchange="applyFilters()">
            <option value="">Event Type</option>
            <?php
            if ($types_result && $types_result->num_rows > 0) {
                while ($type_row = $types_result->fetch_assoc()) {
                    $selected = ($filter_type == $type_row['event_type']) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($type_row['event_type']) . '" ' . $selected . '>';
                    echo htmlspecialchars($type_row['event_type']);
                    echo '</option>';
                }
            }
            ?>
          </select>
        </div>
      </div>

      <?php if ($events_result && $events_result->num_rows > 0): ?>
        <div class="event-card-wrapper">
          <?php while ($event = $events_result->fetch_assoc()): ?>
            <div class="past-event-card">
              <div class="event-poster-box">
                <?php if (!empty($event['event_poster'])): 
                  $poster_path = '../' . ltrim($event['event_poster'], '/');
                ?>
                  <img src="<?= htmlspecialchars($poster_path) ?>" alt="Event Poster">
                <?php else: ?>
                  <div class="event-poster-placeholder">🖼️</div>
                <?php endif; ?>
              </div>
              
              <div class="event-info-box">
                <div class="event-info-text">
                  <h3>Title: <?= htmlspecialchars($event['event_title']) ?></h3>
                  <p><strong>Date:</strong> <?= date('d/m/Y', strtotime($event['event_date_time'])) ?></p>
                  <p><strong>Status:</strong> Archived</p>
                </div>
                
                <div class="event-card-buttons">
                  <a href="organizer_event_detail.php?event_id=<?= htmlspecialchars($event['event_id']) ?>" class="btn-outline">View Details</a>
                  <a href="organizer_event_media_gallery.php?event_id=<?= htmlspecialchars($event['event_id']) ?>" class="btn-outline">View Media (<?= $event['media_count'] ?>)</a>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>

        <div style="text-align: center;">
          <button onclick="resetFilters()" class="action-btn" style="background: #444; border-color: #444; padding: 14px 48px; font-size: 15px;">Reset</button>
        </div>
      <?php else: ?>
        <div class="management-section" style="text-align: center; padding: 40px; color: #6c757d; font-size: 16px;">
          No archived events found.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function applyFilters() {
      const urlParams = new URLSearchParams(window.location.search);
      const search = urlParams.get('search') || '';
      const filterDate = document.getElementById('filter_date').value;
      const filterType = document.getElementById('filter_type').value;
      
      let url = 'organizer_past_events.php?';
      if (search) url += 'search=' + encodeURIComponent(search) + '&';
      if (filterDate) url += 'filter_date=' + encodeURIComponent(filterDate) + '&';
      if (filterType) url += 'filter_type=' + encodeURIComponent(filterType);
      
      window.location.href = url;
    }
    
    function resetFilters() {
      window.location.href = 'organizer_past_events.php';
    }
  </script>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
