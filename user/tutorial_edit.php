<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "../connect.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");
  exit();
}

$user_id = $_SESSION["user_id"];
$post_id = trim($_GET["id"] ?? "");

$from = trim($_GET["from"] ?? "view");
$backUrl = "tutorial_view.php";
if ($from === "detail" && $post_id !== "") {
  $backUrl = "tutorial_detail.php?id=" . urlencode($post_id);
}

function h($s){ return htmlspecialchars($s ?? "", ENT_QUOTES, "UTF-8"); }
function go($url){
  header("Location: " . $url);
  exit();
}

$userSql = "SELECT name, eco_points, profile_image FROM users WHERE user_id=? LIMIT 1";
$uStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($uStmt, "s", $user_id);
mysqli_stmt_execute($uStmt);
$uRes  = mysqli_stmt_get_result($uStmt);
$user  = mysqli_fetch_assoc($uRes) ?: ["name"=>"User","eco_points"=>0,"profile_image"=>""];
mysqli_stmt_close($uStmt);

$profileImg = "../images/profile.png";
if (!empty($user["profile_image"])) {
  $try  = "../" . ltrim($user["profile_image"], "/");
  $disk = __DIR__ . "/../" . ltrim($user["profile_image"], "/");
  if (file_exists($disk)) $profileImg = $try;
}

$cats = [];
$catSql = "SELECT content_category_id, content_name
           FROM content_categories
           WHERE is_active = 1
           ORDER BY content_name ASC";
$catRes = mysqli_query($conn, $catSql);
if ($catRes) {
  while ($c = mysqli_fetch_assoc($catRes)) $cats[] = $c;
}

$post = null;
if ($post_id !== "") {
  $sql = "SELECT post_id, user_id, content_category_id, title, body, difficulty_level, post_status, post_created_at
          FROM posts
          WHERE post_id=? AND user_id=?
          LIMIT 1";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "ss", $post_id, $user_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $post = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);
}

if (!$post) {
  $post_id = ""; 
}

$media = [];
if ($post) {
  $mSql = "SELECT media_id, media_type, file_path, video_url, order_number
           FROM post_media
           WHERE post_id=?
           ORDER BY order_number ASC, media_id ASC";
  $mStmt = mysqli_prepare($conn, $mSql);
  mysqli_stmt_bind_param($mStmt, "s", $post_id);
  mysqli_stmt_execute($mStmt);
  $mRes = mysqli_stmt_get_result($mStmt);
  while ($m = mysqli_fetch_assoc($mRes)) $media[] = $m;
  mysqli_stmt_close($mStmt);
}

