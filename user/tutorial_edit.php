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
$id = trim($_GET["id"] ?? "");

$userSql = "SELECT name, eco_points, profile_image FROM users WHERE user_id=? LIMIT 1";
$uStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($uStmt, "s", $user_id);
mysqli_stmt_execute($uStmt);
$uRes  = mysqli_stmt_get_result($uStmt);
$user  = mysqli_fetch_assoc($uRes) ?: ["name"=>"User","eco_points"=>0,"profile_image"=>""];
mysqli_stmt_close($uStmt);

$profileImg = trim($user["profile_image"] ?? "");
if ($profileImg === "") $profileImg = "../assets/default-avatar.png";

$row = null;
if ($id !== "") {
  $sql = "SELECT * FROM posts WHERE post_id=? AND user_id=? LIMIT 1";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "ss", $id, $user_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);
}
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

    @media (max-width:768px){
      .page-container{ max-width:100%; padding:14px; }
    }

    .page-btn{
      display:inline-flex; align-items:center; justify-content:center; gap:8px;
      padding:10px 16px; border-radius:12px;
      background:#1e3a34; color:#ffffff; border:1px solid #1e3a34;
      font-size:15px; font-weight:800; text-decoration:none; cursor:pointer;
      transition:all .2s ease;
    }
    .page-btn:hover{ background:#3ba99c; border-color:#3ba99c; color:#ffffff; }
    button.page-btn{ appearance:none; -webkit-appearance:none; outline:none; }
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

          <?php if (!$row): ?>
            <h2 style="margin:0 0 10px;">Not found / Not your tutorial</h2>
            <p style="margin:0 0 14px;color:#666;">
              This tutorial does not belong to your account OR the id is wrong.
            </p>
            <a class="page-btn" href="tutorial_view.php">Back</a>

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
                <select name="difficulty_level" required>
                  <option value="Beginner" <?php echo ($row["difficulty_level"]==="Beginner")?"selected":""; ?>>easy</option>
                  <option value="Intermediate" <?php echo ($row["difficulty_level"]==="Intermediate")?"selected":""; ?>>medium</option>
                  <option value="Advanced" <?php echo ($row["difficulty_level"]==="Advanced")?"selected":""; ?>>hard</option>
                </select>
              </div>

              <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                <a class="page-btn" href="tutorial_view.php">Back</a>
                <button type="submit" class="page-btn">Save</button>
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
