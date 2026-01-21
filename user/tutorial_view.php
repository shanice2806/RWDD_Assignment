<?php
session_start();
require_once "../connect.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");
  exit();
}

$user_id = $_SESSION["user_id"];

$userSql = "SELECT name, eco_points, profile_image FROM users WHERE user_id='$user_id' LIMIT 1";
$uRes = mysqli_query($conn, $userSql);
$user = mysqli_fetch_assoc($uRes) ?: ["name"=>"User","eco_points"=>0,"profile_image"=>""];

$profileImg = trim($user["profile_image"] ?? "");
if ($profileImg === "") $profileImg = "../assets/default-avatar.png";

$filterDiff = trim($_GET["diff"] ?? "all");
$filterCat  = trim($_GET["cat"] ?? "all");

$catSql = "SELECT content_category_id, content_name
           FROM content_categories
           WHERE is_active=1
           ORDER BY content_name ASC";
$catRes = mysqli_query($conn, $catSql);

$sql = "SELECT post_id, title, difficulty_level, content_category_id, post_created_at
        FROM posts
        WHERE user_id='$user_id'";

if ($filterDiff !== "all") $sql .= " AND difficulty_level='$filterDiff' ";
if ($filterCat  !== "all") $sql .= " AND content_category_id='$filterCat' ";

$sql .= " ORDER BY post_created_at DESC";
$result = mysqli_query($conn, $sql);
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
      background : blue;
    }

    @media (max-width:768px){
      .page-container{ max-width:100%; padding:14px; }
      .card-title{ font-size:22px; }

      .list-item{ flex-direction:column; align-items:flex-start; }
      .actions{ width:100%; justify-content:flex-start; min-width:0; }
      .actions .user-top-btn{ flex:1; text-align:center; }
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
        <input class="user-top-search" type="text" placeholder="Search...">
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
            <a href="user_tutorial_add.php" class="user-top-btn" title="Create Tutorial">＋</a>
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
                  <a class="user-top-btn" href="tutorial_detail.php?id=<?php echo $row["post_id"]; ?>">View</a>
                  <a class="user-top-btn" href="tutorial_edit.php?id=<?php echo $row["post_id"]; ?>">Edit</a>
                  <a class="user-top-btn" href="tutorial_delete.php?id=<?php echo $row["post_id"]; ?>"
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

<script src="../user/user.js"></script>
</body>
</html>
