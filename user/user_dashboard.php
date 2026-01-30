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

function h($str) {
  return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

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

$profileImg = "../images/profile.png";
if (!empty($user["profile_image"])) {
  $try  = "../" . ltrim($user["profile_image"], "/");
  $disk = __DIR__ . "/../" . ltrim($user["profile_image"], "/");
  if (file_exists($disk)) $profileImg = $try;
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
$pendingLogs = getCount($conn, "SELECT COUNT(*) AS c FROM recycling_log WHERE user_id = ? AND UPPER(status) = 'PENDING'", $user_id);
$validLogs   = getCount($conn, "SELECT COUNT(*) AS c FROM recycling_log WHERE user_id = ? AND UPPER(status) = 'VALID'", $user_id);
$totalPosts  = getCount($conn, "SELECT COUNT(*) AS c FROM posts WHERE user_id = ?", $user_id);


$recentLogs = [];
$logSql = "SELECT rl.log_id,
                  m.materials_name AS material_type,
                  rl.weight_kg     AS weight,
                  rl.submitted_at  AS date,
                  rl.status
           FROM recycling_log rl
           JOIN materials m ON rl.material_id = m.material_id
           WHERE rl.user_id = ?
           ORDER BY rl.submitted_at DESC
           LIMIT 5";
$logStmt = mysqli_prepare($conn, $logSql);
if ($logStmt) {
  mysqli_stmt_bind_param($logStmt, "s", $user_id);
  mysqli_stmt_execute($logStmt);
  $logRes = mysqli_stmt_get_result($logStmt);
  while ($r = mysqli_fetch_assoc($logRes)) $recentLogs[] = $r;
  mysqli_stmt_close($logStmt);
}

$recentPosts = [];
$postSql = "SELECT post_id, title, post_created_at AS created_at
            FROM posts
            WHERE user_id = ?
            ORDER BY post_created_at DESC
            LIMIT 5";
$postStmt = mysqli_prepare($conn, $postSql);
if ($postStmt) {
  mysqli_stmt_bind_param($postStmt, "s", $user_id);
  mysqli_stmt_execute($postStmt);
  $postRes = mysqli_stmt_get_result($postStmt);
  while ($p = mysqli_fetch_assoc($postRes)) $recentPosts[] = $p;
  mysqli_stmt_close($postStmt);
}

$notifications = [];

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
  }
  mysqli_stmt_close($notifStmt);
}

