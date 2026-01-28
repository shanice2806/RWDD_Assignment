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

$userSql = "SELECT name, eco_points, profile_image FROM users WHERE user_id = ? LIMIT 1";
$uStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($uStmt, "s", $user_id);
mysqli_stmt_execute($uStmt);
$uRes  = mysqli_stmt_get_result($uStmt);
$user  = mysqli_fetch_assoc($uRes) ?: ["name" => "User", "eco_points" => 0, "profile_image" => ""];
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
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload Tutorial</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">


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
          <span> Welcome! <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?> </span>
        </div>
      </div>

      <div class="user-top-center">
        <h1 style="color: white;">Tutorial</h1>
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
          <h2 class="card-title">Upload Tutorial</h2>

          <form id="addForm"
              action="user_tutorial_process.php"
              method="POST"
              enctype="multipart/form-data"
              novalidate>

            
            <div class="form-row">
              <label>Title</label>
              <input type="text" name="title" placeholder="e.g. How to recycle plastic bottles" required>
            </div>

            <div class="form-row">
              <label>Body</label>
              <textarea name="body" placeholder="Write your steps / explanation..." required></textarea>
            </div>

            <div class="form-row">
              <label>Difficulty Level</label>
              <select name="difficulty_level" required>
                <option value="Beginner">Beginner</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
              </select>
            </div>

            <div class="form-row">
              <label>Category</label>
              <select name="content_category_id" required>
                <option value="" disabled selected>Select category</option>
                <?php if ($catRes): ?>
                  <?php while($c = mysqli_fetch_assoc($catRes)): ?>
                    <option value="<?php echo htmlspecialchars($c["content_category_id"]); ?>">
                      <?php echo htmlspecialchars($c["content_name"]); ?>
                    </option>
                  <?php endwhile; ?>
                <?php endif; ?>
              </select>
            </div>

            <div class="form-row">
              <label>Upload Images (optional)</label>
              <input 
                type="file" 
                name="media_files[]" 
                accept="image/*"
                multiple
              >
            </div>

            <div style="display:flex; gap:12px; justify-content:flex-end;">
              <a class="btn" href="tutorial_view.php">Back</a>
              <button type="button" class="btn" id="openConfirm">Upload</button>
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
    <h3 style="margin:0 0 10px;">Confirm upload?</h3>
    <div class="modal-actions">
      <button type="button" class="btn" id="noBtn">No</button>
      <button type="button" class="btn" id="yesBtn">Confirm</button>
    </div>
  </div>
</div>

<script src="../user/user.js"></script>
<script>
  const modal   = document.getElementById("confirmModal");
  const form    = document.getElementById("addForm");

  const btnOpen = document.getElementById("openConfirm");
  const btnNo   = document.getElementById("noBtn");
  const btnYes  = document.getElementById("yesBtn");

  const title = form.querySelector('input[name="title"]');
  const body  = form.querySelector('textarea[name="body"]');
  const level = form.querySelector('select[name="difficulty_level"]');
  const cat   = form.querySelector('select[name="content_category_id"]');

  function showError(msg, el) {
    alert(msg);
    if (el) el.focus();
  }

  btnOpen.addEventListener("click", function (e) {
    e.preventDefault();
    modal.style.display = "flex";
  });

  btnNo.addEventListener("click", function (e) {
    e.preventDefault();
    modal.style.display = "none";
  });

  btnYes.addEventListener("click", function (e) {
    e.preventDefault();

    if (!title.value.trim()) return showError("Please fill in Title.", title);
    if (!body.value.trim())  return showError("Please fill in Body.", body);
    if (!level.value.trim()) return showError("Please select Difficulty Level.", level);
    if (!cat.value.trim())   return showError("Please select Category.", cat);

    modal.style.display = "none";

    if (form.requestSubmit) form.requestSubmit();
    else form.submit();
  });

  modal.addEventListener("click", function (e) {
    if (e.target === modal) modal.style.display = "none";
  });
</script>

</body>
</html>
