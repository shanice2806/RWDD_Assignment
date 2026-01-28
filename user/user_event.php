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

$role = strtolower(trim($_SESSION["role"] ?? ""));
if ($role === "admin") {
  header("Location: ../admin/admin_dashboard.php");
  exit();
}

$user_id = $_SESSION["user_id"];

function h($str) {
  return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function make_id($prefix) {
  return $prefix . "_" . date("YmdHis") . rand(10, 99);
}

$userSql = "SELECT user_id, name, role, eco_points, profile_image
            FROM users WHERE user_id = ? LIMIT 1";
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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["join_event_id"])) {
  $event_id = trim($_POST["join_event_id"]);

  $evtCheck = mysqli_prepare($conn, "
    SELECT registration_status
    FROM events
    WHERE event_id = ?
      AND event_status = 'Approved'
    LIMIT 1
  ");
  mysqli_stmt_bind_param($evtCheck, "s", $event_id);
  mysqli_stmt_execute($evtCheck);
  $evtRes = mysqli_stmt_get_result($evtCheck);
  $evtRow = mysqli_fetch_assoc($evtRes);
  mysqli_stmt_close($evtCheck);

  if (!$evtRow) {
    header("Location: user_event.php?join=notfound");
    exit();
  }
  if (strtolower(trim($evtRow["registration_status"])) !== "open") {
    header("Location: user_event.php?join=closed");
    exit();
  }

  $check = mysqli_prepare($conn,
    "SELECT registration_id FROM registrations
     WHERE user_id=? AND event_id=? LIMIT 1"
  );
  mysqli_stmt_bind_param($check, "ss", $user_id, $event_id);
  mysqli_stmt_execute($check);
  $exists = (mysqli_num_rows(mysqli_stmt_get_result($check)) > 0);
  mysqli_stmt_close($check);

  if ($exists) {
    header("Location: user_event.php?join=dup");
    exit();
  }

  $reg_id = make_id("reg");
  $ins = mysqli_prepare($conn,
    "INSERT INTO registrations
     (registration_id, event_id, user_id, registration_status)
     VALUES (?, ?, ?, 'Registered')"
  );
  mysqli_stmt_bind_param($ins, "sss", $reg_id, $event_id, $user_id);
  mysqli_stmt_execute($ins);
  mysqli_stmt_close($ins);

  header("Location: user_event.php?join=ok");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_event_id"])) {
  $event_id = trim($_POST["cancel_event_id"]);

  $del = mysqli_prepare($conn,
    "DELETE FROM registrations WHERE user_id=? AND event_id=? LIMIT 1"
  );
  mysqli_stmt_bind_param($del, "ss", $user_id, $event_id);
  mysqli_stmt_execute($del);
  mysqli_stmt_close($del);

  header("Location: user_event.php");
  exit();
}

$q = trim($_GET["q"] ?? "");

if ($q !== "") {
  $like = "%".$q."%";

  $eventsStmt = mysqli_prepare($conn,
    "SELECT event_id, event_title, event_date_time,
            event_location, registration_status
     FROM events
     WHERE event_status='Approved'
       AND (event_title LIKE ? OR event_location LIKE ?)
     ORDER BY event_date_time ASC"
  );
  mysqli_stmt_bind_param($eventsStmt, "ss", $like, $like);
} else {
  $eventsStmt = mysqli_prepare($conn,
    "SELECT event_id, event_title, event_date_time,
            event_location, registration_status
     FROM events
     WHERE event_status='Approved'
     ORDER BY event_date_time ASC"
  );
}

mysqli_stmt_execute($eventsStmt);
$eventsRes = mysqli_stmt_get_result($eventsStmt);
mysqli_stmt_close($eventsStmt);

$myRegs = [];
$myRegStmt = mysqli_prepare($conn,
  "SELECT event_id FROM registrations WHERE user_id=?"
);
mysqli_stmt_bind_param($myRegStmt, "s", $user_id);
mysqli_stmt_execute($myRegStmt);
$myRegRes = mysqli_stmt_get_result($myRegStmt);
while ($r = mysqli_fetch_assoc($myRegRes)) {
  $myRegs[trim($r["event_id"])] = true; 
}
mysqli_stmt_close($myRegStmt);

$myListStmt = mysqli_prepare($conn,
  "SELECT e.event_id, 
          e.event_title,
          e.event_date_time, 
          e.event_location,
          a.attendance_id
   FROM registrations r
   JOIN events e ON e.event_id = r.event_id
   LEFT JOIN attendance a
   ON a.event_id = e.event_id
   AND a.user_id = r.user_id
   WHERE r.user_id=?
   ORDER BY e.event_date_time DESC"
);
mysqli_stmt_bind_param($myListStmt, "s", $user_id);
mysqli_stmt_execute($myListStmt);
$myListRes = mysqli_stmt_get_result($myListStmt);
mysqli_stmt_close($myListStmt);

$myVolListStmt = mysqli_prepare($conn,
  "SELECT e.event_id, e.event_title,
          e.event_date_time, e.event_location,
          v.volunteer_task, v.volunteer_hours_logged
   FROM volunteers v
   JOIN events e ON e.event_id = v.event_id
   WHERE v.user_id=?
   ORDER BY e.event_date_time DESC"
);
mysqli_stmt_bind_param($myVolListStmt, "s", $user_id);
mysqli_stmt_execute($myVolListStmt);
$myVolListRes = mysqli_stmt_get_result($myVolListStmt);
mysqli_stmt_close($myVolListStmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Events | ReLife Hub</title>
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

      <form class="user-top-center" method="GET" action="user_event.php">
        <input class="user-top-search" type="text" name="q" placeholder="Search events..." value="<?= h($q) ?>">
        <button class="user-top-search-btn" type="submit">Search</button>
      </form>

      <div class="user-top-right">
        <span class="user-top-points">🪙 <?= (int)$user["eco_points"]; ?> points</span>
        <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
        <a class="user-top-btn" href="friends_add.php" title="Add Friend">👥</a>
        <a href="../login/logout.php" class="user-top-btn logout" title="Logout">❌</a>
      </div>

    </div>

<?php
$att = $_GET["att"] ?? "";
$join = $_GET["join"] ?? "";
?>

<div class="section-preview">
  <h2>Take Attendance</h2>

  <form method="POST" action="attendance_take_event.php" id="attForm">
    <input type="text" name="code" placeholder="example ：ABC12 " required
      style="font-size:18px;
            padding:10px; 
            border-radius:12px; 
            text-align:center;">

    <button type="submit" class="btn">
      Take Attendance
    </button>
  </form>
</div>

<script>
  (function(){
    const att = <?php echo json_encode($att); ?>;
    if (att) {
      if (att === "ok") alert("✅ Attendance recorded successfully!");
      else if (att === "dup") alert("ℹ️ You already checked in for this event.");
      else if (att === "noreg") alert("❌ You are not registered for this event.");
      else if (att === "badcode") alert("❌ Invalid attendance code.");
      else alert("❌ Attendance failed. Please check the code and try again.");
    }

    const join = <?php echo json_encode($join); ?>;
    if (!join) return;

    if (join === "ok") alert("✅ Joined successfully!");
    else if (join === "dup") alert("ℹ️ You already joined this event.");
    else if (join === "closed") alert("❌ This event registration is CLOSED.");
    else if (join === "notfound") alert("❌ Event not found / not approved.");
  })();
</script>

    <div class="section-preview">
      <h2>Upcoming Events</h2>

      <?php if ($eventsRes && mysqli_num_rows($eventsRes) > 0): ?>
        <?php while ($e = mysqli_fetch_assoc($eventsRes)): ?>

          <?php
            $eventId = trim($e["event_id"]);
            $already = isset($myRegs[$eventId]);
            $isOpen  = (strtolower(trim($e["registration_status"] ?? "")) === "open");
          ?>

          <div class="feed-card">
            <h3><?= h($e["event_title"]); ?></h3>
            <p><strong>Date & Time:</strong> <?= h($e["event_date_time"]); ?></p>
            <p><strong>Location:</strong> <?= h($e["event_location"]); ?></p>

            <a class="btn" href="user_event_detail.php?event_id=<?= urlencode($eventId); ?>">View Details</a>

            <?php if ($already): ?>
              <span class="btn" style="margin-left:10px;opacity:.7;cursor:default;">✅ Registered</span>

            <?php elseif (!$isOpen): ?>
               <span class="btn" style="margin-left:10px;opacity:.6;cursor:not-allowed;">🔒 Closed</span>

            <?php else: ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="join_event_id" value="<?= h($eventId); ?>">
                <button type="submit" class="btn" style="margin-left:10px;">✅ Join</button>
              </form>

            <?php endif; ?>

          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No upcoming events found.</p>
      <?php endif; ?>
    </div>

    <div class="section-preview">
      <h2>My Registrations</h2>

      <table>
        <tr>
          <th>Event</th>
          <th>Date & Time</th>
          <th>Location</th>
          <th>Attendance</th>
          <th>Action</th>
        </tr>

        <?php if ($myListRes && mysqli_num_rows($myListRes) > 0): ?>
          <?php while ($r = mysqli_fetch_assoc($myListRes)): ?>
            <tr>
              <td><?= h($r["event_title"]); ?></td>
              <td><?= h($r["event_date_time"]); ?></td>
              <td><?= h($r["event_location"]); ?></td>
              <td>
            <?php if (!empty($r["attendance_id"])): ?>
              <span style="color:green;">Attended</span>
            <?php else: ?>
              <span style="color:brown;">Not Yet</span>
            <?php endif; ?>
              </td>
              <td>
                <form method="POST" onsubmit="return confirm('Cancel this registration?');">
                  <input type="hidden" name="cancel_event_id" value="<?= h($r["event_id"]); ?>">
                  <button type="submit" class="btn">Cancel</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="4">No registrations yet.</td></tr>
        <?php endif; ?>
      </table>
    </div>

  </div>
</div>

<div class="section-preview">
  <h2>My Volunteer Assignments</h2>

  <table>
    <tr>
      <th>Event</th>
      <th>Date & Time</th>
      <th>Location</th>
      <th>Task</th>
      <th>Hours</th>
    </tr>

    <?php if ($myVolListRes && mysqli_num_rows($myVolListRes) > 0): ?>
      <?php while ($vrow = mysqli_fetch_assoc($myVolListRes)): ?>
        <tr>
          <td><?= h($vrow["event_title"]); ?></td>
          <td><?= h($vrow["event_date_time"]); ?></td>
          <td><?= h($vrow["event_location"]); ?></td>
          <td><?= h($vrow["volunteer_task"] ?: "Not assigned yet"); ?></td>
          <td><?= h($vrow["volunteer_hours_logged"] ?? "0"); ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="5">No registrations yet.</td></tr>
    <?php endif; ?>
  </table>
</div>

<footer>
  <p>&copy; 2026 ReLife Hub</p>
</footer>

<script src="../user/user.js"></script>
</body>
</html>