if (count($notifications) === 0) {
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

$announcements = [];
$annSql = "
SELECT announcement_id, title, message, start_date, end_date, created_at
FROM announcements
WHERE is_active = 1
AND (start_date IS NULL OR start_date <= CURDATE())
AND (end_date IS NULL OR end_date >= CURDATE())
ORDER BY created_at DESC
LIMIT 3
";
$annRes = mysqli_query($conn, $annSql);
if ($annRes) {
  while ($a = mysqli_fetch_assoc($annRes)) $announcements[] = $a;
}


$userBadges = [];
$badgeSql = "
SELECT b.badge_id, b.badge_name, b.icon_path, b.description
FROM user_badges ub
JOIN badges b ON ub.badge_id = b.badge_id
WHERE ub.user_id = ?
AND b.is_active = 1
ORDER BY ub.date_awarded DESC
";
$badgeStmt = mysqli_prepare($conn, $badgeSql);
if ($badgeStmt) {
  mysqli_stmt_bind_param($badgeStmt, "s", $user_id);
  mysqli_stmt_execute($badgeStmt);
  $badgeRes = mysqli_stmt_get_result($badgeStmt);
  while ($b = mysqli_fetch_assoc($badgeRes)) $userBadges[] = $b;
  mysqli_stmt_close($badgeStmt);
}

$joined    = $user["created_at"] ? date("F Y", strtotime($user["created_at"])) : "—";
$lastLogin = $user["last_login"] ? date("d M Y, h:i A", strtotime($user["last_login"])) : "First login";

$badgeBaseDir = "../images/badges/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Dashboard | ReLife Hub</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">

<style>
  .top-panels{
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin: 14px 0;
  }
  .panel-box{
    background: #fff;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    padding: 14px 16px;
  }
  .panel-title{
    font-weight: 700;
    font-size: 18px;
    margin: 0 0 6px 0;
  }
  .panel-sub{
    color: var(--color-muted);
    font-size: 13px;
    margin-bottom: 10px;
  }
  .panel-list{
    list-style: none;
    padding: 0;
    margin: 0;
    display:flex;
    flex-direction:column;
    gap: 10px;
  }
  .panel-item{
    border: 1px solid var(--color-border);
    border-radius: 10px;
    padding: 10px 12px;
    background: var(--color-section);
  }
  .panel-meta{
    display:flex;
    flex-wrap:wrap;
    gap: 10px;
    font-size: 12px;
    color: var(--color-muted);
    margin-bottom: 6px;
  }
  .pill{
    display:inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    border: 1px solid var(--color-border);
    background: #fff;
    font-size: 12px;
    color: var(--color-text);
  }
  .panel-empty{
    color: var(--color-muted);
    margin: 0;
  }

  /* badge grid */
  .badge-grid{
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 12px;
    margin-top: 10px;
  }
  .badge-card{
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-section);
    padding: 10px;
    text-align: center;
  }
  .badge-card img{
    width: 54px;
    height: 54px;
    object-fit: contain;
    display:block;
    margin: 0 auto 8px auto;
  }
  .badge-name{
    font-weight: 700;
    font-size: 13px;
    margin: 0;
  }
  .badge-desc{
    color: var(--color-muted);
    font-size: 12px;
    margin: 4px 0 0 0;
    line-height: 1.25;
  }

  @media (max-width: 900px){
    .badge-grid{ grid-template-columns: repeat(3, minmax(0, 1fr)); }
  }
  @media (max-width: 768px) {
    .top-panels{ grid-template-columns: 1fr; }
    .panel-title{ font-size: 16px; }
    .panel-sub{ font-size: 12px; }
    .badge-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
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
          <span> Welcome! <?= h($_SESSION['name'] ?? 'User') ?> </span>
        </div>
      </div>

      <div class="user-top-center">
        <input class="user-top-search" type="text" placeholder="Search...">
        <button class="user-top-search-btn" type="submit">Search</button>
      </div>

      <div class="user-top-right">
        <span class="user-top-points">🪙 <?= (int)$user["eco_points"]; ?> points</span>
        <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
        <a class="user-top-btn" href="add_friends.php" title="Add Friend">👥</a>
        <a href="../login/logout.php" class="user-top-btn logout" title="Logout">❌</a>
      </div>
    </div>

    <div class="top-panels">

      <div class="panel-box">
        <p class="panel-title">📢 Announcements</p>
        <div class="panel-sub">Latest announcements (up to 3)</div>

        <?php if (count($announcements) === 0): ?>
          <p class="panel-empty">No announcements right now.</p>
        <?php else: ?>
          <ul class="panel-list">
            <?php foreach ($announcements as $a): ?>
              <li class="panel-item">
                <div class="panel-meta">
                  <span class="pill"><?= h($a["announcement_id"]); ?></span>
                  <span>
                    <?= $a["created_at"] ? date("d M Y, h:i A", strtotime($a["created_at"])) : ""; ?>
                  </span>
                </div>
                <div style="font-weight:700; margin-bottom:6px;"><?= h($a["title"]); ?></div>
                <div><?= nl2br(h($a["message"])); ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="panel-box">
        <p class="panel-title">🔔 Notifications</p>
        <div class="panel-sub">Latest updates for you (up to 5)</div>

        <?php if (count($notifications) === 0): ?>
          <p class="panel-empty">No notifications right now.</p>
        <?php else: ?>
          <ul class="panel-list">
            <?php foreach ($notifications as $n): ?>
              <li class="panel-item">
                <div class="panel-meta">
                  <span class="pill"><?= h($n["audience_type"]); ?></span>
                  <span>Event: <?= h($n["event_id"]); ?></span>
                  <span>
                    <?= $n["created_at"] ? date("d M Y, h:i A", strtotime($n["created_at"])) : ""; ?>
                  </span>
                </div>
                <div><?= nl2br(h($n["message"])); ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

    </div>

    <div class="profile-card">
      <h2>User Profile</h2>
      <p>
        <?= h($user["name"]); ?> | Joined <?= h($joined); ?><br>
        <span style="color: var(--color-muted);">Last login: <?= h($lastLogin); ?></span>
      </p>

      <div class="profile-stats">
        <div>
          <h3>Eco-Points</h3>
          <p><?= (int)$user["eco_points"]; ?></p>
        </div>
        <div>
          <h3>Recycling Logs</h3>
          <p><?= (int)$totalLogs; ?></p>
        </div>
        <div>
          <h3>Posts</h3>
          <p><?= (int)$totalPosts; ?></p>
        </div>
      </div>

      <div class="badges">
        <h3>Badges</h3>

        <?php if (count($userBadges) === 0): ?>
          <div class="alert-warning">No badges yet. Start recycling to earn your first badge!</div>
        <?php else: ?>
          <div class="badge-grid">
            <?php foreach ($userBadges as $b): ?>
              <?php
                $img = trim($b["icon_path"] ?? "");
                $imgUrl = $badgeBaseDir . $img;

                $imgDisk = __DIR__ . "/" . $badgeBaseDir . $img; 
              ?>
              <div class="badge-card" title="<?= h($b["badge_name"]); ?>">
                <?php if ($img !== ""): ?>
                  <img src="<?= h($imgUrl); ?>" alt="<?= h($b["badge_name"]); ?>">
                <?php else: ?>
                  <div style="font-size:30px; margin: 6px 0;">🏅</div>
                <?php endif; ?>
                <p class="badge-name"><?= h($b["badge_name"]); ?></p>
                <p class="badge-desc"><?= h($b["description"]); ?></p>
              </div>
            <?php endforeach; ?>
          </div>
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
          <td><?= (int)$totalLogs; ?></td>
          <td><?= (int)$pendingLogs; ?></td>
          <td><?= (int)$validLogs; ?></td>
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
              <td><?= h($log["date"]); ?></td>
              <td><?= h($log["material_type"]); ?></td>
              <td><?= h($log["weight"]); ?></td>
              <td><?= h($log["status"]); ?></td>
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
              <td><?= h($p["title"]); ?></td>
              <td><?= h($p["created_at"]); ?></td>
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
