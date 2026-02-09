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

// Get selected event and search query
$selected_event_id = $_GET['event_id'] ?? '';
$search_query = $_GET['search'] ?? '';

// Fetch all events for dropdown
$events_query = "SELECT event_id, event_title FROM events ORDER BY event_date_time DESC";
$events_result = $conn->query($events_query);

// Fetch comments based on filters
$comments = [];

// Build SQL query
$sql = "SELECT f.feedback_comments, u.name as user_name, e.event_title 
        FROM feedback f 
        LEFT JOIN users u ON f.user_id = u.user_id 
        LEFT JOIN events e ON f.event_id = e.event_id
        WHERE 1=1";

$params = [];
$types = "";

// Add event filter if selected
if (!empty($selected_event_id)) {
    $sql .= " AND f.event_id = ?";
    $params[] = $selected_event_id;
    $types .= "s";
}

// Add search filter if provided
if (!empty($search_query)) {
    $sql .= " AND f.feedback_comments LIKE ?";
    $params[] = '%' . $search_query . '%';
    $types .= "s";
}

$sql .= " ORDER BY f.feedback_id DESC";

$stmt = $conn->prepare($sql);

if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

$stmt->close();

// Handle export
if (isset($_POST['export'])) {
    $export_sql = "SELECT f.feedback_comments, u.name as user_name, e.event_title
                   FROM feedback f 
                   LEFT JOIN users u ON f.user_id = u.user_id 
                   LEFT JOIN events e ON f.event_id = e.event_id
                   WHERE 1=1";
    
    if (!empty($selected_event_id)) {
        $export_sql .= " AND f.event_id = ?";
    }
    
    $export_sql .= " ORDER BY f.feedback_id DESC";
    
    $export_stmt = $conn->prepare($export_sql);
    
    if (!empty($selected_event_id)) {
        $export_stmt->bind_param("s", $selected_event_id);
    }
    
    $export_stmt->execute();
    $export_result = $export_stmt->get_result();
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    $filename = !empty($selected_event_id) ? 'comments_event_' . $selected_event_id . '_' . date('Y-m-d') . '.csv' : 'comments_all_' . date('Y-m-d') . '.csv';
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Comment', 'User Name', 'Event']);
    
    while ($row = $export_result->fetch_assoc()) {
        fputcsv($output, [
            $row['feedback_comments'],
            $row['user_name'] ?? 'Anonymous',
            $row['event_title']
        ]);
    }
    
    fclose($output);
    $export_stmt->close();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comment List - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <div class="main-content">
    <div class="event-management-container">
      <div class="page-header">
        <h1 class="page-title">Comment List</h1>
        <a href="organizer_collaboration_feedback_hub.php" class="action-btn">Back</a>
      </div>

      <div class="management-section">
        <form method="GET" action="" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
          <label for="event-select" style="font-size: 16px; font-weight: 500;">Select Event:</label>
          <select name="event_id" id="event-select" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; min-width: 300px; flex: 1;">
          <option value="">-- Choose an Event --</option>
          <?php
          if ($events_result && $events_result->num_rows > 0) {
              while ($event = $events_result->fetch_assoc()) {
                  $selected = ($event['event_id'] == $selected_event_id) ? 'selected' : '';
                  echo '<option value="' . htmlspecialchars($event['event_id']) . '" ' . $selected . '>';
                  echo htmlspecialchars($event['event_title']);
                  echo '</option>';
              }
          }
          ?>
          </select>
          <button type="submit" class="action-btn" style="padding: 10px 20px;">View</button>
        </form>
      </div>

      <div class="management-section">
        <form method="GET" action="" style="display: flex; align-items: center; gap: 10px;">
          <?php if (!empty($selected_event_id)): ?>
            <input type="hidden" name="event_id" value="<?= htmlspecialchars($selected_event_id) ?>">
          <?php endif; ?>
          <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" 
                 placeholder="Search comments..." 
                 style="flex: 1; padding: 12px 15px; border: 1px solid #ddd; border-radius: 25px; font-size: 14px;">
          <button type="submit" class="search-btn" style="border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">🔍</button>
        </form>
      </div>

      <?php if (count($comments) > 0): ?>
        <div style="display: grid; gap: 15px; margin-bottom: 20px;">
          <?php foreach ($comments as $comment): ?>
            <div class="management-section">
              <div style="font-size: 15px; color: #333; margin-bottom: 8px; line-height: 1.6;">
                "<?= htmlspecialchars($comment['feedback_comments']) ?>"
              </div>
              <div style="font-size: 13px; color: #666;">
                - by <?= htmlspecialchars($comment['user_name'] ?? 'Anonymous') ?>
                <?php if (!empty($comment['event_title'])): ?>
                  <span style="color: #3ba99c; margin-left: 10px;">• <?= htmlspecialchars($comment['event_title']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <form method="POST" action="" style="text-align: right;">
          <?php if (!empty($selected_event_id)): ?>
            <input type="hidden" name="event_id" value="<?= htmlspecialchars($selected_event_id) ?>">
          <?php endif; ?>
          <button type="submit" name="export" class="action-btn" style="background: #444; border-color: #444; padding: 14px 32px; font-size: 15px;">Export Comments</button>
        </form>
      <?php else: ?>
        <div class="management-section" style="text-align: center; padding: 40px; color: #6c757d; font-size: 16px;">
          <?= !empty($search_query) ? 'No comments found matching your search.' : 'No comments available yet.' ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
