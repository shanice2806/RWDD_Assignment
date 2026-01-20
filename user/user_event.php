<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "../connect.php";

/* =========================
   LOGIN CHECK
========================= */
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

/* =========================
   GET USER INFO (same as dashboard)
========================= */
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

$profileImg = !empty($user["profile_image"]) ? "../" . $user["profile_image"] : "../images/profile.png";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["join_event_id"])) {
  $event_id = trim($_POST["join_event_id"]);

  $check = mysqli_prepare($conn, "SELECT 1 FROM registrations WHERE user_id=? AND event_id=? LIMIT 1");
  mysqli_stmt_bind_param($check, "ss", $user_id, $event_id);
  mysqli_stmt_execute($check);
  $exists = mysqli_stmt_get_result($check)->num_rows;
  mysqli_stmt_close($check);

  if ($exists == 0) {
    $ins = mysqli_prepare($conn, "INSERT INTO registrations (user_id, event_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($ins, "ss", $user_id, $event_id);
    mysqli_stmt_execute($ins);
    mysqli_stmt_close($ins);
  }

  header("Location: user_event.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["volunteer_event_id"])) {
  $event_id = trim($_POST["volunteer_event_id"]);

  if ($event_id === "") {
    header("Location: user_event.php?volerr=1");
    exit();
  }

  // check already applied
  $check = mysqli_prepare($conn, "SELECT 1 FROM volunteers WHERE user_id=? AND event_id=? LIMIT 1");
  mysqli_stmt_bind_param($check, "ss", $user_id, $event_id);
  mysqli_stmt_execute($check);
  $exists = mysqli_stmt_get_result($check)->num_rows;
  mysqli_stmt_close($check);

  if ($exists == 0) {
    $ins = mysqli_prepare($conn, "INSERT INTO volunteers (user_id, event_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($ins, "ss", $user_id, $event_id);
    mysqli_stmt_execute($ins);
    mysqli_stmt_close($ins);

    header("Location: user_event.php?volok=1");
    exit();
  } else {
    header("Location: user_event.php?voldup=1");
    exit();
  }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_volunteer_event_id"])) {
  $event_id = trim($_POST["cancel_volunteer_event_id"]);

  $del = mysqli_prepare($conn, "DELETE FROM volunteers WHERE user_id=? AND event_id=? LIMIT 1");
  mysqli_stmt_bind_param($del, "ss", $user_id, $event_id);
  mysqli_stmt_execute($del);
  mysqli_stmt_close($del);

  header("Location: user_event.php?volcancel=1");
  exit();
}
/* =========================
   CANCEL REGISTRATION (optional)
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_event_id"])) {
  $event_id = trim($_POST["cancel_event_id"]);

  $del = mysqli_prepare($conn, "DELETE FROM registrations WHERE user_id=? AND event_id=? LIMIT 1");
  mysqli_stmt_bind_param($del, "ss", $user_id, $event_id);
  mysqli_stmt_execute($del);
  mysqli_stmt_close($del);

  header("Location: user_event.php");
  exit();
}

/* =========================
   SEARCH
========================= */
$q = trim($_GET["q"] ?? "");

if ($q !== "") {
  $like = "%".$q."%";

  $eventsStmt = mysqli_prepare($conn,
    "SELECT event_id, event_title, event_date_time, event_location
     FROM events
     WHERE event_status='Approved'
      AND (event_title LIKE ? OR event_location LIKE ?)
     ORDER BY event_date_time ASC"
  );

  mysqli_stmt_bind_param($eventsStmt, "ss", $like, $like);

} else {
  $eventsStmt = mysqli_prepare($conn,
    "SELECT event_id, event_title, event_date_time, event_location
     FROM events
     WHERE event_status='Approved'
     ORDER BY event_date_time ASC"
  );
}

mysqli_stmt_execute($eventsStmt);
$eventsRes = mysqli_stmt_get_result($eventsStmt);
mysqli_stmt_close($eventsStmt);

/* =========================
   MY REGISTRATIONS
========================= */
$myRegs = [];
$myRegStmt = mysqli_prepare($conn, "SELECT event_id FROM registrations WHERE user_id=?");
mysqli_stmt_bind_param($myRegStmt, "s", $user_id);
mysqli_stmt_execute($myRegStmt);
$myRegRes = mysqli_stmt_get_result($myRegStmt);
while ($r = mysqli_fetch_assoc($myRegRes)) {
  $myRegs[$r["event_id"]] = true;
}
mysqli_stmt_close($myRegStmt);

$myVols = [];
$myVolStmt = mysqli_prepare($conn, "SELECT event_id FROM volunteers WHERE user_id=?");
mysqli_stmt_bind_param($myVolStmt, "s", $user_id);
mysqli_stmt_execute($myVolStmt);
$myVolRes = mysqli_stmt_get_result($myVolStmt);
while ($v = mysqli_fetch_assoc($myVolRes)) {
  $myVols[$v["event_id"]] = true;
}
mysqli_stmt_close($myVolStmt);

/* =========================
   MY REGISTRATIONS LIST (table)
========================= */
$myListStmt = mysqli_prepare($conn, "
  SELECT e.event_id, e.event_title, e.event_date_time, e.event_location
  FROM registrations r
  JOIN events e ON e.event_id = r.event_id
  WHERE r.user_id=?
  ORDER BY e.event_date_time DESC
");
mysqli_stmt_bind_param($myListStmt, "s", $user_id);
mysqli_stmt_execute($myListStmt);
$myListRes = mysqli_stmt_get_result($myListStmt);
mysqli_stmt_close($myListStmt);
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

    <!-- TOP BAR (same as dashboard) -->
    <div class="user-top-bar">

      <div class="user-top-left">
        <button class="sidebar-toggle" id="userSidebarToggle" type="button">☰</button>

        <div class="user-top-user">
          <img src="<?php echo htmlspecialchars($profileImg); ?>" class="user-top-avatar" alt="Avatar">
          <span class="user-top-name"><?php echo htmlspecialchars($user["name"]); ?></span>
        </div>
      </div>

      <!-- SEARCH BAR (works now) -->
      <form class="user-top-center" method="GET" action="user_event.php">
        <input class="user-top-search" type="text" name="q" placeholder="Search events..." value="<?php echo htmlspecialchars($q); ?>">
        <button class="user-top-search-btn" type="submit">Search</button>
      </form>

      <div class="user-top-right">
        <span class="user-top-points">🪙 <?php echo (int)$user["eco_points"]; ?> points</span>
        <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
        <a class="user-top-btn" href="friends_add.php" title="Add Friend">👥</a>
        <a href="../login/logout.php" class="user-top-btn logout" title="Logout">⎋</a>
      </div>

    </div>


    <!-- UPCOMING EVENTS -->
    <div class="section-preview">
      <h2>Upcoming Events</h2>

<?php if (isset($_GET["volok"])): ?>
  <p style="color:green;">✅ Volunteer request submitted successfully.</p>

<?php elseif (isset($_GET["volcancel"])): ?>
  <p style="color:green;">✅ Volunteer request cancelled.</p>

<?php elseif (isset($_GET["voldup"])): ?>
  <p style="color:#b45309;">ℹ️ You already applied as volunteer.</p>

<?php elseif (isset($_GET["volerr"])): ?>
  <p style="color:red;">❌ Error. Please try again.</p>
<?php endif; ?>



      <?php if ($eventsRes && mysqli_num_rows($eventsRes) > 0): ?>
        <?php while ($e = mysqli_fetch_assoc($eventsRes)): ?>
          <?php
            $eventId = $e["event_id"];
            $already = isset($myRegs[$eventId]);
            $volApplied = isset($myVols[$eventId]);

          ?>
          <div class="feed-card">
            <h3><?php echo ($e["event_title"]); ?></h3>
            <p><strong>Date & Time:</strong> <?php echo ($e["event_date_time"]); ?></p>
            <p><strong>Location:</strong> <?php echo ($e["event_location"]); ?></p>

            <a class="btn" href="event_detail.php?event_id=<?php echo urlencode($eventId); ?>">View Details</a>

<?php if ($already): ?>
  <span class="btn" style="margin-left:10px;opacity:.7;cursor:default;">✅ Registered</span>
<?php else: ?>
  <form method="POST" style="display:inline;">
    <input type="hidden" name="join_event_id" value="<?php echo htmlspecialchars($eventId); ?>">
    <button type="submit" class="btn" style="margin-left:10px;">✅ Join</button>
  </form>
<?php endif; ?>

<!-- Volunteer -->
<?php if ($volApplied): ?>
  <span class="btn" style="margin-left:10px;opacity:.7;cursor:default;">🤝 Volunteer Applied</span>

  <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel volunteer request?');">
    <input type="hidden" name="cancel_volunteer_event_id" value="<?php echo htmlspecialchars($eventId); ?>">
    <button type="submit" class="btn" style="margin-left:10px;background:#ef4444;">❌ Cancel Volunteer</button>
  </form>
<?php else: ?>
  <form method="POST" style="display:inline;">
    <input type="hidden" name="volunteer_event_id" value="<?php echo htmlspecialchars($eventId); ?>">
    <button type="submit" class="btn" style="margin-left:10px;">🤝 Volunteer</button>
  </form>
<?php endif; ?>


          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No upcoming events found.</p>
      <?php endif; ?>
    </div>

    <!-- MY REGISTRATIONS -->
    <div class="section-preview">
      <h2>My Registrations</h2>

      <table>
        <tr>
          <th>Event</th>
          <th>Date & Time</th>
          <th>Location</th>
          <th>Action</th>
        </tr>

        <?php if ($myListRes && mysqli_num_rows($myListRes) > 0): ?>
          <?php while ($r = mysqli_fetch_assoc($myListRes)): ?>
            <tr>
              <td><?php echo ($r["event_title"]); ?></td>
              <td><?php echo ($r["event_date_time"]); ?></td>
              <td><?php echo ($r["event_location"]); ?></td>
              <td>
                <form method="POST" onsubmit="return confirm('Cancel this registration?');">
                  <input type="hidden" name="cancel_event_id" value="<?php echo htmlspecialchars($r["event_id"]); ?>">
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

<footer>
  <p>&copy; 2026 ReLife Hub</p>
</footer>

<script src="../user/user.js"></script>
</body>
</html>
