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

/* ===== 取用户资料（给 sidebar + topbar）===== */
$userSql = "SELECT name, eco_points, profile_image FROM users WHERE user_id=? LIMIT 1";
$uStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($uStmt, "s", $user_id);
mysqli_stmt_execute($uStmt);
$uRes = mysqli_stmt_get_result($uStmt);
$user = mysqli_fetch_assoc($uRes) ?: ["name"=>"User","eco_points"=>0,"profile_image"=>""];
mysqli_stmt_close($uStmt);

$profileImg = trim($user["profile_image"] ?? "");
if ($profileImg === "") $profileImg = "../assets/default-avatar.png";

/* ===== filters ===== */
$filterDiff = trim($_GET["diff"] ?? "all");
$filterCat  = trim($_GET["cat"] ?? "all");

/* category list */
$catSql = "SELECT content_category_id, content_name
           FROM content_categories
           WHERE is_active=1
           ORDER BY content_name ASC";
$catRes = mysqli_query($conn, $catSql);

/* ===== 取我的 posts（prepared）===== */
$sql = "SELECT post_id, title, difficulty_level, content_category_id, post_created_at
        FROM posts
        WHERE user_id = ?";

$params = [$user_id];
$types  = "s";

if ($filterDiff !== "all") {
  $sql .= " AND difficulty_level = ?";
  $params[] = $filterDiff;
  $types .= "s";
}
if ($filterCat !== "all") {
  $sql .= " AND content_category_id = ?";
  $params[] = $filterCat;
  $types .= "s";
}

$sql .= " ORDER BY post_created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

