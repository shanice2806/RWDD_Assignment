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
  $sql = "SELECT post_id, title, body, difficulty_level
          FROM posts
          WHERE post_id=? AND user_id=?
          LIMIT 1";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "ss", $id, $user_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);
}

function prettyLevel($level) {
  if ($level === "Beginner") return "Easy";
  if ($level === "Intermediate") return "Medium";
  if ($level === "Advanced") return "Hard";
  return $level;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tutorial Detail</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">

  <style>
    .page-wrap{ padding-top:80px; background:#f5f7f8; min-height:100vh; }
    .page-container{ max-width:900px; margin:0 auto; padding:16px; }
    .card{ background:#fff; border:1px solid #ddd; border-radius:14px; padding:18px; }

    .info-row{ margin-bottom:14px; }
    .info-label{ font-weight:900; margin-bottom:6px; display:block; }
    .info-box{
      width:100%;
      padding:12px 14px;
      border:1px solid #ddd;
      border-radius:12px;
      font-size:16px;
      box-sizing:border-box;
      background:#fafafa;
      color:#222;
      white-space:pre-wrap; 
      word-break:break-word;
    }

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

    .btn-row{
      display:flex;
      gap:10px;
      justify-content:flex-end;
      flex-wrap:wrap;
      margin-top:14px;
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
<h1 style="color: white;">Tutorial</h1>
  </div>

      <div class="user-top-right">
        <span class="user-top-points">
      🪙 <?php echo (int)$user["eco_points"]; ?> points
        </span>
    <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
    <a class="user-top-btn" href="friends_add.php" title="Add Friend">👥</a>
    <a href="../login/logout.php" class="user-top-btn logout" title="Logout">❌</a>
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
            <h2>Tutorial Detail</h2>

            <div class="info-row">
              <span class="info-label">Title</span>
              <div class="info-box"><?php echo htmlspecialchars($row["title"]); ?></div>
            </div>

            <div class="info-row">
              <span class="info-label">Body</span>
              <div class="info-box"><?php echo htmlspecialchars($row["body"]); ?></div>
            </div>

            <div class="info-row">
              <span class="info-label">Difficulty Level</span>
              <div class="info-box"><?php echo htmlspecialchars(prettyLevel($row["difficulty_level"])); ?></div>
            </div>
            <div class="btn-row">
              <a class="page-btn" href="tutorial_view.php">Back</a>
              <a class="page-btn" href="tutorial_edit.php?id=<?php echo urlencode($row["post_id"]); ?>">Edit</a>
            </div>

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
