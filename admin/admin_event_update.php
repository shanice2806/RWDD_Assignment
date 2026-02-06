<?php
session_start();
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
        registration_status,
        event_attendance_code
    FROM events
    WHERE event_id = ?
");
$stmt->bind_param("s", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Event not found.');
}

$event = $result->fetch_assoc();

/* =====================
   STATUS MESSAGE
===================== */
$success = "";
$error   = "";

/* =====================
   HANDLE STATUS UPDATE
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $new_status = $_POST['event_status'];

    if ($new_status === 'Approved') {

        // Approved → Registration Open
        $update_stmt = $conn->prepare("
            UPDATE events
            SET event_status = ?, registration_status = 'Open'
            WHERE event_id = ?
        ");
        $update_stmt->bind_param("ss", $new_status, $event_id);

    } elseif ($new_status === 'Cancelled') {

        // Cancelled → Registration Closed
        $update_stmt = $conn->prepare("
            UPDATE events
            SET event_status = ?, registration_status = 'Closed'
            WHERE event_id = ?
        ");
        $update_stmt->bind_param("ss", $new_status, $event_id);

    } else {

        // Pending / Rejected → only update event status
        $update_stmt = $conn->prepare("
            UPDATE events
            SET event_status = ?
            WHERE event_id = ?
        ");
        $update_stmt->bind_param("ss", $new_status, $event_id);
    }

    if ($update_stmt->execute()) {
        $success = "Event status updated successfully.";
        $event['event_status'] = $new_status;

        if ($new_status === 'Approved') {
            $event['registration_status'] = 'Open';
        } elseif ($new_status === 'Cancelled') {
            $event['registration_status'] = 'Closed';
        }

    } else {
        $error = "Failed to update event status.";
    }
}
?>

<div class="main-content">
<main class="dashboard">

<div class="page-title-bar">
    <a href="admin_event.php" class="icon-btn back-btn">↩</a>
    <h2><?= htmlspecialchars($event['event_id']) ?></h2>
</div>

<!-- STATUS MESSAGE -->
<?php if (!empty($success)): ?>
    <div class="alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- =====================
     EVENT REVIEW CARD
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

    <div class="form-group">
        <label>Registration Status</label>
        <input type="text" value="<?= htmlspecialchars($event['registration_status']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Attendance Code</label>
        <input type="text" value="<?= htmlspecialchars($event['event_attendance_code']) ?>" readonly>
    </div>

    <!-- =====================
         STATUS (EDITABLE)
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

    <!-- =====================
         ACTION BUTTONS
    ===================== -->
    <div style="display:flex; justify-content:center; gap:20px; margin-top:30px;">
        <button type="submit" class="btn">Update</button>
    </div>

</form>

</div>
</main>
</div>

<?php include "admin_footer.php"; ?>
