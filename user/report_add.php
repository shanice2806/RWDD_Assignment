<?php
session_start();
require_once "../connect.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");
  exit();
}

$catSql = "SELECT content_category_id, content_name
           FROM content_categories
           WHERE is_active=1
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
    .row{ margin-bottom:12px; }
    .row label{ display:block; font-weight:800; margin-bottom:6px; }
    .row input, .row textarea, .row select{
      width:100%; padding:12px 14px; border:1px solid #ddd; border-radius:12px;
      font-size:16px; box-sizing:border-box;
    }
    .row textarea{ min-height:140px; }

    .modal-bg{ position:fixed; inset:0; background:rgba(0,0,0,.45);
      display:none; align-items:center; justify-content:center; z-index:5000; }
    .modal{ width:min(420px,92vw); background:#fff; border:1px solid #ddd; border-radius:14px;
      padding:16px; text-align:center; }
    .modal-actions{ display:flex; gap:12px; justify-content:center; margin-top:14px; }
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
        <div style="font-weight:900;">Tutorial</div>
      </div>
      <div class="user-top-right">
        <a href="tutorial_view.php" class="user-top-btn">←</a>
      </div>
    </div>

    <div class="page-wrap">
      <div class="page-container">
        <div class="card">
          <h2 style="margin:0 0 14px;">Upload Tutorial</h2>

          <form id="addForm" action="user_tutorial_process.php" method="POST">
            <div class="row">
              <label>title</label>
              <input type="text" name="title" required>
            </div>

            <div class="row">
              <label>body</label>
              <textarea name="body" required></textarea>
            </div>

            <div class="row">
              <label>difficultylevel</label>
              <select name="difficulty_level" required>
                <option value="Beginner">easy</option>
                <option value="Intermediate">medium</option>
                <option value="Advanced">hard</option>
              </select>
            </div>

            <div class="row">
              <label>category</label>
              <select name="content_category_id" required>
                <option value="" disabled selected>category</option>
                <?php while($c = mysqli_fetch_assoc($catRes)) { ?>
                  <option value="<?php echo $c["content_category_id"]; ?>">
                    <?php echo $c["content_name"]; ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
              <a class="user-top-btn" href="tutorial_view.php">Back</a>
              <button type="button" class="user-top-btn" id="openConfirm">Upload</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <footer><p>&copy; 2026 ReLife Hub</p></footer>
  </div>
</div>

<div class="modal-bg" id="confirmModal">
  <div class="modal">
    <h3 style="margin:0 0 10px;">Are you Confirm want to Upload the Content?</h3>
    <div class="modal-actions">
      <button type="button" class="user-top-btn" id="noBtn">No</button>
      <button type="button" class="user-top-btn" id="yesBtn">Confirm</button>
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