if ($post && $_SERVER["REQUEST_METHOD"] === "POST") {

  if (isset($_POST["delete_media"]) && isset($_POST["media_id"])) {

    $media_id = trim($_POST["media_id"]);
    if ($media_id === "") go("tutorial_edit.php?id=" . urlencode($post_id) . "&from=" . urlencode($from));

    mysqli_begin_transaction($conn);

    try {
      $getSql = "SELECT pm.media_id, pm.media_type, pm.file_path
                FROM post_media pm
                INNER JOIN posts p ON p.post_id = pm.post_id
                WHERE pm.media_id=? AND pm.post_id=? AND p.user_id=?
                LIMIT 1
                FOR UPDATE";
      $gStmt = mysqli_prepare($conn, $getSql);
      mysqli_stmt_bind_param($gStmt, "sss", $media_id, $post_id, $user_id);
      mysqli_stmt_execute($gStmt);
      $gRes = mysqli_stmt_get_result($gStmt);
      $row  = mysqli_fetch_assoc($gRes);
      mysqli_stmt_close($gStmt);

      if (!$row) {
        mysqli_rollback($conn);
        go("tutorial_edit.php?id=" . urlencode($post_id) . "&from=" . urlencode($from));
      }

      $delSql = "DELETE FROM post_media WHERE media_id=? AND post_id=? LIMIT 1";
      $dStmt = mysqli_prepare($conn, $delSql);
      mysqli_stmt_bind_param($dStmt, "ss", $media_id, $post_id);
      mysqli_stmt_execute($dStmt);
      mysqli_stmt_close($dStmt);

      mysqli_commit($conn);

      $type = strtolower(trim($row["media_type"] ?? ""));
      $file = trim($row["file_path"] ?? "");

      if ($type === "image" && $file !== "" && str_starts_with($file, "images/post_media/")) {
        $full = __DIR__ . "/../" . $file;
        if (is_file($full)) @unlink($full);
      }

      go("tutorial_edit.php?id=" . urlencode($post_id) . "&from=" . urlencode($from) . "&img_deleted=1");

    } catch (Throwable $e) {
      mysqli_rollback($conn);
      die("Delete media failed: " . h($e->getMessage()));
    }
  }

  if (isset($_POST["delete_video"]) && isset($_POST["media_id"])) {

    $media_id = trim($_POST["media_id"]);
    if ($media_id === "") go("tutorial_edit.php?id=" . urlencode($post_id) . "&from=" . urlencode($from));

    mysqli_begin_transaction($conn);

    try {
      $getSql = "SELECT pm.media_id
                FROM post_media pm
                INNER JOIN posts p ON p.post_id = pm.post_id
                WHERE pm.media_id=? AND pm.post_id=? AND p.user_id=? AND pm.media_type='video'
                LIMIT 1
                FOR UPDATE";
      $gStmt = mysqli_prepare($conn, $getSql);
      mysqli_stmt_bind_param($gStmt, "sss", $media_id, $post_id, $user_id);
      mysqli_stmt_execute($gStmt);
      $gRes = mysqli_stmt_get_result($gStmt);
      $row  = mysqli_fetch_assoc($gRes);
      mysqli_stmt_close($gStmt);

      if (!$row) {
        mysqli_rollback($conn);
        go("tutorial_edit.php?id=" . urlencode($post_id) . "&from=" . urlencode($from));
      }

      $delSql = "DELETE FROM post_media WHERE media_id=? AND post_id=? LIMIT 1";
      $dStmt = mysqli_prepare($conn, $delSql);
      mysqli_stmt_bind_param($dStmt, "ss", $media_id, $post_id);
      mysqli_stmt_execute($dStmt);
      mysqli_stmt_close($dStmt);

      mysqli_commit($conn);

      go("tutorial_edit.php?id=" . urlencode($post_id) . "&from=" . urlencode($from) . "&video_deleted=1");

    } catch (Throwable $e) {
      mysqli_rollback($conn);
      die("Delete video failed: " . h($e->getMessage()));
    }
  }
}

