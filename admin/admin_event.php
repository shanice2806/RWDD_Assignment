<?php
include "../connect.php";

$page_title = "Recycling Event";
include "admin_header.php";

/* =====================
   INSIGHTS
   ===================== */

/* Total events */
$total_events = $conn->query("
    SELECT COUNT(*) AS total 
    FROM events
")->fetch_assoc()['total'];

/* Upcoming events */
$upcoming_events = $conn->query("
    SELECT COUNT(*) AS total
    FROM events
    WHERE event_date_time > NOW() AND event_status = 'Approved'
")->fetch_assoc()['total'];

/* Total pending events */
$total_pending_events = $conn->query("
    SELECT COUNT(*) AS total
    FROM events
    WHERE event_status = 'Pending'
")->fetch_assoc()['total'];


/* =====================
   GET FILTER VALUES
   ===================== */
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

/* =====================
   BUILD CONDITIONS
   ===================== */
$where  = [];
$params = [];
$types  = "";

/* Search by title or location */
if ($search !== '') {
    $where[] = "(event_title LIKE ? OR event_location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= "ss";
}

/* Status filter */
if ($status !== '') {
    $where[] = "event_status = ?";
    $params[] = $status;
    $types   .= "s";
}

/* =====================
   BASE QUERY
   ===================== */
$sql = "
    SELECT
        event_id,
        event_title,
        event_type,
        event_date_time,
        event_location,
        event_status,
        event_created_by,
        event_qr_code_url
    FROM events
";

/* Apply filters */
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY event_date_time DESC";

/* =====================
   PREPARE & EXECUTE
   ===================== */
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="main-content">
<main class="dashboard">

<h2 class="page-title">Recycling Event</h2>

<!-- =====================
     INSIGHTS
     ===================== -->
<div class="card-grid">

  <div class="card">
    <div class="icon">📅</div>
    <h4>Total Events</h4>
    <p><strong><?= $total_events ?></strong></p>
  </div>

  <div class="card">
    <div class="icon">🔜</div>
    <h4>Upcoming Events</h4>
    <p><strong><?= $upcoming_events ?></strong></p>
  </div>

 <div class="card">
  <div class="icon">⌛</div>
  <h4>Total Pending Events</h4>
  <p><strong><?= $total_pending_events ?></strong></p>
</div>

</div>

<!-- =====================
     SEARCH & FILTER
     ===================== -->
<div class="announcement-search-wrapper">
  <form method="GET" class="announcement-search-form">

    <input
      type="text"
      name="search"
      value="<?= htmlspecialchars($search) ?>"
      placeholder="Search Event"
      class="announcement-search-input"
    >

    <select name="status" class="announcement-search-input">
      <option value="">All Status</option>
      <option value="Pending"  <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
      <option value="Approved" <?= $status === 'Approved' ? 'selected' : '' ?>>Approved</option>
      <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>

    <button type="submit" class="announcement-search-btn">🔍</button>

  </form>
</div>

<!-- =====================
     EVENT TABLE
     ===================== -->
<div class="table-wrapper">
<table class="eco-table">
  <thead>
    <tr>
      <th>Event Type</th>
      <th>Event Title</th>
      <th>Location</th>
      <th>Date & Time</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>

  <tbody>
    <?php if ($result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['event_type']) ?></td>
          <td><?= htmlspecialchars($row['event_title']) ?></td>
          <td><?= htmlspecialchars($row['event_location']) ?></td>
          <td><?= date("Y-m-d H:i", strtotime($row['event_date_time'])) ?></td>
          <td><?= htmlspecialchars($row['event_status']) ?></td>
          <td class="action-col">
            <a href="admin_event_view.php?id=<?= $row['event_id'] ?>" class="btn">VIEW</a>
            <a href="admin_event_update.php?id=<?= $row['event_id'] ?>" class="btn">UPDATE</a>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td colspan="6" style="text-align:center;">No events found</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>
</div>

</main>
</div>

<?php include "admin_footer.php"; ?>
