<?php
include "../connect.php";

$page_title = "View Recycling Log";
include "admin_header.php";

/* Validate log ID */
if (!isset($_GET['id'])) {
    die("Log ID not provided.");
}

$log_id = $_GET['id'];

/* Fetch recycling log */
$stmt = $conn->prepare("
    SELECT 
        rl.log_id,
        rl.user_id,
        rl.material_id,
        rl.event_id,
        rl.weight_kg,
        rl.location,
        rl.photo_path,
        rl.submitted_at,
        rl.status,
        rl.points_awarded,
        rl.is_flagged,
        rl.flag_reason,
        m.materials_name
    FROM recycling_log rl
    LEFT JOIN materials m ON rl.material_id = m.material_id
    WHERE rl.log_id = ?
");
$stmt->bind_param("s", $log_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Recycling log not found.");
}

$log = $result->fetch_assoc();
?>

<div class="main-content">
<main class="dashboard">

<div class="page-title-bar">
    <a href="admin_recycling_log.php" class="icon-btn back-btn">↩</a>
    <h2><?= htmlspecialchars($log['log_id']) ?></h2>
</div>

<!-- =====================
     LOG DETAILS CARD
     ===================== -->
<div class="section-preview" style="max-width:800px; margin:auto;">

    <div class="form-group">
        <label>User ID</label>
        <input type="text" value="<?= htmlspecialchars($log['user_id']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Submitted At</label>
        <input type="text" value="<?= htmlspecialchars($log['submitted_at']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Location</label>
        <input type="text" value="<?= htmlspecialchars($log['location']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Event ID</label>
        <input type="text" value="<?= htmlspecialchars($log['event_id'] ?? '-') ?>" readonly>
    </div>

    <div class="form-group">
        <label>Material</label>
        <input type="text" value="<?= htmlspecialchars($log['materials_name'] ?? 'Unknown') ?>" readonly>
    </div>

    <div class="form-group">
        <label>Weight (KG)</label>
        <input type="text" value="<?= htmlspecialchars($log['weight_kg']) ?>" readonly>
    </div>

    <!-- =====================
     PHOTO PROOF
     ===================== -->
<div class="form-group">
    <label>Photo Proof</label>
    <div style="border:1px solid #ccc; padding:10px; text-align:center;">

        <?php
        $photoPath = !empty($log['photo_path'])
            ? "../images/recycling_proof/" . htmlspecialchars($log['photo_path'])
            : "";
        ?>

        <?php if (!empty($photoPath) && file_exists(__DIR__ . "/../images/recycling_proof/" . $log['photo_path'])): ?>
            <img
                src="<?= $photoPath ?>"
                alt="Photo Proof"
                style="max-width:100%; max-height:300px; object-fit:contain;"
            >
        <?php else: ?>
            <p>No photo uploaded</p>
        <?php endif; ?>

    </div>
</div>


    <div class="form-group">
        <label>Status</label>
        <input type="text" value="<?= htmlspecialchars($log['status']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Points Awarded</label>
        <input type="text" value="<?= htmlspecialchars($log['points_awarded']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Flagged</label>
        <input type="text" value="<?= $log['is_flagged'] ? 'Yes' : 'No' ?>" readonly>
    </div>

    <div class="form-group">
        <label>Reason</label>
        <input type="text" value="<?= htmlspecialchars($log['flag_reason'] ?? '-') ?>" readonly>
    </div>

    <!-- =====================
         UPDATE BUTTON
         ===================== -->
    <div style="text-align:center; margin-top:25px;">
        <a href="admin_recycling_log_update.php?id=<?= urlencode($log['log_id']) ?>" class="btn">
            Update
        </a>
    </div>

</div>
</div>
</main>

<?php include "admin_footer.php"; ?>
