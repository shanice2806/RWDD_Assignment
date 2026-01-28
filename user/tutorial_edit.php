<?php
session_start();
require_once "../connect.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");
  exit();
}

$user_id = $_SESSION["user_id"];
$post_id = trim($_GET["id"] ?? "");


$userSql = "SELECT name, eco_points , profile_image
            FROM users
            WHERE user_id=? LIMIT 1";
$uStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($uStmt, "s", $user_id);
mysqli_stmt_execute($uStmt);
$uRes  = mysqli_stmt_get_result($uStmt);
$user  = mysqli_fetch_assoc($uRes) ?: ["name"=>"User","eco_points"=>0,"profile_image" => ""];
mysqli_stmt_close($uStmt);

$profileImg = "../images/profile.png";

if (!empty($user["profile_image"])) {
  $try = "../" . ltrim($user["profile_image"], "/");
  $disk = __DIR__ . "/../" . ltrim($user["profile_image"], "/");
  if (file_exists($disk)) {
    $profileImg = $try;
  }
}

$catSql = "SELECT content_category_id, content_name
           FROM content_categories
           WHERE is_active = 1
           ORDER BY content_name ASC";
$catRes = mysqli_query($conn, $catSql);

$post = null;
if ($post_id !== "") {
  $sql = "SELECT *
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

$mediaList = [];
if ($post) {
  $mSql = "SELECT media_id, file_path
           FROM post_media
           WHERE post_id=?
           ORDER BY media_id ASC";
  $mStmt = mysqli_prepare($conn, $mSql);
  mysqli_stmt_bind_param($mStmt, "s", $post_id);
  mysqli_stmt_execute($mStmt);
  $mRes = mysqli_stmt_get_result($mStmt);
  while ($m = mysqli_fetch_assoc($mRes)) {
    $mediaList[] = $m; 
  }
  mysqli_stmt_close($mStmt);
}

function h($s){ return htmlspecialchars($s ?? "", ENT_QUOTES, "UTF-8"); }
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
    .small{
      font-size:12px; color:#666; font-weight:800;
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
        <a class="user-top-btn" href="friends_add.php" title="Add Friend">👥</a>
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
            <h2 class="title">Edit Tutorial</h2>

            <form id="editForm"
                  action="tutorial_update.php"
                  method="POST"
                  enctype="multipart/form-data"
                  novalidate>

              <input type="hidden" name="post_id" value="<?php echo h($post["post_id"]); ?>">

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
                  <?php if ($catRes): ?>
                    <?php while($c = mysqli_fetch_assoc($catRes)): ?>
                      <option value="<?php echo h($c["content_category_id"]); ?>"
                        <?php echo ($post["content_category_id"] === $c["content_category_id"]) ? "selected" : ""; ?>>
                        <?php echo h($c["content_name"]); ?>
                      </option>
                    <?php endwhile; ?>
                  <?php endif; ?>
                </select>
              </div>

              <div class="row">
                <label>Add New Images (optional)</label>
                <input type="file" name="media_files[]" accept="image/*" multiple>
                <div class="hint">You can add more images here. Existing images will stay unless you delete them below.</div>
              </div>

              <div class="btn-row">
                <a class="btn secondary" href="tutorial_view.php">Back</a>
                <button type="submit" class="btn">Save Changes</button>
              </div>
            </form>

            <div class="section-title">Existing Images</div>

            <?php if (empty($mediaList)): ?>
              <p style="margin:0;color:#777;">No images yet.</p>
            <?php else: ?>
              <div class="img-grid">
                <?php foreach ($mediaList as $m): ?>
                  <div class="img-card">
                    <img src="<?php echo h($m["file_path"]); ?>" alt="Tutorial Image">
                    <div class="img-actions">
                      <span class="small"><?php echo h($m["media_id"]); ?></span>

                      <form action="tutorial_delete.php" method="POST" style="margin:0;">
                        <input type="hidden" name="post_id" value="<?php echo h($post_id); ?>">
                        <input type="hidden" name="media_id" value="<?php echo h($m["media_id"]); ?>">
                        <button type="submit" class="btn danger"
                          onclick="return confirm('Delete this image?');">
                          Delete
                        </button>
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
