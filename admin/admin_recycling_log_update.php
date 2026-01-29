<?php
include "../connect.php";
$page_title = "Update Recycling Log";
include "admin_header.php";

/* =====================
   VALIDATE ID
   ===================== */
if (!isset($_GET['id'])) {
    header("Location: admin_recycling_log.php");
    exit;
}

$log_id = $_GET['id'];

/* =====================
   FETCH LOG DATA
   ===================== */
$stmt = $conn->prepare("
    SELECT *
    FROM recycling_log
    WHERE log_id = ?
");
$stmt->bind_param("s", $log_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Recycling log not found.");
}

$data = $result->fetch_assoc();

/* =====================
   HANDLE UPDATE
   ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $status = $_POST['status'];
    $flag_reason = $_POST['flag_reason'] ?? NULL;

    /* Auto logic */
    if ($status === 'VALID') {
        $points = round($data['weight_kg'] * 10);
        $is_flagged = 0;
        $flag_reason = NULL;
    } elseif ($status === 'INVALID') {
        $points = 0;
        $is_flagged = 1;
        $flag_reason = $flag_reason ?: 'Invalid submission';
    } else { // PENDING
        $points = 0;
        $is_flagged = 0;
        $flag_reason = NULL;
    }

    $update = $conn->prepare("
        UPDATE recycling_log
        SET status = ?, points_awarded = ?, is_flagged = ?, flag_reason = ?
        WHERE log_id = ?
    ");
    $update->bind_param(
        "siiss",
        $status,
        $points,
        $is_flagged,
        $flag_reason,
        $log_id
    );
    $update->execute();

    header("Location: admin_recycling_log_view.php?id=" . urlencode($log_id));
    exit;
}
?>

<div class="main-content">
<main class="dashboard">

    <!-- PAGE TITLE -->
    <div class="page-title-bar">
        <a href="admin_recycling_log_view.php?id=<?= urlencode($log_id) ?>" class="icon-btn back-btn">↩</a>
        <h2><?= htmlspecialchars($log_id) ?></h2>
    </div>

    <!-- UPDATE FORM -->
    <form method="POST" class="section-preview" style="max-width:750px; margin:auto;">

        <div class="form-group">
            <label>User ID :</label>
            <input type="text" value="<?= htmlspecialchars($data['user_id']) ?>" readonly class="readonly">
        </div>

        <div class="form-group">
            <label>Submitted At :</label>
            <input type="text" value="<?= htmlspecialchars($data['submitted_at']) ?>" readonly class="readonly">
        </div>

        <div class="form-group">
            <label>Location :</label>
            <input type="text" value="<?= htmlspecialchars($data['location']) ?>" readonly class="readonly">
        </div>

        <div class="form-group">
            <label>Event ID :</label>
            <input type="text" value="<?= htmlspecialchars($data['event_id'] ?? '-') ?>" readonly class="readonly">
        </div>

        <div class="form-group">
            <label>Material ID :</label>
            <input type="text" value="<?= htmlspecialchars($data['material_id']) ?>" readonly class="readonly">
        </div>

        <div class="form-group">
            <label>Weight (KG) :</label>
            <input type="text" value="<?= htmlspecialchars($data['weight_kg']) ?>" readonly class="readonly">
        </div>

        
    <!-- =====================
         PHOTO PROOF
    ===================== -->
    <div class="form-group">
        <label>Photo Proof</label>

        <div style="
            border:1px solid #ccc;
            padding:15px;
            text-align:center;
            border-radius:6px;
            background:#fafafa;
        ">
            <?php if (!empty($data['photo_path'])): ?>
                <img
                    src="../images/recycling_proof/<?= htmlspecialchars($data['photo_path']) ?>"
                    alt="Recycling Proof"
                    style="max-width:100%; max-height:300px; object-fit:contain;"
                >
            <?php else: ?>
                <p style="color:#777;">No photo uploaded</p>
            <?php endif; ?>
        </div>
    </div>
        <!-- STATUS -->
        <div class="form-group">
            <label>Status :</label>
            <select name="status" id="statusSelect" required>
                <option value="PENDING" <?= $data['status'] === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                <option value="VALID"   <?= $data['status'] === 'VALID'   ? 'selected' : '' ?>>VALID</option>
                <option value="INVALID" <?= $data['status'] === 'INVALID' ? 'selected' : '' ?>>INVALID</option>
            </select>
        </div>

        <!-- FLAG REASON -->
        <div class="form-group">
            <label>Reason :</label>
            <textarea
                name="flag_reason"
                id="flagReason"
                rows="3"
                <?= $data['status'] !== 'INVALID' ? 'readonly class="readonly"' : '' ?>
                placeholder="State reason if invalid"><?= htmlspecialchars($data['flag_reason'] ?? '') ?></textarea>
        </div>

        <!-- POINTS -->
        <div class="form-group">
            <label>Points Awarded :</label>
            <input type="text"
                   value="<?= htmlspecialchars($data['points_awarded']) ?>"
                   readonly
                   class="readonly">
        </div>

        <!-- BUTTONS -->
        <div style="display:flex; justify-content:center; gap:20px; margin-top:25px;">
            <a href="admin_recycling_log_view.php?id=<?= urlencode($log_id) ?>" class="btn">Cancel</a>
            <button type="submit" class="btn save-btn">Update</button>
        </div>

    </form>
</main>
</div>

<script>
const statusSelect = document.getElementById('statusSelect');
const flagReason   = document.getElementById('flagReason');

function toggleFlagReason() {
    if (statusSelect.value === 'INVALID') {
        flagReason.removeAttribute('readonly');
        flagReason.classList.remove('readonly');
        flagReason.focus();
    } else {
        flagReason.value = '';
        flagReason.setAttribute('readonly', true);
        flagReason.classList.add('readonly');
    }
}
statusSelect.addEventListener('change', toggleFlagReason);
toggleFlagReason();
</script>

<?php include "admin_footer.php"; ?>
