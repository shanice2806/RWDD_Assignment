<?php
include "../connect.php";
$page_title = "Recycling Event";
include "admin_header.php";


$total_events = $conn->query("
    SELECT COUNT(*) AS total 
    FROM events
")->fetch_assoc()['total'];


$upcoming_events = $conn->query("
    SELECT COUNT(*) AS total
    FROM events
    WHERE event_date_time > NOW()
      AND event_status = 'Approved'
")->fetch_assoc()['total'];


$total_pending_events = $conn->query("
    SELECT COUNT(*) AS total
    FROM events
    WHERE event_status = 'Pending'
")->fetch_assoc()['total'];



$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';


$where  = [];
$params = [];
$types  = "";


if ($search !== '') {
    $where[] = "(e.event_title LIKE ? OR e.event_location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= "ss";
}


if ($status !== '') {
    $where[] = "e.event_status = ?";
    $params[] = $status;
    $types   .= "s";
}

$sql = "
    SELECT
        e.event_id,
        e.event_title,
        e.event_type,
        e.event_date_time,
        e.event_location,
        e.event_status,
        e.registration_status,
        e.event_attendance_code,
        COUNT(a.attendance_id) AS total_attendance
    FROM events e
    LEFT JOIN attendance a
        ON e.event_id = a.event_id
";


if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}


$sql .= "
    GROUP BY e.event_id
    ORDER BY e.event_date_time DESC
";

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
      <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
      <option value="Approved" <?= $status === 'Approved' ? 'selected' : '' ?>>Approved</option>
      <option value="Rejected" <?= $status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
      <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>

    <button type="submit" class="announcement-search-btn">🔍</button>

  </form>
</div>


<div class="table-wrapper">
<table class="eco-table">
  <thead>
    <tr>
      <th>Event Type</th>
      <th>Event Title</th>
      <th>Location</th>
      <th>Date & Time</th>
      <th>Status</th>
      <th>Total Attendance</th>
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
        <td><?= $row['total_attendance'] ?></td>
        <td class="action-col">
          <a href="admin_event_view.php?id=<?= urlencode($row['event_id']) ?>" class="btn">View</a>
          <a href="admin_event_update.php?id=<?= urlencode($row['event_id']) ?>" class="btn">Update</a>
        </td>
      </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr>
      <td colspan="9" style="text-align:center;">No events found</td>
    </tr>
  <?php endif; ?>
  </tbody>
</table>
</div>

</main>
</div>

<?php include "admin_footer.php"; ?>
