<?php
include "../connect.php";

$page_title = "Attendance Record";
include "admin_header.php";

/* =====================
   VALIDATE EVENT ID
===================== */
if (!isset($_GET['id'])) {
    die("Event ID not provided.");
}

$event_id = $_GET['id'];

/* =====================
   SEARCH
===================== */
$search = $_GET['search'] ?? '';

$where = "WHERE event_id = ?";
$params = [$event_id];
$types  = "s";

if ($search !== '') {
    $where .= " AND user_id LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

/* =====================
   FETCH ATTENDANCE
===================== */
$sql = "
    SELECT
        attendance_id,
        user_id,
        attendance_check_in_time,
        attendance_method
    FROM attendance
    $where
    ORDER BY attendance_check_in_time ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="main-content">
<main class="dashboard">
<div class="page-title-bar">
    <a href="admin_event_view.php?id=<?= urlencode($event_id) ?>" class="icon-btn back-btn">↩</a>
    <h2><?= htmlspecialchars($event_id) ?></h2>
</div>

<h2 style="text-align:center; margin:20px 0;">Attendance Record</h2>

<!-- =====================
     SEARCH BAR
===================== -->
<div class="announcement-search-wrapper">
    <form method="GET" class="announcement-search-form">
        <input type="hidden" name="id" value="<?= htmlspecialchars($event_id) ?>">

        <input
            type="text"
            name="search"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="Search User ID"
            class="announcement-search-input"
        >

        <button type="submit" class="announcement-search-btn">🔍</button>
    </form>
</div>

<!-- =====================
     ATTENDANCE TABLE
===================== -->
<div class="table-wrapper">
<table class="eco-table">
    <thead>
        <tr>
            <th>Attendance ID</th>
            <th>User ID</th>
            <th>Check-In Time</th>
            <th>Attendance Method</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['attendance_id']) ?></td>
                    <td><?= htmlspecialchars($row['user_id']) ?></td>
                    <td><?= htmlspecialchars($row['attendance_check_in_time']) ?></td>
                    <td><?= htmlspecialchars($row['attendance_method']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" style="text-align:center;">No attendance record found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
        </div>
</main>

<?php include "admin_footer.php"; ?>
