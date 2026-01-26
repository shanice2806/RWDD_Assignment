<?php
session_start();
require_once "../connect.php";

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
$user  = mysqli_fetch_assoc($uRes) ?: ["name"=>"User","eco_points"=>0,"profile_image"=>""];
mysqli_stmt_close($uStmt);

$profileImg = trim($user["profile_image"] ?? "");
if ($profileImg === "") $profileImg = "../assets/default-avatar.png";


$sql = "
  SELECT p.post_id, p.title, p.body, p.difficulty_level, p.post_created_at, u.name AS author_name
  FROM posts p
  JOIN users u ON u.user_id = p.user_id
  WHERE p.post_status = 'Public'
  ORDER BY p.post_created_at DESC
";
$res = mysqli_query($conn, $sql);

$uploadSuccess = (isset($_GET["upload"]) && $_GET["upload"] === "success");
$editSuccess   = (isset($_GET["edit"]) && $_GET["edit"] === "success");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tutorial</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">
  <style>
    .page-wrap{ padding-top:80px; min-height:100vh; background:#f5f7f8; }
    .page-card{ max-width:720px; margin:0 auto; padding:16px; }
    .list-card{ background:#fff; border:1px solid #ddd; border-radius:14px; overflow:hidden; }
    .list-row{ display:flex; gap:12px; padding:12px; border-bottom:1px solid #eee; align-items:center; }
    .list-row:last-child{ border-bottom:none; }
    .t-title{ font-weight:800; }
    .t-snippet{ color:#555; font-size:14px; margin-top:4px; }
    .t-meta{ color:#777; font-size:12px; margin-top:6px; }
    .t-actions{ margin-left:auto; display:flex; gap:8px; }
    .btn-view{ padding:10px 14px; border-radius:12px; border:1px solid #ddd; background:#fff; text-decoration:none; font-weight:800; color:#111; }
    .btn-view:hover{ background:#f3f3f3; }

    /* success modal */
    .modal-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; z-index:3000; }
    .modal{ width:min(420px,92vw); background:#fff; border:1px solid #ddd; border-radius:14px; padding:16px; text-align:center; }
    .modal-actions{ display:flex; gap:10px; justify-content:space-between; margin-top:12px; }
    @media (max-width:768px){
      .page-card{ max-width:100%; padding:14px; }
      .t-snippet{ font-size:15px; }
      .btn-view{ padding:12px 16px; font-size:16px; }
    }
  </style>
</head>

<body class="sidebar-collapsed">
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="user-layout">
  <?php include __DIR__ . "/user_sidebar.php"; ?>

  <div class="user-content">
    <!-- Top bar -->
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
        <a href="user_tutorial_add.php" class="user-top-btn">＋</a>
      </div>
    </div>

    <div class="page-wrap">
      <div class="page-card">

        <div class="list-card">
          <?php if (!$res || mysqli_num_rows($res) === 0): ?>
            <div class="list-row">No tutorial found.</div>
          <?php else: ?>
            <?php while ($row = mysqli_fetch_assoc($res)): ?>
              <?php
                $pid = $row["post_id"];
                $snippet = trim($row["body"] ?? "");
                if (mb_strlen($snippet) > 70) $snippet = mb_substr($snippet, 0, 70) . "...";
              ?>
              <div class="list-row">
                <div style="min-width:0;">
                  <div class="t-title"><?php echo htmlspecialchars($row["title"]); ?></div>
                  <div class="t-snippet"><?php echo htmlspecialchars($snippet); ?></div>
                  <div class="t-meta">
                    <?php echo htmlspecialchars($row["difficulty_level"]); ?> · by <?php echo htmlspecialchars($row["author_name"]); ?>
                  </div>
                </div>

                <div class="t-actions">
                  <a class="btn-view" href="tutorial_view.php?post_id=<?php echo urlencode($pid); ?>">View</a>
                  <a class="btn-view" href="tutorial_edit.php?post_id=<?php echo urlencode($pid); ?>">Edit</a>
                </div>
              </div>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <footer>
      <p>&copy; 2026 ReLife Hub</p>
    </footer>
  </div>
</div>

<div class="modal-backdrop" id="successModal">
  <div class="modal">
    <h3 id="successTitle">Successful</h3>
    <p id="successText">Done.</p>
    <div class="modal-actions">
      <a class="btn-view" href="tutorial_list.php">Back</a>
      <a class="btn-view" href="tutorial_list.php">OK</a>
    </div>
  </div>
</div>

<script src="../user/user.js"></script>
<script>
  const uploadSuccess = <?php echo $uploadSuccess ? "true" : "false"; ?>;
  const editSuccess   = <?php echo $editSuccess ? "true" : "false"; ?>;

  const modal = document.getElementById("successModal");
  const title = document.getElementById("successTitle");
  const text  = document.getElementById("successText");

  if (uploadSuccess) {
    title.textContent = "Upload Successful";
    text.textContent  = "+15 point";
    modal.style.display = "flex";
  }
  if (editSuccess) {
    title.textContent = "Changed Successful";
    text.textContent  = "Tutorial updated.";
    modal.style.display = "flex";
  }

  modal.addEventListener("click", (e) => { if (e.target === modal) modal.style.display = "none"; });
</script>
</body>
</html>
