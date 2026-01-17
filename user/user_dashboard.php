<?php
session_start();
require_once "../connect.php";

// Must login
if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");
  exit();
}

// Only allow User role (optional but recommended)
// If your role values are like "User", "Admin", "Event Organizer"
$role = strtolower(trim($_SESSION["role"] ?? ""));
if ($role === "admin") {
  header("Location: ../admin/admin_dashboard.php"); // change if your admin dashboard name differs
  exit();
}

$user_id = $_SESSION["user_id"];

// --------------------
// Fetch user profile
// --------------------
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
  // Session exists but user removed from DB
  session_unset();
  session_destroy();
  header("Location: ../login/login.php");
  exit();
}

// Profile image fallback
$profileImg = $user["profile_image"] ? "../" . $user["profile_image"] : "../images/profile.png";

// --------------------
// Stats: recycling logs
// --------------------
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

// --------------------
// Stats: posts
// --------------------
$totalPosts  = getCount($conn, "SELECT COUNT(*) AS c FROM posts WHERE user_id = ?", $user_id);

// --------------------
// Recent activity lists (optional, nice for dashboard)
// --------------------
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
                                  FROM posts
                                  WHERE user_id = ?
                                  ORDER BY post_created_at DESC
                                  LIMIT 5");
if ($postStmt) {
  mysqli_stmt_bind_param($postStmt, "s", $user_id);
  mysqli_stmt_execute($postStmt);
  $postRes = mysqli_stmt_get_result($postStmt);
  while ($p = mysqli_fetch_assoc($postRes)) $recentPosts[] = $p;
  mysqli_stmt_close($postStmt);
}

// Badge parsing (your badges column could be NULL or "badge1,badge2")
$badges = [];
if (!empty($user["badges"])) {
  // supports comma-separated badges
  $parts = array_map("trim", explode(",", $user["badges"]));
  foreach ($parts as $b) if ($b !== "") $badges[] = $b;
}

// Friendly dates
$joined = $user["created_at"] ? date("F Y", strtotime($user["created_at"])) : "—";
$lastLogin = $user["last_login"] ? date("d M Y, h:i A", strtotime($user["last_login"])) : "First login";

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Dashboard | ReLife Hub</title>

  <!-- Use ABSOLUTE path so it always loads -->
  <link rel="stylesheet" href="/RWDD_Assignment/css/style.css">
</head>
<body>

<header>
  <h1>ReLife Hub</h1>
  <p>Eco • Modern • Sustainable</p>
</header>

<nav>
  <a href="dashboard.php">Home</a>
  <a href="../events/events.php">Events</a>
  <a href="../rewards/rewards.php">Rewards</a>
  <a href="../profile/profile.php">Profile</a>
  <a href="../friends/friends.php">Friends</a>
  <a href="../login/logout.php" onclick="return confirm('Logout now?');">Logout</a>
</nav>

<div class="search-bar">
  <input type="text" placeholder="Search ReLife Hub...">
  <button type="button">Search</button>
</div>

<!-- Profile Summary -->
<div class="profile-card">
  <img src="<?php echo htmlspecialchars($profileImg); ?>" alt="Profile Picture">
  <h2><?php echo htmlspecialchars($user["name"]); ?></h2>
  <p>
    <?php echo htmlspecialchars($user["role"]); ?> | Joined <?php echo htmlspecialchars($joined); ?>
    <br>
    <span style="color: var(--color-muted);">Last login: <?php echo htmlspecialchars($lastLogin); ?></span>
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
        <span class="badge"><?php echo htmlspecialchars($b); ?></span>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Status Overview -->
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

  <a class="btn" href="../recycling/recycle_add.php">+ Add Recycling Log</a>
  <a class="btn" href="../recycling/recycle_list.php" style="margin-left: 10px;">View My Logs</a>
</div>

<!-- Recent Activity -->
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
          <td><?php echo htmlspecialchars($log["date"]); ?></td>
          <td><?php echo htmlspecialchars($log["material_type"]); ?></td>
          <td><?php echo htmlspecialchars($log["weight"]); ?></td>
          <td><?php echo htmlspecialchars($log["status"]); ?></td>
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
          <td><?php echo htmlspecialchars($p["title"]); ?></td>
          <td><?php echo htmlspecialchars($p["created_at"]); ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <a class="btn" href="../posts/post_add.php">+ Create Post</a>
  <a class="btn" href="../posts/post_list.php" style="margin-left: 10px;">View Community</a>
</div>

<footer>
  <p>&copy; 2026 ReLife Hub | Designed for Eco-School Sustainability</p>
  <p><a href="#">Privacy Policy</a> | <a href="#">Terms of Use</a> | <a href="#">Contact Us</a></p>
</footer>

</body>
</html>
