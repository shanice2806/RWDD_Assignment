<?php
include "../connect.php";

$page_title = "Review Event";
include "admin_header.php";

/* =====================
   VALIDATE EVENT ID
===================== */
if (!isset($_GET['id'])) {
    die("Event ID not provided.");
}

$event_id = $_GET['id'];

/* =====================
   FETCH EVENT DETAILS
===================== */
$stmt = $conn->prepare("
    SELECT
        event_id,
        event_title,
        event_type,
        event_description,
        event_date_time,
        event_location,
        event_created_by,
        event_status,
        event_qr_code_url
    FROM events
    WHERE event_id = ?
");
$stmt->bind_param("s", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Event not found.");
}

$event = $result->fetch_assoc();

/* =====================
   HANDLE STATUS UPDATE
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $new_status = $_POST['event_status'];

    $update_stmt = $conn->prepare("
        UPDATE events
        SET event_status = ?
        WHERE event_id = ?
    ");
    $update_stmt->bind_param("ss", $new_status, $event_id);
    $update_stmt->execute();

    header("Location: admin_event_view.php?id=" . urlencode($event_id));
    exit;
}
?>

<!-- =====================
     PAGE TITLE BAR
     ===================== -->
<div class="page-title-bar">
    <a href="admin_event.php" class="icon-btn back-btn">↩</a>
    <h2><?= htmlspecialchars($event['event_id']) ?></h2>
</div>

<!-- =====================
     EVENT REView CARD
     ===================== -->
<div class="section-preview" style="max-width:800px; margin:auto;">

<form method="POST">

    <div class="form-group">
        <label>Event ID</label>
        <input type="text" value="<?= htmlspecialchars($event['event_id']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Event Title</label>
        <input type="text" value="<?= htmlspecialchars($event['event_title']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Event Type</label>
        <input type="text" value="<?= htmlspecialchars($event['event_type']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Event Description</label>
        <input type="text" value="<?= htmlspecialchars($event['event_description']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Date & Time</label>
        <input type="text" value="<?= htmlspecialchars($event['event_date_time']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Location</label>
        <input type="text" value="<?= htmlspecialchars($event['event_location']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Created By</label>
        <input type="text" value="<?= htmlspecialchars($event['event_created_by']) ?>" readonly>
    </div>

    <!-- =====================
         STATUS (EditABLE)
         ===================== -->
    <div class="form-group">
        <label>Event Status</label>
        <select name="event_status" required>
            <option value="Pending"   <?= $event['event_status'] === 'Pending'   ? 'selected' : '' ?>>Pending</option>
            <option value="Approved"  <?= $event['event_status'] === 'Approved'  ? 'selected' : '' ?>>Approved</option>
            <option value="Rejected"  <?= $event['event_status'] === 'Rejected'  ? 'selected' : '' ?>>Rejected</option>
            <option value="Cancelled" <?= $event['event_status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
    </div>

    <div class="form-group">
        <label>Event QR Code</label>
        <input type="text" value="<?= htmlspecialchars($event['event_qr_code_url']) ?>" readonly>
    </div>

    <!-- =====================
         ACTION BUTTONS
         ===================== -->
    <div style="display:flex; justify-content:center; gap:20px; margin-top:30px;">
        <a href="admin_event.php" class="btn">Cancel</a>
        <button type="submit" class="btn">Edit</button>
    </div>

</form>
</div>

<?php include "admin_footer.php"; ?>
