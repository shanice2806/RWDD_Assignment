<?php

session_start();
include "../connect.php";
$page_title = "Edit Announcement";
include 'admin_header.php';

if (!isset($_GET['id'])) {
    header("Location: admin_announcement.php");
    exit;
}

$announcement_id = $_GET['id'];


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


$success = "";
$error   = "";

/* Handle update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $end_date   = $_POST['end_date'];
    $is_active  = $_POST['is_active'];
    $title      = $_POST['title'];
    $message    = $_POST['message'];

    $update = $conn->prepare("
        UPDATE announcements
        SET end_date = ?, is_active = ?, title = ?, message = ?
        WHERE announcement_id = ?
    ");

    $update->bind_param(
        "sisss",
        $end_date,
        $is_active,
        $title,
        $message,
        $announcement_id
    );

    if ($update->execute()) {
        $success = "Announcement updated successfully.";
    } else {
        $error = "Failed to update announcement.";
    }
}

?>

<div class="main-content">
    <main class="dashboard">


        <div class="page-title-bar">
            <a href="admin_announcement.php?id=<?= urlencode($announcement_id) ?>" class="icon-btn back-btn">↩</a>
            <h2><?= htmlspecialchars($announcement_id) ?></h2>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>


        <form method="POST" class="section-preview" style="max-width:700px; margin:auto;">

            <div class="form-group">
                <label>Announcement ID</label>
                <input type="text" value="<?= htmlspecialchars($data['announcement_id']) ?>" readonly>
            </div>

            <div class="form-group">
                <label>End Date</label>
                <input type="datetime-local" name="end_date"
                    value="<?= date('Y-m-d\TH:i', strtotime($data['end_date'])) ?>" required>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="is_active">
                    <option value="1" <?= $data['is_active'] ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= !$data['is_active'] ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($data['title']) ?>" required>
            </div>

            <div class="form-group">
                <label>Message</label>
                <textarea name="message" rows="4" required><?= htmlspecialchars($data['message']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Created At</label>
                <input type="text" value="<?= htmlspecialchars($data['created_at']) ?>" readonly>
            </div>

            <div class="form-group">
                <label>Created By</label>
                <input type="text" value="<?= htmlspecialchars($data['created_by']) ?>" readonly>
            </div>


            <div style="display:flex; justify-content:center; gap:20px; margin-top:20px;">
                <button type="submit" class="btn save-btn">Edit</button>
            </div>

        </form>
    </main>

<?php include 'admin_footer.php'; ?>
