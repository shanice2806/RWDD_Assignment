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

$post = null;

if ($post_id !== "") {
  $sql = "SELECT 
            p.post_id, p.user_id, p.content_category_id,
            p.title, p.body, p.difficulty_level,
            p.post_status, p.post_created_at,
            u.name AS author_name,
            c.content_name AS category_name
          FROM posts p
          LEFT JOIN users u ON u.user_id = p.user_id
          LEFT JOIN content_categories c ON c.content_category_id = p.content_category_id
          WHERE p.post_id = ?
          LIMIT 1";

  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "s", $post_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $post = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);

  if ($post && strtolower($post["post_status"]) !== "public" && $post["user_id"] !== $user_id) {
    $post = null;
  }
}

$mediaList = [];
if ($post) {
  $mSql = "SELECT file_path
           FROM post_media
           WHERE post_id = ?
           ORDER BY media_id ASC";
  $mStmt = mysqli_prepare($conn, $mSql);
  mysqli_stmt_bind_param($mStmt, "s", $post_id);
  mysqli_stmt_execute($mStmt);
  $mRes = mysqli_stmt_get_result($mStmt);
  while ($m = mysqli_fetch_assoc($mRes)) {
    $mediaList[] = $m["file_path"];
  }
  mysqli_stmt_close($mStmt);
}

function h($s){ return htmlspecialchars($s ?? "", ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tutorial Detail</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">


  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">

  <style>
    .page-wrap{ padding-top:80px; background:#f5f7f8; min-height:100vh; }
    .page-container{ max-width:950px; margin:0 auto; padding:16px; }

    .card{
      background:#fff; border:1px solid #ddd; border-radius:14px; padding:18px;
    }

    .title{
      font-size:26px; font-weight:900; margin:0 0 8px;
    }

    .meta{
      display:flex; flex-wrap:wrap; gap:10px;
      margin:0 0 14px;
      color:#666; font-size:14px;
    }

    .badge{
      display:inline-flex; align-items:center;
      padding:6px 10px; border-radius:999px;
      border:1px solid #e5e5e5;
      background:#fafafa;
      font-weight:700;
      color:#333;
    }

    .body-text{
      white-space:pre-wrap;
      line-height:1.7;
      font-size:16px;
      color:#222;
      margin-top:12px;
    }

    .img-grid{
      display:grid;
      grid-template-columns: repeat(3, 1fr);
      gap:10px;
      margin-top:14px;
    }

    .img-grid img{
      width:100%;
      height:220px;
      object-fit:cover;
      border-radius:12px;
      border:1px solid #e5e5e5;
      background:#fff;
      cursor:pointer;
    }

    @media (max-width:768px){
      .page-container{ max-width:100%; padding:14px; }
      .title{ font-size:22px; }
      .img-grid{ grid-template-columns: repeat(2, 1fr); }
      .img-grid img{ height:180px; }
    }

    .btn-row{
      display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;
      margin-top:14px;
    }

    .btn{
      display:inline-flex; align-items:center; justify-content:center;
      padding:10px 16px; border-radius:12px;
      background:#1e3a34; color:#fff;
      border:1px solid #1e3a34;
      font-weight:800;
      text-decoration:none;
      cursor:pointer;
    }
    .btn.secondary{
      background:#ffffff; color:#1e3a34; border-color:#cfd6d3;
    }

    .lightbox{
      position:fixed; inset:0; background:rgba(0,0,0,.75);
      display:none; align-items:center; justify-content:center;
      z-index:9999;
      padding:18px;
    }
    .lightbox img{
      max-width:95vw;
      max-height:88vh;
      border-radius:12px;
      border:2px solid rgba(255,255,255,.2);
      background:#fff;
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
          <span class="user-top-name">Welcome! <?php echo h($user["name"]); ?></span>
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
            <h2 class="title">Not found</h2>
            <p style="margin:0;color:#666;">
              Tutorial not found OR you don't have permission to view it.
            </p>
            <div class="btn-row">
              <a class="btn secondary" href="tutorial_view.php">Back</a>
            </div>

          <?php else: ?>
            <h2 class="title"><?php echo h($post["title"]); ?></h2>

            <div class="meta">
              <span class="badge">User: <?php echo h($post["author_name"] ?: "Unknown"); ?></span>
              <span class="badge">Category: <?php echo h($post["category_name"] ?: "No Category"); ?></span>
              <span class="badge">Difficulty Level: <?php echo h($post["difficulty_level"]); ?></span>
              <span class="badge">Time: <?php echo h($post["post_created_at"]); ?></span>
            </div>

            <?php if (!empty($mediaList)): ?>
              <div class="img-grid" id="imgGrid">
                <?php foreach ($mediaList as $src): ?>
                  <img src="<?php echo h($src); ?>" alt="Tutorial Image">
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p style="margin:0;color:#777;">No images for this tutorial.</p>
            <?php endif; ?>

            <div class="body-text"><?php echo h($post["body"]); ?></div>

            <div class="btn-row">
              <a class="btn secondary" href="tutorial_view.php">Back</a>

              <?php if ($post["user_id"] === $user_id): ?>
                <a class="btn" href="tutorial_edit.php?id=<?php echo h($post["post_id"]); ?>">Edit</a>
              <?php endif; ?>
            </div>

          <?php endif; ?>

        </div>
      </div>
    </div>

    <footer><p>&copy; 2026 ReLife Hub</p></footer>
  </div>
</div>

<div class="lightbox" id="lightbox">
  <img id="lightboxImg" src="" alt="Preview">
</div>

<script src="../user/user.js"></script>
<script>
  const grid = document.getElementById("imgGrid");
  const lightbox = document.getElementById("lightbox");
  const lightboxImg = document.getElementById("lightboxImg");

  if (grid) {
    grid.addEventListener("click", (e) => {
      if (e.target.tagName.toLowerCase() === "img") {
        lightboxImg.src = e.target.src;
        lightbox.style.display = "flex";
      }
    });
  }

  if (lightbox) {
    lightbox.addEventListener("click", () => {
      lightbox.style.display = "none";
      lightboxImg.src = "";
    });
  }
</script>
</body>
</html>
