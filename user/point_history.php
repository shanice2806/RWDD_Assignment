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

/* user mini info */
$userSql = "SELECT name, eco_points FROM users WHERE user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res) ?: ["name"=>"User", "eco_points"=>0];
mysqli_stmt_close($stmt);

/* transactions */
$tx = [];
$txSql = "
SELECT transaction_id, source_id, points_change, description, created_at
FROM eco_points_transactions
WHERE user_id = ?
ORDER BY created_at DESC
LIMIT 50
";
$txStmt = mysqli_prepare($conn, $txSql);
if ($txStmt) {
  mysqli_stmt_bind_param($txStmt, "s", $user_id);
  mysqli_stmt_execute($txStmt);
  $txRes = mysqli_stmt_get_result($txStmt);
  while ($row = mysqli_fetch_assoc($txRes)) $tx[] = $row;
  mysqli_stmt_close($txStmt);
}

/* summary (optional) */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Points Record | ReLife Hub</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">

  <style>
    .box{
      background:#fff;
      border:1px solid var(--color-border);
      border-radius:12px;
      padding:14px 16px;
      margin:14px 0;
    }
    .row{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      align-items:center;
      justify-content:space-between;
    }
    .pos{ font-weight:700; }
    .neg{ font-weight:700; }
    @media (max-width:768px){
      .box{ padding:12px; }
      table{ font-size:13px; }
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
    <div class="box">
      <div class="row">
        <div>
          <h2 style="margin:0;">Points Record</h2>
          <div style="color:var(--color-muted); font-size:13px; margin-top:4px;">
            Showing latest 50 transactions
          </div>
        </div>
      </div>
    </div>

    <div class="box">
      <?php if (count($tx) === 0): ?>
        <div class="alert-warning">No points record found.</div>
      <?php else: ?>
        <table>
          <tr>
            <th>Date</th>
            <th>Transaction</th>
            <th>Source</th>
            <th>Points</th>
            <th>Description</th>
          </tr>

          <?php foreach ($tx as $t): ?>
            <?php
              $pc = (int)$t["points_change"];
              $isPos = $pc >= 0;
            ?>
            <tr>
              <td><?= $t["created_at"] ? h(date("d M Y, h:i A", strtotime($t["created_at"]))) : ""; ?></td>
              <td><?= h($t["transaction_id"]); ?></td>
              <td><?= h($t["source_id"]); ?></td>
              <td class="<?= $isPos ? "pos" : "neg" ?>">
                <?= $isPos ? "+" : "" ?><?= $pc; ?>
              </td>
              <td><?= h($t["description"]); ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>

  </div>
</div>

<footer>
  <p>&copy; 2026 ReLife Hub</p>
</footer>

<script src="../user/user.js"></script>
</body>
</html>
