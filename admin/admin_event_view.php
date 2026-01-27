<?php
include "../connect.php";

$page_title = "View Event";
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
   FETCH EVENT MEDIA
===================== */
$media_stmt = $conn->prepare("
    SELECT event_media_file_path
    FROM event_media
    WHERE event_id = ?
      AND event_media_is_hidden = 0
");
$media_stmt->bind_param("s", $event_id);
$media_stmt->execute();
$media_result = $media_stmt->get_result();


/* =====================
   FETCH TOTAL ATTENDANCE
===================== */
$att_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_attendance
    FROM attendance
    WHERE event_id = ?
");
$att_stmt->bind_param("s", $event_id);
$att_stmt->execute();
$attendance = $att_stmt->get_result()->fetch_assoc();

$total_attendance = $attendance['total_attendance'] ?? 0;


?>

<!-- =====================
     PAGE TITLE BAR
     ===================== -->
<div class="page-title-bar">
    <a href="admin_event.php" class="icon-btn back-btn">↩</a>
    <h2><?= htmlspecialchars($event['event_id']) ?></h2>
</div>

<!-- =====================
     EVENT DETAILS CARD
     ===================== -->
<div class="section-preview" style="max-width:800px; margin:auto;">

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
        <label>Status</label>
        <input type="text" value="<?= htmlspecialchars($event['event_status']) ?>" readonly>
    </div>

    <div class="form-group">
    <label>Total Attendance</label>
    <input type="text" value="<?= htmlspecialchars($total_attendance) ?>" readonly>
</div>


    <div class="form-group">
        <label>Event QR Code</label>
        <input type="text" value="<?= htmlspecialchars($event['event_qr_code_url']) ?>" readonly>
    </div>

    <!-- =====================
         EVENT MEDIA
         ===================== -->
    <div class="form-group">
        <label>Event Media</label>

        <div style="display:flex; flex-wrap:wrap; gap:10px;">
            <?php if ($media_result->num_rows > 0): ?>
                <?php while ($media = $media_result->fetch_assoc()): ?>
                    <img
                      src="<?= htmlspecialchars($media['event_media_file_path']) ?>"
                      alt="Event Media"
                      style="width:150px; height:150px; object-fit:cover; border:1px solid #ccc;"
                    >
                <?php endwhile; ?>
            <?php else: ?>
                <p>No media uploaded</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- =====================
         ACTION BUTTONS
         ===================== -->
    <div style="text-align:center; margin-top:25px;">
        <a href="admin_event_attendance.php?id=<?= urlencode($event_id) ?>" class="btn">
            View Attendance
        </a>

        <a href="admin_event_update.php?id=<?= urlencode($event_id) ?>" class="btn" style="margin-left:10px;">
            Update
        </a>
    </div>

</div>

<?php include "admin_footer.php"; ?>