$flashSuccess = $_SESSION["flash_success"] ?? null;
if ($flashSuccess) unset($_SESSION["flash_success"]);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Tutorial</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">


  <style>
    .page-wrap{ padding-top:80px; background:#f5f7f8; min-height:100vh; }
    .page-container{ max-width:900px; margin:0 auto; padding:16px; }
    .card{ background:#fff; border:1px solid #ffffff; border-radius:14px; padding:18px; }

    .card-header{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      margin-bottom:10px;
    }
    .card-title{ font-size:26px; font-weight:800; margin:0; }

    .filters{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      margin:10px 0 6px;
    }
    .filters select{
      padding:10px 12px;
      border:1px solid #ddd;
      border-radius:12px;
      font-size:15px;
      background:#fff;
    }

    .list-item{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      padding:14px 10px;
      border-top:1px solid #eee;
    }
    .list-left b{ display:block; font-size:16px; }
    .list-left small{ color:#666; }

    .actions{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      justify-content:flex-end;
      min-width:240px;
    }

    @media (max-width:768px){
      .page-container{ max-width:100%; padding:14px; }
      .card-title{ font-size:22px; }
      .list-item{ flex-direction:column; align-items:flex-start; }
      .actions{ width:100%; justify-content:flex-start; min-width:0; }
      .actions .page-btn{ flex:1; text-align:center; }
    }

    .page-btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      padding:10px 16px;
      border-radius:12px;
      background:#1e3a34;
      color:#ffffff;
      border:1px solid #1e3a34;
      font-size:15px;
      font-weight:800;
      text-decoration:none;
      cursor:pointer;
      transition:all .2s ease;
    }
    .page-btn:hover{ background:#3ba99c; border-color:#3ba99c; color:#fff; }
    button.page-btn{ appearance:none; -webkit-appearance:none; outline:none; }

/* ===== Upload Success Popup (no class collision) ===== */
.success-modal-bg{
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 6000;
}

.success-modal-box{
  width: min(520px, 92vw);
  background: #fff;
  border: 2px solid #333;
  border-radius: 4px;
  padding: 26px 18px;
  text-align: center;
}

.success-modal-box h2{
  margin: 0 0 10px;
  font-size: 28px;
  font-weight: 800;
  line-height: 1.15;
}

.success-points{
  margin: 6px 0 18px;
  font-size: 18px;
  font-weight: 700;
}

.success-actions{
  display: flex;
  justify-content: space-between;
  gap: 14px;
  margin-top: 10px;
}

.success-btn{
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 0;
  border-radius: 6px;
  background: #444;
  color: #fff;
  text-decoration: none;
  font-weight: 800;
  border: none;
  cursor: pointer;
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
        <input class="user-top-search" type="text" placeholder="Search... (not connected)">
        <button class="user-top-search-btn" type="button">Search</button>
      </div>

      <div class="user-top-right">
        <span class="user-top-points">🪙 <?php echo (int)$user["eco_points"]; ?> points</span>
        <a href="user_dashboard.php" class="user-top-btn">🏠</a>
        <a href="../login/logout.php" class="user-top-btn logout">❌</a>
      </div>
    </div>

    <div class="page-wrap">
      <div class="page-container">
        <div class="card">

          <div class="card-header">
            <h2 class="card-title">My Tutorial</h2>
            <a href="user_tutorial_add.php" class="page-btn" title="Create Tutorial">＋</a>
          </div>

          <form method="get" class="filters">
            <select name="diff" onchange="this.form.submit()">
              <option value="all" <?php echo ($filterDiff==="all")?"selected":""; ?>>All Difficulty</option>
              <option value="Beginner" <?php echo ($filterDiff==="Beginner")?"selected":""; ?>>Beginner</option>
              <option value="Intermediate" <?php echo ($filterDiff==="Intermediate")?"selected":""; ?>>Intermediate</option>
              <option value="Advanced" <?php echo ($filterDiff==="Advanced")?"selected":""; ?>>Advanced</option>
            </select>

            <select name="cat" onchange="this.form.submit()">
              <option value="all" <?php echo ($filterCat==="all")?"selected":""; ?>>All Category</option>
              <?php if ($catRes): ?>
                <?php while($c = mysqli_fetch_assoc($catRes)): ?>
                  <option value="<?php echo htmlspecialchars($c["content_category_id"]); ?>"
                    <?php echo ($filterCat === $c["content_category_id"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($c["content_name"]); ?>
                  </option>
                <?php endwhile; ?>
              <?php endif; ?>
            </select>
          </form>

          <?php if (!$result || mysqli_num_rows($result) == 0): ?>
            <p style="margin:14px 0 0;">No tutorial found.</p>
          <?php else: ?>
            <?php while($row = mysqli_fetch_assoc($result)) { ?>
              <div class="list-item">
                <div class="list-left">
                  <b><?php echo htmlspecialchars($row["title"]); ?></b>
                  <small>
                    Difficulty: <?php echo htmlspecialchars($row["difficulty_level"]); ?>
                    • <?php echo htmlspecialchars($row["post_created_at"]); ?>
                  </small>
                </div>

                <div class="actions">
                  <a class="page-btn" href="tutorial_detail.php?id=<?php echo urlencode($row["post_id"]); ?>">View</a>
                  <a class="page-btn" href="tutorial_edit.php?id=<?php echo urlencode($row["post_id"]); ?>">Edit</a>
                  <a class="page-btn" href="tutorial_delete.php?id=<?php echo urlencode($row["post_id"]); ?>"
                     onclick="return confirm('Delete this tutorial?');">Delete</a>
                </div>
              </div>
            <?php } ?>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <footer><p>&copy; 2026 ReLife Hub</p></footer>
  </div>
</div>

<?php if ($flashSuccess && ($flashSuccess["type"] ?? "") === "upload"): ?>
  <div class="success-modal-bg" id="successModal">
    <div class="success-modal-box">
      <h2>Upload<br>Successful</h2>

      <div class="success-points">
        💰 +<?php echo (int)($flashSuccess["points"] ?? 0); ?> point
      </div>

      <div class="success-actions">
        <a class="success-btn" href="tutorial_view.php">Back</a>
        <button class="success-btn" type="button" id="successOk">OK</button>
      </div>
    </div>
  </div>
<?php endif; ?>

<script src="../user/user.js"></script>
<script>
  const successModal = document.getElementById("successModal");
  const successOk = document.getElementById("successOk");

  if (successModal) {
    // show
    successModal.style.display = "flex";

    // ok close
    if (successOk) {
      successOk.addEventListener("click", () => {
        successModal.style.display = "none";
      });
    }

    // click outside close
    successModal.addEventListener("click", (e) => {
      if (e.target === successModal) successModal.style.display = "none";
    });
  }

</script>
</body>
</html>
