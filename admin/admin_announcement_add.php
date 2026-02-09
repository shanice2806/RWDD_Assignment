<?php
session_start();
include "../connect.php";
$page_title = "Add Announcement";
include 'admin_header.php'; 

$success = "";
$error   = "";

$end_date = "";
$title    = "";
$message  = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $end_date = $_POST['end_date'];
    $title    = trim($_POST['title']);
    $message  = trim($_POST['message']);

    $created_by = $_SESSION["user_id"];

    if ($end_date === "" || $title === "" || $message === "") {
        $error = "All fields are required.";
    } else {

        $today = date('Y-m-d');
        $is_active = ($today <= date('Y-m-d', strtotime($end_date))) ? 1 : 0;

        $idQuery = $conn->query("
            SELECT announcement_id
            FROM announcements
            ORDER BY announcement_id DESC
            LIMIT 1
        ");

        if ($idQuery->num_rows > 0) {
            $lastId = $idQuery->fetch_assoc()['announcement_id'];
            $num = (int) substr($lastId, 2) + 1;
        } else {
            $num = 1;
        }

        $announcement_id = 'a_' . str_pad($num, 3, '0', STR_PAD_LEFT);


        $stmt = $conn->prepare("
            INSERT INTO announcements
            (announcement_id, title, message, end_date, is_active, created_at, created_by)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");

        $stmt->bind_param(
            "ssssis",
            $announcement_id,
            $title,
            $message,
            $end_date,
            $is_active,
            $created_by
        );

        if ($stmt->execute()) {
            $success = "Announcement added successfully.";
            $end_date = "";
            $title    = "";
            $message  = "";
        } else {
            $error = "Failed to add announcement.";
        }
    }
}
?>

<div class="main-content">
    <main class="dashboard">


        <div class="page-title-bar">
            <a href="admin_announcement.php" class="icon-btn back-btn">↩</a>
            <h2>Add Announcement</h2>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>


        <form method="POST" class="section-preview" style="max-width:700px; margin:auto;">

            <div class="form-group">
                <label>End Date :</label>
                <input type="datetime-local" name="end_date"
                       value="<?= htmlspecialchars($end_date) ?>" required>
            </div>

            <div class="form-group">
                <label>Title :</label>
                <input type="text" name="title"
                       value="<?= htmlspecialchars($title) ?>" required>
            </div>

            <div class="form-group">
                <label>Message :</label>
                <textarea name="message" rows="4" required><?= htmlspecialchars($message) ?></textarea>
            </div>

            <div style="text-align:center; margin-top:20px;">
                <button type="submit" class="btn">Add</button>
            </div>

        </form>
    </main>

<?php include 'admin_footer.php'; ?>