$images = [];
$videos = [];
foreach ($media as $m) {
  $t = strtolower(trim($m["media_type"] ?? ""));
  if ($t === "video") $videos[] = $m;
  else if ($t === "image") $images[] = $m;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Tutorial</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">

  <style>
    .page-wrap{ padding-top:80px; background:#f5f7f8; min-height:100vh; }
    .page-container{ max-width:980px; margin:0 auto; padding:16px; }
    .card{ background:#fff; border:1px solid #ddd; border-radius:14px; padding:18px; }

    .title{ font-size:24px; font-weight:900; margin:0 0 14px; }

    .row{ margin-bottom:12px; }
    .row label{ display:block; font-weight:800; margin-bottom:6px; }
    .row input, .row textarea, .row select{
      width:100%;
      padding:12px 14px;
      border:1px solid #ddd;
      border-radius:12px;
      font-size:16px;
      box-sizing:border-box;
      background:#fff;
    }
    .row textarea{ min-height:140px; resize:vertical; }

    .btn{
      display:inline-flex; align-items:center; justify-content:center;
      padding:10px 16px; border-radius:12px;
      background:#1e3a34; color:#fff;
      border:1px solid #1e3a34;
      font-weight:800;
      text-decoration:none;
      cursor:pointer;
    }
    .btn.secondary{ background:#fff; color:#1e3a34; border-color:#cfd6d3; }
    .btn.danger{ background:#b23a3a; border-color:#b23a3a; color:#fff; }

    .btn-row{
      display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;
      margin-top:10px;
    }

    .section-title{
      font-size:18px;
      font-weight:900;
      margin:18px 0 10px;
    }

    .hint{ font-size:13px; color:#6c757d; margin-top:6px; }

    .img-grid{
      display:grid;
      grid-template-columns: repeat(3, 1fr);
      gap:12px;
      margin-top:10px;
    }
    .img-card{
      border:1px solid #e5e5e5;
      border-radius:14px;
      overflow:hidden;
      background:#fff;
      display:flex;
      flex-direction:column;
    }
    .img-card img{
      width:100%;
      height:190px;
      object-fit:cover;
      background:#f2f2f2;
    }
    .img-card .img-actions{
      padding:10px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
    }
    .small{ font-size:12px; color:#666; font-weight:800; }

    .msg{
      padding:10px 12px;
      border-radius:10px;
      margin:10px 0;
      background:#eaffea;
      border:1px solid #b8e6b8;
      color:#0b5a0b;
      font-weight:700;
    }

    .video-box{
      border:1px solid #e5e5e5;
      border-radius:12px;
      padding:12px;
      margin-top:10px;
      background:#fff;
    }

    @media (max-width:768px){
      .page-container{ max-width:100%; padding:14px; }
      .img-grid{ grid-template-columns: repeat(2, 1fr); }
      .img-card img{ height:170px; }
    }
  </style>
</head>

<body class="sidebar-collapsed">
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="user-layout">
  <?php include __DIR__ . "/user_sidebar.php"; ?>

  <div class="user-content">
    <div class="user-top-bar">
      <div class="user-top-left">
        <button class="sidebar-toggle" id="userSidebarToggle" type="button">☰</button>
        <div class="user-top-user">
          <span class="user-top-name"><?php echo h($user["name"]); ?></span>
        </div>
      </div>

      <div class="user-top-center">
        <h1 style="color:white;">Tutorial</h1>
      </div>

      <div class="user-top-right">
        <span class="user-top-points">🪙 <?php echo (int)$user["eco_points"]; ?> points</span>
        <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
        <a class="user-top-btn" href="add_friends.php" title="Add Friend">👥</a>
        <a href="../login/logout.php" class="user-top-btn logout" title="Logout">❌</a>
      </div>
    </div>

    <div class="page-wrap">
      <div class="page-container">
        <div class="card">

          <?php if (!$post): ?>
            <h2 class="title">Not found / Not your tutorial</h2>
            <p style="margin:0;color:#666;">
              This tutorial does not exist OR it is not created by your account.
            </p>
            <div class="btn-row">
              <a class="btn secondary" href="tutorial_view.php">Back</a>
            </div>

          <?php else: ?>

            <?php if (isset($_GET["img_deleted"])): ?>
              <div class="msg">✅ Image deleted.</div>
            <?php endif; ?>
            <?php if (isset($_GET["video_deleted"])): ?>
              <div class="msg">✅ Video deleted.</div>
            <?php endif; ?>

            <h2 class="title">Edit Tutorial</h2>

            <form id="editForm"
                  action="tutorial_update.php"
                  method="POST"
                  enctype="multipart/form-data"
                  novalidate>

              <input type="hidden" name="post_id" value="<?php echo h($post["post_id"]); ?>">
              <input type="hidden" name="from" value="<?php echo h($from); ?>">

              <div class="row">
                <label>Title</label>
                <input type="text" name="title" value="<?php echo h($post["title"]); ?>" required>
              </div>

              <div class="row">
                <label>Body</label>
                <textarea name="body" required><?php echo h($post["body"]); ?></textarea>
              </div>

              <div class="row">
                <label>Difficulty Level</label>
                <select name="difficulty_level" required>
                  <option value="Beginner" <?php echo ($post["difficulty_level"]==="Beginner")?"selected":""; ?>>Beginner</option>
                  <option value="Intermediate" <?php echo ($post["difficulty_level"]==="Intermediate")?"selected":""; ?>>Intermediate</option>
                  <option value="Advanced" <?php echo ($post["difficulty_level"]==="Advanced")?"selected":""; ?>>Advanced</option>
                </select>
              </div>

              <div class="row">
                <label>Category</label>
                <select name="content_category_id" required>
                  <option value="" disabled>Select category</option>
                  <?php foreach($cats as $c): ?>
                    <option value="<?php echo h($c["content_category_id"]); ?>"
                      <?php echo ($post["content_category_id"] === $c["content_category_id"]) ? "selected" : ""; ?>>
                      <?php echo h($c["content_name"]); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="row">
                <label>Add New Images (optional)</label>
                <input type="file" name="media_files[]" accept="image/*" multiple>
                <div class="hint">delete the images below before you add a new image</div>
              </div>
              <div class="row">
                <label>Video URL (optional)</label>
                <input type="url" name="video_url"
                       placeholder="https://www.youtube.com/watch?v=..."
                       value="">
                <div class="hint">(If you already have a video,delete it before add a new one.)</div>
              </div>
              <div class="btn-row">
                <a class="btn secondary" href="<?php echo h($backUrl); ?>">Back</a>
                <button type="submit" class="btn">Save Changes</button>
              </div>
            </form>

            <div class="section-title">Existing Videos</div>
            <?php if (empty($videos)): ?>
              <p style="margin:0;color:#777;">No video yet.</p>
            <?php else: ?>
              <?php foreach ($videos as $v): ?>
                <div class="video-box">
                  <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
                    <div style="font-weight:800;color:#1e3a34;word-break:break-all;">
                      <?php echo h($v["video_url"]); ?>
                    </div>
                    <div style="display:flex;gap:10px;align-items:center;">
                      <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this video?');">
                        <input type="hidden" name="media_id" value="<?php echo h($v["media_id"]); ?>">
                        <button type="submit" class="btn danger" name="delete_video" value="1">Delete</button>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <div class="section-title">Existing Images</div>
            <?php if (empty($images)): ?>
              <p style="margin:0;color:#777;">No images yet.</p>
            <?php else: ?>
              <div class="img-grid">
                <?php foreach ($images as $m): ?>
                  <div class="img-card">
                    <?php
                      $src = trim($m["file_path"] ?? "");
                    ?>
                    <img src="<?php echo "../" . h($src); ?>" alt="Tutorial Image">
                    <div class="img-actions">
                      <span class="small"><?php echo h($m["media_id"]); ?></span>

                      <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this image?');">
                        <input type="hidden" name="media_id" value="<?php echo h($m["media_id"]); ?>">
                        <button type="submit" class="btn danger" name="delete_media" value="1">Delete</button>
                      </form>

                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

          <?php endif; ?>

        </div>
      </div>
    </div>

    <footer><p>&copy; 2026 ReLife Hub</p></footer>
  </div>
</div>

<script src="../user/user.js"></script>
<script>
  const form = document.getElementById("editForm");
  if (form) {
    form.addEventListener("submit", function (e) {
      const title = form.querySelector('input[name="title"]');
      const body  = form.querySelector('textarea[name="body"]');
      const level = form.querySelector('select[name="difficulty_level"]');
      const cat   = form.querySelector('select[name="content_category_id"]');

      if (!title.value.trim()) { alert("Please fill in Title."); title.focus(); e.preventDefault(); return; }
      if (!body.value.trim())  { alert("Please fill in Body."); body.focus(); e.preventDefault(); return; }
      if (!level.value.trim()) { alert("Please select Difficulty Level."); level.focus(); e.preventDefault(); return; }
      if (!cat.value.trim())   { alert("Please select Category."); cat.focus(); e.preventDefault(); return; }
    });
  }
</script>
</body>
</html>
