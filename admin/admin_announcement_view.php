<?php
include "../connect.php";
include 'admin_header.php';

/* Validate ID */
if (!isset($_GET['id'])) {
    die("Announcement ID not provided.");
}

$announcement_id = $_GET['id'];

/* Fetch announcement */
$stmt = $conn->prepare("
    SELECT announcement_id, title, message, start_date, end_date,
           is_active, created_at, created_by
    FROM announcements
    WHERE announcement_id = ?
");
$stmt->bind_param("s", $announcement_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Announcement not found.");
}

$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Announcement</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>


<main class="dashboard">
    <div class="page-title-bar">
  <a href="admin_announcement.php" class="icon-btn back-btn">↩</a>
  <h2><?= htmlspecialchars($data['announcement_id']) ?></h2>
</div>

    <!-- Announcement Details Card -->
    <div class="section-preview" style="max-width:700px; margin:auto;">

        <div class="form-group">
            <label>Announcement ID :</label>
            <input type="text" value="<?= htmlspecialchars($data['announcement_id']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Start Date :</label>
            <input type="text" value="<?= htmlspecialchars($data['start_date']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>End Date :</label>
            <input type="text" value="<?= htmlspecialchars($data['end_date']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Status :</label>
            <input type="text" value="<?= $data['is_active'] ? 'Active' : 'Inactive' ?>" readonly>
        </div>

        <div class="form-group">
            <label>Title :</label>
            <input type="text" value="<?= htmlspecialchars($data['title']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Message :</label>
            <input type="text" value="<?= htmlspecialchars($data['message']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Created At :</label>
            <input type="text" value="<?= htmlspecialchars($data['created_at']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Created By :</label>
            <input type="text" value="<?= htmlspecialchars($data['created_by']) ?>" readonly>
        </div>

        <!-- Edit Button -->
        <div style="text-align:center; margin-top:20px;">
            <a href="admin_announcement_edit.php?id=<?= urlencode($data['announcement_id']) ?>"
               class="btn">EDIT</a>
        </div>

    </div>

</main>

<footer>
    © 2026 ReLife Hub
</footer>

</body>
</html>
