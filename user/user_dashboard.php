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

$profileImg = "../images/profile.png";

if (!empty($user["profile_image"])) {
  $try = "../" . ltrim($user["profile_image"], "/");
  $disk = __DIR__ . "/../" . ltrim($user["profile_image"], "/");
  if (file_exists($disk)) {
    $profileImg = $try;
  }
}

if (!$user) {
  session_unset();
  session_destroy();
  header("Location: ../login/login.php");
  exit();
}




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
$postStmt = mysqli_prepare($conn, "SELECT post_id, title, post_created_at AS created_at
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


$badges = [];
if (!empty($user["badges"])) {
  $parts = array_map("trim", explode(",", $user["badges"]));
  foreach ($parts as $b) if ($b !== "") $badges[] = $b;
}

$joined = $user["created_at"] ? date("F Y", strtotime($user["created_at"])) : "—";
$lastLogin = $user["last_login"] ? date("d M Y, h:i A", strtotime($user["last_login"])) : "First login";

$notifications = [];
$notifError = "";

$notifSql = "
SELECT
  n.notification_id,
  n.event_id,
  n.message,
  n.audience_type,
  n.created_at
FROM notifications n
WHERE n.is_active = 1
AND (
  n.audience_type = 'participants'
  OR (
    n.audience_type = 'volunteers'
    AND EXISTS (
      SELECT 1 FROM volunteers v
      WHERE v.user_id = ? AND v.event_id = n.event_id
    )
  )
)
ORDER BY n.created_at DESC
LIMIT 5
";

$notifStmt = mysqli_prepare($conn, $notifSql);

if ($notifStmt) {
  mysqli_stmt_bind_param($notifStmt, "s", $user_id);
  if (mysqli_stmt_execute($notifStmt)) {
    $notifRes = mysqli_stmt_get_result($notifStmt);
    while ($n = mysqli_fetch_assoc($notifRes)) $notifications[] = $n;
  } else {
    $notifError = "Notification query fallback.";
  }
  mysqli_stmt_close($notifStmt);
} else {
  $notifError = "Notification statement prepare failed.";
}

if ($notifError !== "") {
  $notifications = [];
  $fallbackSql = "
    SELECT notification_id, event_id, message, audience_type, created_at
    FROM notifications
    WHERE is_active = 1 AND audience_type = 'participants'
    ORDER BY created_at DESC
    LIMIT 5
  ";
  $fallbackRes = mysqli_query($conn, $fallbackSql);
  if ($fallbackRes) {
    while ($n = mysqli_fetch_assoc($fallbackRes)) $notifications[] = $n;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile | ReLife Hub</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">

<style>
  .notif-wrap{
    margin: 16px 0;
    padding: 14px 16px;
    background: #fff;
    border: 1px solid var(--color-border);
    border-radius: 12px;
  }
  .notif-header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap: 12px;
    margin-bottom: 10px;
  }
  .notif-title{
    font-weight: 700;
    font-size: 18px;
    margin: 0;
  }
  .notif-small{
    color: var(--color-muted);
    font-size: 13px;
    margin-top: 2px;
  }
  .notif-list{
    display:flex;
    flex-direction:column;
    gap: 10px;
    margin: 0;
    padding: 0;
    list-style: none;
  }
  .notif-item{
    border: 1px solid var(--color-border);
    border-radius: 10px;
    padding: 10px 12px;
    background: var(--color-section);
  }
  .notif-meta{
    display:flex;
    flex-wrap:wrap;
    gap: 10px;
    font-size: 12px;
    color: var(--color-muted);
    margin-bottom: 6px;
  }
  .notif-msg{
    margin: 0;
    line-height: 1.35;
    word-break: break-word;
  }
  .notif-badge{
    display:inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    border: 1px solid var(--color-border);
    background: #fff;
    font-size: 12px;
    color: var(--color-text);
  }
  .notif-empty{
    color: var(--color-muted);
    margin: 0;
  }

  @media (max-width: 768px) {
    .notif-wrap{
      margin: 12px 0;
      padding: 12px;
      border-radius: 10px;
    }

    .notif-title{
      font-size: 16px;
    }

    .notif-small{
      font-size: 12px;
    }

    .notif-item{
      padding: 10px;
      border-radius: 10px;
    }

    .notif-meta{
      gap: 8px;
      font-size: 11px;
    }

    .notif-badge{
      font-size: 11px;
      padding: 2px 7px;
    }

    .notif-msg{
      font-size: 13px;
      line-height: 1.45;
    }
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

    <div class="notif-wrap" id="notifBox">
      <div class="notif-header">
        <div>
          <p class="notif-title">🔔 Notifications</p>
          <div class="notif-small">Latest updates for you (showing up to 5)</div>
        </div>
      </div>

      <?php if (count($notifications) === 0): ?>
        <p class="notif-empty">No notifications right now.</p>
      <?php else: ?>
        <ul class="notif-list">
          <?php foreach ($notifications as $n): ?>
            <li class="notif-item">
              <div class="notif-meta">
                <span class="notif-badge">
                  <?php echo htmlspecialchars($n["audience_type"]); ?>
                </span>
                <span>Event: <?php echo htmlspecialchars($n["event_id"]); ?></span>
                <span>
                  <?php echo $n["created_at"] ? date("d M Y, h:i A", strtotime($n["created_at"])) : ""; ?>
                </span>
              </div>
              <p class="notif-msg"><?php echo nl2br(htmlspecialchars($n["message"])); ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="profile-card">
      <h2>User Profile</h2>
      <p>
        <?php echo htmlspecialchars($user["name"]); ?> | Joined <?php echo htmlspecialchars($joined); ?>
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
          <p><?php echo (int)$totalLogs; ?></p>
        </div>
        <div>
          <h3>Posts</h3>
          <p><?php echo (int)$totalPosts; ?></p>
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

    <div class="section-preview">
      <h2>Recycling Status Overview</h2>
      <table>
        <tr>
          <th>Total Logs</th>
          <th>Pending</th>
          <th>Valid</th>
        </tr>
        <tr>
          <td><?php echo (int)$totalLogs; ?></td>
          <td><?php echo (int)$pendingLogs; ?></td>
          <td><?php echo (int)$validLogs; ?></td>
        </tr>
      </table>
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
              <td><?php echo htmlspecialchars($log["date"]); ?></td>
              <td><?php echo htmlspecialchars($log["material_type"]); ?></td>
              <td><?php echo htmlspecialchars($log["weight"]); ?></td>
              <td><?php echo htmlspecialchars($log["status"]); ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
          <a class="btn" href="../user/user_recycle.php" style="margin-left: 10px;">View My Logs</a>
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
