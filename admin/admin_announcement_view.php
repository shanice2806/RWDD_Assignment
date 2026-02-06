<?php
include "../connect.php";
$page_title = "View Announcement";
include "admin_header.php";
/* Validate ID */
if (!isset($_GET['id'])) {
    die("Announcement ID not provided.");
}

$announcement_id = $_GET['id'];

/* Fetch announcement */
$stmt = $conn->prepare("
    SELECT announcement_id, title, message, end_date,
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

<div class="main-content">
    <main class="dashboard">
        <div class="page-title-bar">
            <a href="admin_announcement.php" class="icon-btn back-btn">↩</a>
            <h2><?= htmlspecialchars($data['announcement_id']) ?></h2>
        </div>

        <!-- Announcement Details Card -->
        <div class="section-preview" style="max-width:700px; margin:auto;">

            <div class="form-group">
                <label>Announcement ID</label>
                <input type="text" value="<?= htmlspecialchars($data['announcement_id']) ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>End Date</label>
                <input type="text" value="<?= htmlspecialchars($data['end_date']) ?>" readonly>
            </div>

            <div class="form-group">
                <label>Status</label>
                <input type="text" value="<?= $data['is_active'] ? 'Active' : 'Inactive' ?>" readonly>
            </div>

            <div class="form-group">
                <label>Title</label>
                <input type="text" value="<?= htmlspecialchars($data['title']) ?>" readonly>
            </div>

            <div class="form-group">
                <label>Message</label>
                <input type="text" value="<?= htmlspecialchars($data['message']) ?>" readonly>
            </div>

            <div class="form-group">
                <label>Created At</label>
                <input type="text" value="<?= htmlspecialchars($data['created_at']) ?>" readonly>
            </div>

            <div class="form-group">
                <label>Created By</label>
                <input type="text" value="<?= htmlspecialchars($data['created_by']) ?>" readonly>
            </div>

            <!-- Edit Button -->
            <div style="text-align:center; margin-top:20px;">
                <a href="admin_announcement_edit.php?id=<?= urlencode($data['announcement_id']) ?>"
                class="btn">Edit</a>
            </div>

        </div>
    </main>

<?php include 'admin_footer.php'; ?>