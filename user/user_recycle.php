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

$userSql = "SELECT user_id, name, eco_points, profile_image, role FROM users WHERE user_id=? LIMIT 1";
$userStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($userStmt, "s", $user_id);
mysqli_stmt_execute($userStmt);
$userRes = mysqli_stmt_get_result($userStmt);
$user = mysqli_fetch_assoc($userRes);
mysqli_stmt_close($userStmt);

if (!$user) {
  session_destroy();
  header("Location: ../login/login.php");
  exit();
}

$profileImg = !empty($user["profile_image"]) ? "../" . $user["profile_image"] : "../images/profile.png";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove_log_id"])) {
  $log_id = trim($_POST["remove_log_id"]);

  $del = mysqli_prepare($conn, "DELETE FROM recycling_log WHERE log_id=? AND user_id=? LIMIT 1");
  mysqli_stmt_bind_param($del, "ss", $log_id, $user_id);
  mysqli_stmt_execute($del);
  mysqli_stmt_close($del);

  header("Location: user_recycle.php?removed=1");
  exit();
}

$editMode = (isset($_GET["edit"]) && $_GET["edit"] == "1");

$logsStmt = mysqli_prepare($conn, "
  SELECT 
    log_id, material_id, event_id, weight_kg, photo_path,
    submitted_at, status, points_awarded, is_flagged, flag_reason
  FROM recycling_log
  WHERE user_id=?
  ORDER BY submitted_at DESC
");
mysqli_stmt_bind_param($logsStmt, "s", $user_id);
mysqli_stmt_execute($logsStmt);
$logsRes = mysqli_stmt_get_result($logsStmt);
mysqli_stmt_close($logsStmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recycle | ReLife Hub</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">

  <style>
    .card { background:#fff; border-radius:12px; padding:16px; box-shadow:0 6px 18px rgba(0,0,0,.08); }
    .toolbar { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:12px; flex-wrap:wrap; }
    .toolbar h2{ margin:0; }
    table{ width:100%; border-collapse:collapse; }
    th, td{ padding:10px 8px; border-bottom:1px solid #eee; text-align:left; font-size:14px; vertical-align:top; }
    th{ background:#f7f7f7; }
    .btn{ display:inline-block; padding:8px 10px; border-radius:10px; border:0; cursor:pointer; font-weight:700; text-decoration:none; }
    .btn-gray{ background:#111827; color:#fff; }
    .btn-red{ background:#ef4444; color:#fff; }
    .btn-outline{ background:#fff; border:1px solid #ddd; color:#111827; }
    .msg{ margin:10px 0; padding:10px 12px; border-radius:10px; }
    .msg-ok{ background:#e9ffef; color:#0b6b2b; }
    .badge{ display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; font-weight:800; }
    .b-valid{ background:#dcfce7; color:#166534; }
    .b-pending{ background:#fff7ed; color:#9a3412; }
    .b-invalid{ background:#fee2e2; color:#991b1b; }
    .small{ color:#6b7280; font-size:12px; }
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
<h1 style="color: white;">Recycle</h1>
  </div>
        <div class="user-top-right">
          <span class="user-top-points">🪙 <?php echo (int)$user["eco_points"]; ?> points</span>
          <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
          <a href="friends_add.php" class="user-top-btn">👥</a>
          <a href="../login/logout.php" class="user-top-btn logout" title="Logout">❌</a>
        </div>
      </div>
      

      <div class="section-preview">
        <div class="card">

          <div class="toolbar">
            <h2>Recycle — User point</h2>
            <div>
              <?php if ($editMode): ?>
                <a class="btn btn-outline" href="user_recycle.php">Done</a>
              <?php else: ?>
                <a class="btn btn-gray" href="user_recycle.php?edit=1">Edit</a>
              <?php endif; ?>

              <a class="btn btn-outline" href="user_recycle_add.php">Submit Log</a>
            </div>
          </div>

          <?php if (isset($_GET["submitted"])): ?>
            <div class="msg msg-ok">✅ Recycle log submitted.</div>
          <?php endif; ?>
          <?php if (isset($_GET["updated"])): ?>
            <div class="msg msg-ok">✅ Log updated.</div>
          <?php endif; ?>
          <?php if (isset($_GET["removed"])): ?>
            <div class="msg msg-ok">✅ Remove Successful.</div>
          <?php endif; ?>

          <table>
            <tr>
              <th>Date</th>
              <th>Material</th>
              <th>Weight (kg)</th>
              <th>Status</th>
              <th>Point</th>
              <?php if ($editMode): ?><th>Action</th><?php endif; ?>
            </tr>

            <?php if ($logsRes && mysqli_num_rows($logsRes) > 0): ?>
              <?php while ($log = mysqli_fetch_assoc($logsRes)): ?>
                <?php
                  $st = strtoupper(trim($log["status"] ?? "PENDING"));
                  $badgeClass = "b-pending";
                  if ($st === "VALID") $badgeClass = "b-valid";
                  if ($st === "INVALID") $badgeClass = "b-invalid";
                ?>
                <tr>
                  <td>
                    <?php echo htmlspecialchars($log["submitted_at"]); ?>
                    <?php if (!empty($log["is_flagged"]) && $log["is_flagged"] == 1): ?>
                      <div class="small">⚑ Flagged: <?php echo htmlspecialchars($log["flag_reason"] ?? "-"); ?></div>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($log["material_id"]); ?></td>
                  <td><?php echo htmlspecialchars($log["weight_kg"]); ?></td>
                  <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($st); ?></span></td>
                  <td><?php echo htmlspecialchars($log["points_awarded"] ?? 0); ?></td>

                  <?php if ($editMode): ?>
                    <td>
                      <a class="btn btn-outline" href="user_recycle_edit.php?log_id=<?php echo urlencode($log["log_id"]); ?>">Edit</a>

                      <form method="POST" style="display:inline;" onsubmit="return confirm('Confirm you want to remove this log?');">
                        <input type="hidden" name="remove_log_id" value="<?php echo htmlspecialchars($log["log_id"]); ?>">
                        <button class="btn btn-red" type="submit">Remove</button>
                      </form>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="<?php echo $editMode ? 6 : 5; ?>">No logs yet.</td></tr>
            <?php endif; ?>
          </table>

        </div>
      </div>

    </div>
  </div>

  <footer>
    © 2026 ReLife Hub
  </footer>

  <script src="../user/user.js"></script>
</body>
</html>
