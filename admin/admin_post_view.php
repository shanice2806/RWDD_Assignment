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

    $update = $conn->prepare("
        UPDATE posts
        SET is_flagged = 0,
            report_count = 0
        WHERE post_id = ?
    ");
    $update->bind_param("s", $post_id);
    $update->execute();
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
   FETCH REPORTS (IF ANY)
   ===================== */
$reports = [];
if ($post['report_count'] > 0) {

    $rStmt = $conn->prepare("
        SELECT 
            reported_by,
            report_reason,
            reported_at
        FROM post_reports
        WHERE post_id = ?
        ORDER BY reported_at DESC
    ");
    $rStmt->bind_param("s", $post_id);
    $rStmt->execute();
    $reports = $rStmt->get_result();
}
?>

<!-- =====================
     PAGE TITLE BAR
     ===================== -->
<div class="page-title-bar">
    <a href="admin_manage_posts.php" class="icon-btn back-btn">↩</a>
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

    <!-- Hide status if VALID action was performed -->
    <?php if (!isset($_GET['action']) || $_GET['action'] !== 'valid'): ?>
    <div class="form-group">
        <label>Status</label>
        <input type="text" value="<?= htmlspecialchars($post['post_status']) ?>" readonly>
    </div>
    <?php endif; ?>

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

    <div class="form-group">
        <label>Like Count</label>
        <input type="text" value="<?= (int)$post['like_count'] ?>" readonly>
    </div>

    <div class="form-group">
        <label>Comment Count</label>
        <input type="text" value="<?= (int)$post['comment_count'] ?>" readonly>
    </div>

    <!-- =====================
         UPDATE BUTTON
         ===================== -->
    <div style="text-align:center; margin-top:25px;">
        <a href="admin_post_update.php?id=<?= urlencode($post_id) ?>" class="btn">
            UPDATE
        </a>
    </div>

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
        <label>Report Reasons</label>
        <textarea rows="3" readonly>
<?php
$reasons = [];
$reports->data_seek(0);
while ($r = $reports->fetch_assoc()) {
    $reasons[] = "- " . $r['report_reason'];
}
echo htmlspecialchars(implode("\n", $reasons));
?>
        </textarea>
    </div>

    <div class="form-group">
        <label>Report Details</label>
        <div style="max-height:180px; overflow:auto; border:1px solid #ccc;">
            <table class="eco-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Reported Date</th>
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

    <!-- =====================
         MODERATION ACTIONS
         ===================== -->
    <div style="display:flex; justify-content:center; gap:20px; margin-top:25px;">
        <a href="admin_post_view.php?id=<?= urlencode($post_id) ?>&action=valid" class="btn">
            VALID
        </a>
        <a href="admin_post_update.php?id=<?= urlencode($post_id) ?>&action=invalid" class="btn">
            INVALID
        </a>
    </div>

</div>

<?php endif; ?>

<?php include "admin_footer.php"; ?>
