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
$id = $_GET["id"] ?? "";

$userSql = "SELECT name, eco_points, profile_image FROM users WHERE user_id='$user_id' LIMIT 1";
$uRes = mysqli_query($conn, $userSql);
$user = mysqli_fetch_assoc($uRes) ?: ["name"=>"User","eco_points"=>0,"profile_image"=>""];

$profileImg = trim($user["profile_image"] ?? "");
if ($profileImg === "") $profileImg = "../assets/default-avatar.png";

$sql = "SELECT * FROM posts WHERE post_id='$id' AND user_id='$user_id'";
$res = mysqli_query($conn, $sql);
$row = $res ? mysqli_fetch_assoc($res) : null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Tutorial</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">

  <style>
    .page-wrap{ padding-top:80px; background:#f5f7f8; min-height:100vh; }
    .page-container{ max-width:900px; margin:0 auto; padding:16px; }
    .card{ background:#fff; border:1px solid #ddd; border-radius:14px; padding:18px; }
    .row{ margin-bottom:12px; }
    .row label{ display:block; font-weight:800; margin-bottom:6px; }
    .row input, .row textarea{
      width:100%;
      padding:12px 14px;
      border:1px solid #ddd;
      border-radius:12px;
      font-size:16px;
      box-sizing:border-box;
      background:#fff;
    }
    .row textarea{ min-height:140px; resize:vertical; }

    @media (max-width:768px){
      .page-container{ max-width:100%; padding:14px; }
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
          <img src="<?php echo htmlspecialchars($profileImg); ?>" class="user-top-avatar" alt="Avatar">
          <span class="user-top-name"><?php echo htmlspecialchars($user["name"]); ?></span>
        </div>
      </div>

      <div class="user-top-center">
        <div style="font-weight:900;">Tutorial</div>
      </div>

      <div class="user-top-right">
        <span class="user-top-points">🪙 <?php echo (int)$user["eco_points"]; ?> points</span>
        <a href="tutorial_view.php" class="user-top-btn">←</a>
      </div>
    </div>

    <div class="page-wrap">
      <div class="page-container">
        <div class="card">

          <div style="font-size:12px;color:#999;margin-bottom:10px;">
            DEBUG: id=<?php echo htmlspecialchars($id); ?> | session_user=<?php echo htmlspecialchars($user_id); ?>
          </div>

          <?php if (!$row): ?>
            <h2 style="margin:0 0 10px;">Not found / Not your tutorial</h2>
            <p style="margin:0 0 14px;color:#666;">
              This tutorial does not belong to your account OR the id is wrong.
            </p>
            <a class="user-top-btn" href="tutorial_view.php">Back</a>

          <?php else: ?>
            <h2 style="margin:0 0 14px;">Edit Tutorial</h2>

            <form action="tutorial_update.php" method="POST">
              <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($row["post_id"]); ?>">

              <div class="row">
                <label>title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($row["title"]); ?>" required>
              </div>

              <div class="row">
                <label>body</label>
                <textarea name="body" required><?php echo htmlspecialchars($row["body"]); ?></textarea>
              </div>

              <div class="row">
                <label>difficultylevel</label>
                <input type="text" name="difficulty_level" value="<?php echo htmlspecialchars($row["difficulty_level"]); ?>" required>
              </div>

              <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                <a class="user-top-btn" href="tutorial_view.php">Back</a>
                <button type="submit" class="user-top-btn">Save</button>
              </div>
            </form>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <footer><p>&copy; 2026 ReLife Hub</p></footer>
  </div>
</div>

<script src="../user/user.js"></script>
</body>
</html>
