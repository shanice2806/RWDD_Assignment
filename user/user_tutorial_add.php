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

$userSql = "SELECT name, eco_points, profile_image FROM users WHERE user_id=? LIMIT 1";
$uStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($uStmt, "s", $user_id);
mysqli_stmt_execute($uStmt);
$uRes  = mysqli_stmt_get_result($uStmt);
$user  = mysqli_fetch_assoc($uRes) ?: ["name"=>"User","eco_points"=>0,"profile_image"=>""];
mysqli_stmt_close($uStmt);

$profileImg = trim($user["profile_image"] ?? "");
if ($profileImg === "") $profileImg = "../assets/default-avatar.png";

$catSql = "SELECT content_category_id, content_name
           FROM content_categories
           WHERE is_active = 1
           ORDER BY content_name ASC";
$catRes = mysqli_query($conn, $catSql);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload Tutorial</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">

  <style>
    .page-wrap{ padding-top:80px; background:#f5f7f8; min-height:100vh; }
    .page-container{ max-width:900px; margin:0 auto; padding:16px; }
    .card{ background:#fff; border:1px solid #ddd; border-radius:14px; padding:18px; }
    .card-title{ font-size:26px; font-weight:800; margin:0 0 14px; }

    .form-row{ margin-bottom:12px; }
    .form-row label{ display:block; font-weight:800; margin-bottom:6px; }
    .form-row input, .form-row textarea, .form-row select{
      width:100%; padding:12px 14px; border:1px solid #ddd; border-radius:12px;
      font-size:16px; box-sizing:border-box; background:#fff;
    }
    .form-row textarea{ min-height:130px; resize:vertical; }

    .modal-bg{ position:fixed; inset:0; background:rgba(0,0,0,.45);
      display:none; align-items:center; justify-content:center; z-index:5000; }
    .modal-uploadbox{ width:min(420px, 92vw); background:#fff; border:1px solid #ddd; border-radius:14px;
      padding:16px; text-align:center; }
    .modal-actions{ display:flex; gap:12px; justify-content:center; margin-top:14px; }

    @media (max-width:768px){
      .page-container{ max-width:100%; padding:14px; }
      .card-title{ font-size:22px; }
    }

    .page-btn{
      display:inline-flex; align-items:center; justify-content:center; gap:8px;
      padding:10px 16px; border-radius:12px;
      background:#1e3a34; color:#ffffff; border:1px solid #1e3a34;
      font-size:15px; font-weight:800; text-decoration:none; cursor:pointer;
      transition:all .2s ease;
    }
    .page-btn:hover{ background:#3ba99c; border-color:#3ba99c; color:#fff; }
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
          <span> Welcome!  <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?> </span>
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
          <h2 class="card-title">Upload Tutorial</h2>

          <form id="addForm" action="user_tutorial_process.php" method="POST">
            <div class="form-row">
              <label>title</label>
              <input type="text" name="title" required>
            </div>

            <div class="form-row">
              <label>body</label>
              <textarea name="body" required></textarea>
            </div>

            <div class="form-row">
              <label>difficultylevel</label>
              <select name="difficulty_level" required>
                <option value="Beginner">easy</option>
                <option value="Intermediate">medium</option>
                <option value="Advanced">hard</option>
              </select>
            </div>

            <div class="form-row">
              <label>category</label>
              <select name="content_category_id" required>
                <option value="" disabled selected>category</option>
                <?php if ($catRes): ?>
                  <?php while($c = mysqli_fetch_assoc($catRes)): ?>
                    <option value="<?php echo htmlspecialchars($c["content_category_id"]); ?>">
                      <?php echo htmlspecialchars($c["content_name"]); ?>
                    </option>
                  <?php endwhile; ?>
                <?php endif; ?>
              </select>
            </div>

            <div style="display:flex; gap:12px; justify-content:flex-end;">
              <a class="page-btn" href="tutorial_view.php">Back</a>
              <button type="button" class="page-btn" id="openConfirm">Upload</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <footer><p>&copy; 2026 ReLife Hub</p></footer>
  </div>
</div>

<div class="modal-bg" id="confirmModal">
  <div class="modal-uploadbox">
    <h3 style="margin:0 0 10px;">Are you Confirm want to Upload the Content?</h3>
    <div class="modal-actions">
      <button type="button" class="page-btn" id="noBtn">No</button>
      <button type="button" class="page-btn" id="yesBtn">Confirm</button>
    </div>
  </div>
</div>

<script src="../user/user.js"></script>
<script>
  const modal = document.getElementById("confirmModal");
  document.getElementById("openConfirm").onclick = () => modal.style.display = "flex";
  document.getElementById("noBtn").onclick = () => modal.style.display = "none";
  document.getElementById("yesBtn").onclick = () => document.getElementById("addForm").submit();
  modal.onclick = (e) => { if (e.target === modal) modal.style.display = "none"; };
</script>
</body>
</html>
