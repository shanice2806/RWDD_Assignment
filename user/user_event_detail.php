<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "../connect.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");
  exit();
}

$user_id = $_SESSION["user_id"];

function h($str) {
  return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

$userSql = "SELECT user_id, name, role, eco_points, profile_image
            FROM users WHERE user_id = ? LIMIT 1";
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
  $try = "../" . ltrim($user["profile_image"], "/");
  $disk = __DIR__ . "/../" . ltrim($user["profile_image"], "/");
  if (file_exists($disk)) {
    $profileImg = $try;
  }
}

$event_id = trim($_GET["event_id"] ?? "");
if ($event_id === "") {
  echo "Invalid event.";
  exit();
}

$eventStmt = mysqli_prepare($conn, "
  SELECT event_id, event_title, event_type, event_description,
         event_date_time, event_location, event_status,
         registration_status
  FROM events
  WHERE event_id = ?
    AND event_status = 'Approved'
  LIMIT 1
");
mysqli_stmt_bind_param($eventStmt, "s", $event_id);
mysqli_stmt_execute($eventStmt);
$eventRes = mysqli_stmt_get_result($eventStmt);
$event = mysqli_fetch_assoc($eventRes);
mysqli_stmt_close($eventStmt);

if (!$event) {
  echo "Event not found or not available.";
  exit();
}

$eventTs = strtotime($event["event_date_time"]);
$isPast  = ($eventTs !== false && $eventTs < time());

$isRegistered = false;
$regStatus = "";

$regStmt = mysqli_prepare($conn, "
  SELECT registration_status
  FROM registrations
  WHERE user_id = ?
    AND event_id = ?
  LIMIT 1
");
mysqli_stmt_bind_param($regStmt, "ss", $user_id, $event_id);
mysqli_stmt_execute($regStmt);
$regRes = mysqli_stmt_get_result($regStmt);
$regRow = mysqli_fetch_assoc($regRes);
mysqli_stmt_close($regStmt);

if ($regRow) {
  $isRegistered = true;
  $regStatus = trim($regRow["registration_status"] ?? "Registered");
}

$isVolunteer = false;
$volTask = "";
$volHours = "";

$volStmt = mysqli_prepare($conn, "
  SELECT volunteer_task, volunteer_hours_logged
  FROM volunteers
  WHERE user_id = ?
    AND event_id = ?
  LIMIT 1
");
mysqli_stmt_bind_param($volStmt, "ss", $user_id, $event_id);
mysqli_stmt_execute($volStmt);
$volRes = mysqli_stmt_get_result($volStmt);
$volRow = mysqli_fetch_assoc($volRes);
mysqli_stmt_close($volStmt);

if ($volRow) {
  $isVolunteer = true;
  $volTask  = trim($volRow["volunteer_task"] ?? "");
  $volHours = (string)($volRow["volunteer_hours_logged"] ?? "0");
}

$mediaStmt = mysqli_prepare($conn, "
  SELECT event_media_file_path
  FROM event_media
  WHERE event_id = ?
    AND event_media_is_hidden = 0
  ORDER BY event_media_id ASC
");
mysqli_stmt_bind_param($mediaStmt, "s", $event_id);
mysqli_stmt_execute($mediaStmt);
$mediaRes = mysqli_stmt_get_result($mediaStmt);
mysqli_stmt_close($mediaStmt);

$archive = null;
if ($isPast) {
  $archiveStmt = mysqli_prepare($conn, "
    SELECT event_archive_summary, event_archive_impact_stats
    FROM event_archive
    WHERE event_id = ?
    LIMIT 1
  ");
  mysqli_stmt_bind_param($archiveStmt, "s", $event_id);
  mysqli_stmt_execute($archiveStmt);
  $archiveRes = mysqli_stmt_get_result($archiveStmt);
  $archive = mysqli_fetch_assoc($archiveRes);
  mysqli_stmt_close($archiveStmt);
}

$regOpen = (strtolower($event["registration_status"]) === "open");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($event["event_title"]); ?> | Event Details</title>
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
          <span class="user-top-name"><span> Welcome!  <?= h($_SESSION['name'] ?? 'User') ?> </span>
        </div>
      </div>

   <div class="user-top-center">
<h1 style="color: white;">Event Detail</h1>
  </div>

      <div class="user-top-right">
        <span class="user-top-points">🪙 <?= (int)$user["eco_points"]; ?> points</span>
        <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
        <a class="user-top-btn" href="friends_add.php" title="Add Friend">👥</a>
        <a href="../login/logout.php" class="user-top-btn logout" title="Logout">❌</a>
      </div>

    </div>

    <div class="section-preview">
      <h2><?= h($event["event_title"]); ?></h2>

      <p><strong>Event:</strong>
        <?= $isPast
          ? '<span style="color:#b45309;">Past</span>'
          : '<span style="color:green;">Upcoming</span>' ?>
      </p>

      <p><strong>Type:</strong> <?= h($event["event_type"]); ?></p>
      <p><strong>Date & Time:</strong> <?= h($event["event_date_time"]); ?></p>
      <p><strong>Location:</strong> <?= h($event["event_location"]); ?></p>

      <p>
        <strong>Registration:</strong>
        <?= $regOpen
          ? '<span style="color:green;">Open</span>'
          : '<span style="color:#b45309;">Closed</span>' ?>
      </p>

      <p style="margin-top:12px;">
        <?= nl2br(h($event["event_description"])); ?>
      </p>

      <div style="margin-top:14px; padding:12px; border:1px solid #eee; border-radius:12px;">
        <h3>My Status</h3>

        <p>
          <strong>Participant:</strong>
          <?= $isRegistered
            ? '✅ Registered (' . h($regStatus) . ')'
            : '❌ Not registered' ?>
        </p>

        <p>
          <strong>Volunteer:</strong>
          <?= $isVolunteer ? '✅ Yes (Offline)' : '❌ No' ?>
        </p>

        <?php if ($isVolunteer): ?>
          <p><strong>Task:</strong> <?= h($volTask ?: "Not assigned"); ?></p>
          <p><strong>Hours:</strong> <?= h($volHours); ?></p>
        <?php endif; ?>
      </div>

      <?php if (!$isPast && $regOpen && !$isRegistered): ?>
        <form method="POST" action="user_event.php" style="margin-top:12px;">
          <input type="hidden" name="join_event_id" value="<?= h($event_id); ?>">
          <button type="submit" class="btn">✅ Join This Event</button>
        </form>
      <?php endif; ?>

      <a class="btn" href="user_event.php" style="margin-top:12px;">← Back</a>
    </div>

    <?php if ($mediaRes && mysqli_num_rows($mediaRes) > 0): ?>
      <div class="section-preview">
        <h3>Event Photos</h3>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
          <?php while ($m = mysqli_fetch_assoc($mediaRes)): ?>
            <?php
              $src = "../" . ltrim($m["event_media_file_path"], "/");
            ?>
            <img src="<?= h($src); ?>" style="max-width:240px; border-radius:12px;">
          <?php endwhile; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($isPast): ?>
      <div class="section-preview">
        <h3>Event Summary</h3>
        <?php if ($archive): ?>
          <p><?= nl2br(h($archive["event_archive_summary"])); ?></p>
          <?php if (!empty($archive["event_archive_impact_stats"])): ?>
            <p><strong>Impact:</strong><br><?= nl2br(h($archive["event_archive_impact_stats"])); ?></p>
          <?php endif; ?>
        <?php else: ?>
          <p>No archive record yet.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<script src="../user/user.js"></script>
</body>
</html>
