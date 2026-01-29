<?php
include "../connect.php";

$page_title = "View Post";
include "admin_header.php";

/* =====================
   VALIDATE POST ID
===================== */
if (!isset($_GET['id'])) {
    die("Post ID not provided.");
}

$post_id = $_GET['id'];

/* =====================
   HANDLE VALID ACTION
===================== */
if (isset($_GET['action']) && $_GET['action'] === 'valid') {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        die("You must be logged in to validate reports.");
    }

    $admin_id = $_SESSION['user_id'];

    $conn->begin_transaction();

    try {
        /* Update POSTS */
        $stmt1 = $conn->prepare("
            UPDATE posts
            SET report_valid_status = 'VALID',
                is_flagged = 1,
                post_status = 'Hidden',
                validated_by = ?
            WHERE post_id = ?
        ");
        $stmt1->bind_param("ss", $admin_id, $post_id);
        $stmt1->execute();

        /* Update POST_REPORTS */
        $stmt2 = $conn->prepare("
            UPDATE post_reports
            SET is_valid = 1,
                validated_by = ?
            WHERE post_id = ?
        ");
        $stmt2->bind_param("ss", $admin_id, $post_id);
        $stmt2->execute();


/* =====================
   HANDLE INVALID ACTION
===================== */
if (isset($_GET['action']) && $_GET['action'] === 'invalid') {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        die("You must be logged in to validate reports.");
    }

    $admin_id = $_SESSION['user_id'];

    $conn->begin_transaction();

    try {
        /* Update POSTS (mark report as NOT valid) */
        $stmt1 = $conn->prepare("
            UPDATE posts
            SET report_valid_status = 'NOT_VALID',
                is_flagged = 0,
                validated_by = ?
            WHERE post_id = ?
        ");
        $stmt1->bind_param("ss", $admin_id, $post_id);
        $stmt1->execute();

        /* Update POST_REPORTS (mark all reports as invalid) */
        $stmt2 = $conn->prepare("
            UPDATE post_reports
            SET is_valid = 0,
                validated_by = ?
            WHERE post_id = ?
        ");
        $stmt2->bind_param("ss", $admin_id, $post_id);
        $stmt2->execute();

        $conn->commit();

        header("Location: admin_post_view.php?id=" . urlencode($post_id));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Invalid action failed: " . $e->getMessage());
    }
}


        
        /* =====================
   DEDUCT ECO POINTS (-15)
===================== */

/* Get post owner */
$ownerStmt = $conn->prepare("
    SELECT user_id
    FROM posts
    WHERE post_id = ?
");
$ownerStmt->bind_param("s", $post_id);
$ownerStmt->execute();
$ownerResult = $ownerStmt->get_result();

if ($ownerResult->num_rows === 0) {
    throw new Exception("Post owner not found.");
}

$post_owner_id = $ownerResult->fetch_assoc()['user_id'];

/* =====================
   GENERATE TRANSACTION ID
===================== */
$count_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM eco_points_transactions
");
$count = $count_result->fetch_assoc()['total'] + 1;
$transaction_id = 'ept_' . str_pad($count, 3, '0', STR_PAD_LEFT);

/* Eco points values */
$points_change = -15;
$description = "Points deducted due to a valid report on post {$post_id}";

/* Insert eco points transaction */
$insert = $conn->prepare("
    INSERT INTO eco_points_transactions
        (transaction_id, user_id, source_id, points_change, description, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");

$insert->bind_param(
    "sssds",
    $transaction_id,
    $post_owner_id,
    $post_id,
    $points_change,
    $description
);

$insert->execute();

if ($insert->affected_rows === 0) {
    throw new Exception("Eco points transaction insert failed.");
}

/* =====================
   UPDATE USER ECO POINTS (-15)
===================== */
$updateUserPoints = $conn->prepare("
    UPDATE users
    SET eco_points = eco_points + ?
    WHERE user_id = ?
");

$updateUserPoints->bind_param(
    "is",
    $points_change,   // this is -15
    $post_owner_id
);

$updateUserPoints->execute();

if ($updateUserPoints->affected_rows === 0) {
    throw new Exception("Failed to update user eco points.");
}


        $conn->commit();

        header("Location: admin_post_view.php?id=" . urlencode($post_id));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Validation failed: " . $e->getMessage());
    }
}

/* =====================
   FETCH POST
===================== */
$stmt = $conn->prepare("
    SELECT 
        post_id,
        user_id,
        content_category_id,
        title,
        body,
        difficulty_level,
        post_status,
        like_count,
        comment_count,
        report_count,
        report_valid_status,
        validated_by,
        is_flagged,
        post_created_at
    FROM posts
    WHERE post_id = ?
");
$stmt->bind_param("s", $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Post not found.");
}

$post = $result->fetch_assoc();

/* =====================
   FETCH POST MEDIA
===================== */
$mStmt = $conn->prepare("
    SELECT media_type, file_path, video_url
    FROM post_media
    WHERE post_id = ?
    ORDER BY order_number ASC
");
$mStmt->bind_param("s", $post_id);
$mStmt->execute();
$mediaResult = $mStmt->get_result();

/* =====================
   FETCH REPORTS
===================== */
$reports = [];
if ($post['report_count'] > 0) {
    $rStmt = $conn->prepare("
        SELECT reported_by, report_reason, reported_at
        FROM post_reports
        WHERE post_id = ?
        ORDER BY reported_at DESC
    ");
    $rStmt->bind_param("s", $post_id);
    $rStmt->execute();
    $reports = $rStmt->get_result();
}
?>

<div class="main-content">
<main class="dashboard">

<div class="page-title-bar">
    <a href="admin_manage_post.php" class="icon-btn back-btn">↩</a>
    <h2><?= htmlspecialchars($post['post_id']) ?></h2>
</div>

<!-- =====================
     POST DETAILS
===================== -->
<div class="section-preview" style="max-width:800px; margin:auto;">

    <div class="form-group">
        <label>User ID</label>
        <input type="text" value="<?= htmlspecialchars($post['user_id']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Date Posted</label>
        <input type="text" value="<?= htmlspecialchars($post['post_created_at']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Post Status</label>
        <input type="text" value="<?= htmlspecialchars($post['post_status']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Category ID</label>
        <input type="text" value="<?= htmlspecialchars($post['content_category_id']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Title</label>
        <input type="text" value="<?= htmlspecialchars($post['title']) ?>" readonly>
    </div>

    <div class="form-group">
        <label>Post Content</label>
        <textarea rows="6" readonly><?= htmlspecialchars($post['body']) ?></textarea>
    </div>

<?php if ($mediaResult->num_rows > 0): ?>
<div class="form-group">
    <label>Post Media</label>
    <div style="display:flex; flex-direction:column; gap:20px; align-items:center;">
        <?php while ($media = $mediaResult->fetch_assoc()): ?>
            <?php if ($media['media_type'] === 'image'): ?>
                <img src="../images/post_media/<?= htmlspecialchars($media['file_path']) ?>"
                     style="max-width:420px; border-radius:8px;">
            <?php elseif ($media['media_type'] === 'video'): ?>
                <iframe width="280" height="160"
                        src="<?= htmlspecialchars(str_replace('watch?v=', 'embed/', $media['video_url'])) ?>"
                        allowfullscreen></iframe>
            <?php endif; ?>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

</div>

<!-- =====================
     REPORT SUMMARY
===================== -->
<?php if ($post['report_count'] > 0): ?>
<div class="section-preview" style="max-width:800px; margin:auto; margin-top:30px;">

    <h3 style="text-align:center;">Report Summary</h3>

    <div class="form-group">
        <label>Total Reports</label>
        <input type="text" value="<?= (int)$post['report_count'] ?>" readonly>
    </div>

    <div class="form-group">
        <label>Report Validation Status</label>
        <input type="text" value="<?= $post['report_valid_status'] ?? 'Pending Review' ?>" readonly>
    </div>

    <div class="form-group">
        <label>Validated By</label>
        <input type="text" value="<?= $post['validated_by'] ?? '-' ?>" readonly>
    </div>

    <div class="form-group">
        <label>Report Reasons</label>
        <textarea rows="4" readonly><?php
            $reasons = [];
            $reports->data_seek(0);
            while ($r = $reports->fetch_assoc()) {
                $reasons[] = "- " . $r['report_reason'];
            }
            echo htmlspecialchars(implode("\n", $reasons));
        ?></textarea>
    </div>

    <div class="form-group">
        <label>Report Details</label>
        <div style="max-height:180px; overflow:auto;">
            <table class="eco-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Date</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $reports->data_seek(0);
                while ($r = $reports->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= htmlspecialchars($r['reported_by']) ?></td>
                        <td><?= htmlspecialchars($r['reported_at']) ?></td>
                        <td><?= htmlspecialchars($r['report_reason']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php if (empty($post['report_valid_status'])): ?>
<div style="display:flex; justify-content:center; gap:20px; margin-top:25px;">
    <a href="admin_post_view.php?id=<?= urlencode($post_id) ?>&action=valid" class="btn">Valid</a>
    <a href="admin_post_update.php?id=<?= urlencode($post_id) ?>&action=invalid" class="btn">Invalid</a>
</div>
<?php else: ?>
<p style="text-align:center; margin-top:20px; color:#555;">
    This report has already been reviewed.
</p>
<?php endif; ?>

</div>
<?php endif; ?>

</main>
</div>

<?php include "admin_footer.php"; ?>
