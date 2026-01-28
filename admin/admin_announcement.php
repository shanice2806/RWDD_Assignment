<?php
include "../connect.php";

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

/* Stats */
$totalRow = $conn->query("SELECT COUNT(*) AS total FROM announcements")->fetch_assoc();
$total = (int)$totalRow['total'];

$activeRow = $conn->query("SELECT COUNT(*) AS active FROM announcements WHERE is_active = 1")->fetch_assoc();
$active = (int)$activeRow['active'];

$nextExpiryRow = $conn->query("
    SELECT MIN(end_date) AS next_expiry
    FROM announcements
    WHERE is_active = 1 AND end_date IS NOT NULL
")->fetch_assoc();
$next_expiry = $nextExpiryRow['next_expiry'] ?? null;

/* Table data */
$searchEscaped = $conn->real_escape_string($search);

$sql = "
    SELECT announcement_id, title, end_date, is_active, created_at, created_by
    FROM announcements
    WHERE title LIKE '%$searchEscaped%' OR announcement_id LIKE '%$searchEscaped%'
    ORDER BY created_at DESC
";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcement</title>
    <?php include 'admin_header.php'; ?>
</head>
<body>
    <div class="main-content">
    <main class="dashboard">
        <h2>Announcements</h2>

        <!-- 📊 Stats -->
        <div class="card-grid">
            <div class="card">
                <div class="icon">📄</div>
                <h3>Total Announcements</h3>
                <p><?= $total ?></p>
            </div>

            <div class="card">
                <div class="icon">✅</div>
                <h3>Active Announcements</h3>
                <p><?= $active ?></p>
            </div>

            <div class="card">
                <div class="icon">⏳</div>
                <h3>Next Expiry Date</h3>
                <p><?= $next_expiry ? htmlspecialchars($next_expiry) : "N/A" ?></p>
            </div>
        </div>

        <div class="announcement-search-wrapper">
            <form method="GET" action="" class="announcement-search-form">
                <input
                type="text"
                name="search"
                class="announcement-search-input"
                placeholder="Search announcement..."
                value="<?= htmlspecialchars($search) ?>"
                />
                <button type="submit" class="announcement-search-btn">🔍</button>
            </form>
        </div>

        <!-- ➕ Add button -->
        <div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
            <a href="admin_announcement_add.php" class="btn">+</a>
        </div>

        <!-- 📋 Announcement Table -->
        <table class="eco-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Announcement ID</th>
                    <th>Title</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Created By</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['announcement_id']) ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['end_date']) ?></td>
                        <td>
                        <?= ((int)$row['is_active'] === 1) ? "Active" : "Inactive" ?>
                        </td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td><?= htmlspecialchars($row['created_by']) ?></td>
                        <td>
                        <a class="btn" href="admin_announcement_view.php?id=<?= urlencode($row['announcement_id']) ?>">View</a>
                        <a class="btn" href="admin_announcement_edit.php?id=<?= urlencode($row['announcement_id']) ?>">Edit</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                    <td colspan="9" style="text-align:center;">No announcements found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

<?php include 'admin_footer.php'; // Include the new footer ?>
</body>
</html>
