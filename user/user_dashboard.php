<?php
session_start();
require_once "../connect.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");
  exit();
}

$role = strtolower(trim($_SESSION["role"] ?? ""));
if ($role === "admin") {
  header("Location: ../admin/admin_dashboard.php");
  exit();
}

$user_id = $_SESSION["user_id"];

$userSql = "SELECT user_id, name, email, role, eco_points, badges, created_at, account_status, last_login, profile_image
            FROM users
            WHERE user_id = ?
            LIMIT 1";
$userStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($userStmt, "s", $user_id);
mysqli_stmt_execute($userStmt);
$userRes = mysqli_stmt_get_result($userStmt);
$user = mysqli_fetch_assoc($userRes);
mysqli_stmt_close($userStmt);

if (!$user) {
  session_unset();
  session_destroy();
  header("Location: ../login/login.php");
  exit();
}

$profileImg = $user["profile_image"] ? "../" . $user["profile_image"] : "../images/diz1jrk-e0114a7d-478f-4478-94e5-cbe3a8cc7cc2.png";

function getCount($conn, $sql, $user_id) {
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "s", $user_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);
  return (int)($row["c"] ?? 0);
}

$totalLogs   = getCount($conn, "SELECT COUNT(*) AS c FROM recycling_log WHERE user_id = ?", $user_id);
$pendingLogs = getCount($conn, "SELECT COUNT(*) AS c FROM recycling_log WHERE user_id = ? AND status = 'pending'", $user_id);
$validLogs   = getCount($conn, "SELECT COUNT(*) AS c FROM recycling_log WHERE user_id = ? AND status = 'valid'", $user_id);

$totalPosts  = getCount($conn, "SELECT COUNT(*) AS c FROM posts WHERE user_id = ?", $user_id);

$recentLogs = [];
$logStmt = mysqli_prepare($conn, "SELECT rl.log_id,
       m.materials_name AS material_type,
       rl.weight_kg     AS weight,
       rl.submitted_at  AS date,
       rl.status
FROM recycling_log rl
JOIN materials m ON rl.material_id = m.material_id
WHERE rl.user_id = ?
ORDER BY rl.submitted_at DESC
LIMIT 5
");
if ($logStmt) {
  mysqli_stmt_bind_param($logStmt, "s", $user_id);
  mysqli_stmt_execute($logStmt);
  $logRes = mysqli_stmt_get_result($logStmt);
  while ($r = mysqli_fetch_assoc($logRes)) $recentLogs[] = $r;
  mysqli_stmt_close($logStmt);
}

$recentPosts = [];
$postStmt = mysqli_prepare($conn, "SELECT post_id, title,post_created_at AS created_at 
FROM posts WHERE user_id = ? 
ORDER BY post_created_at 
DESC LIMIT 5");


if ($postStmt) {
  mysqli_stmt_bind_param($postStmt, "s", $user_id);
  mysqli_stmt_execute($postStmt);
  $postRes = mysqli_stmt_get_result($postStmt);
  while ($p = mysqli_fetch_assoc($postRes)) $recentPosts[] = $p;
  mysqli_stmt_close($postStmt);
}

$badges = [];
if (!empty($user["badges"])) {
  $parts = array_map("trim", explode(",", $user["badges"]));
  foreach ($parts as $b) if ($b !== "") $badges[] = $b;
}

$joined = $user["created_at"] ? date("F Y", strtotime($user["created_at"])) : "—";
$lastLogin = $user["last_login"] ? date("d M Y, h:i A", strtotime($user["last_login"])) : "First login";

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile | ReLife Hub</title>


  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">

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
    <input class="user-top-search" type="text" placeholder="Search...">
    <button class="user-top-search-btn" type="submit">Search</button>
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


<div class="profile-card">
  <h2>User Profile</h2>
  <p>
    <?php echo ($user["name"]); ?> | Joined <?php echo ($joined); ?>
    <br>
    <span style="color: var(--color-muted);">Last login: <?php echo ($lastLogin); ?></span>
  </p>

  <div class="profile-stats">
    <div>
      <h3>Eco-Points</h3>
      <p><?php echo (int)$user["eco_points"]; ?></p>
    </div>
    <div>
      <h3>Recycling Logs</h3>
      <p><?php echo $totalLogs; ?></p>
    </div>
    <div>
      <h3>Posts</h3>
      <p><?php echo $totalPosts; ?></p>
    </div>
  </div>

  <div class="badges">
    <h3>Badges</h3>
    <?php if (count($badges) === 0): ?>
      <div class="alert-warning">No badges yet. Start recycling to earn your first badge!</div>
    <?php else: ?>
      <?php foreach ($badges as $b): ?>
        <span class="badge"><?php echo ($b); ?></span>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="section-preview">
  <h2>Recycling Status Overview</h2>
  <table>
    <tr>
      <th>Total Logs</th>
      <th>Pending</th>
      <th>Valid</th>
    </tr>
    <tr>
      <td><?php echo $totalLogs; ?></td>
      <td><?php echo $pendingLogs; ?></td>
      <td><?php echo $validLogs; ?></td>
    </tr>
  </table>

  <a class="btn" href="../user/user_recycle_add.php">+ Add Recycling Log</a>
  <a class="btn" href="../user/user_recycle.php" style="margin-left: 10px;">View My Logs</a>
</div>

<div class="section-preview">
  <h2>Recent Activity</h2>

  <h3>Recent Recycling Logs</h3>
  <?php if (count($recentLogs) === 0): ?>
    <div class="alert-warning">No recycling logs found. Add your first log now.</div>
  <?php else: ?>
    <table>
      <tr>
        <th>Date</th>
        <th>Material</th>
        <th>Weight</th>
        <th>Status</th>
      </tr>
      <?php foreach ($recentLogs as $log): ?>
        <tr>
          <td><?php echo ($log["date"]); ?></td>
          <td><?php echo ($log["material_type"]); ?></td>
          <td><?php echo ($log["weight"]); ?></td>
          <td><?php echo ($log["status"]); ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <h3>Recent Posts</h3>
  <?php if (count($recentPosts) === 0): ?>
    <div class="alert-warning">No posts yet. Share your first sustainability tip!</div>
  <?php else: ?>
    <table>
      <tr>
        <th>Title</th>
        <th>Created</th>
      </tr>
      <?php foreach ($recentPosts as $p): ?>
        <tr>
          <td><?php echo ($p["title"]); ?></td>
          <td><?php echo ($p["created_at"]); ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <a class="btn" href="../posts/post_add.php">+ Create Post</a>
  <a class="btn" href="../user/user_community.php" style="margin-left: 10px;">View Community</a>
</div>

  </div>
</div>

<footer>
  <p>&copy; 2026 ReLife Hub</p>
</footer>

<script src="../user/user.js"></script>

</body>
</html>
