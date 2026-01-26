<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include "../connect.php";
$page_title = "Add Announcement";
include 'admin_header.php'; 


/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $start_date = $_POST['start_date'];
    $end_date   = $_POST['end_date'];
    $title      = $_POST['title'];
    $message    = $_POST['message'];

    /* Temporary static admin ID (replace with session later) */
    $created_by =$_SESSION["user_id"];

    /* ===============================
       Determine is_active based on date
       =============================== */
    $today = date('Y-m-d');
    $startDateOnly = date('Y-m-d', strtotime($start_date));

    // If created day != start date → inactive
    $is_active = ($startDateOnly === $today) ? 1 : 0;

    /* ===============================
       Generate announcement_id (a_001)
       =============================== */
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

    /* ===============================
       Insert into database
       =============================== */
    $stmt = $conn->prepare("
        INSERT INTO announcements
        (announcement_id, title, message, start_date, end_date, is_active, created_at, created_by)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
    ");

    $stmt->bind_param(
        "sssssis",
        $announcement_id,
        $title,
        $message,
        $start_date,
        $end_date,
        $is_active,
        $created_by
    );

   if (!$stmt->execute()) {
    die("Insert failed: " . $stmt->error);
}


    /* Redirect back to announcement list */
    header("Location: admin_announcement.php");
    exit;
}
?>

<div class="main-content">
    <main class="dashboard">
        <!-- Page Title -->
        <div class="page-title-bar">
            <a href="admin_announcement.php" class="icon-btn back-btn">↩</a>
            <h2>Add Announcement</h2>
        </div>

        <!-- Add Announcement Form -->
        <form method="POST" class="section-preview" style="max-width:700px; margin:auto;">

            <div class="form-group">
                <label>Start Date :</label>
                <input type="datetime-local" name="start_date" required>
            </div>

            <div class="form-group">
                <label>End Date :</label>
                <input type="datetime-local" name="end_date" required>
            </div>

            <div class="form-group">
                <label>Title :</label>
                <input type="text" name="title" required>
            </div>

            <div class="form-group">
                <label>Message :</label>
                <textarea name="message" rows="4" required></textarea>
            </div>

            <!-- ADD Button -->
            <div style="text-align:center; margin-top:20px;">
                <button type="submit" class="btn">ADD</button>
            </div>

        </form>
    </main>

<?php include 'admin_footer.php'; ?>