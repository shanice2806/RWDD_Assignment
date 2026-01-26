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

$userSql = "SELECT user_id, name, eco_points, profile_image, badges, created_at, last_login
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

$profileImg = $user["profile_image"] ? "../" . $user["profile_image"] : "../images/profile.png";
$joined = $user["created_at"] ? date("F Y", strtotime($user["created_at"])) : "—";
$lastLogin = $user["last_login"] ? date("d M Y, h:i A", strtotime($user["last_login"])) : "First login";

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Friend | ReLife Hub</title>

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
          <img src="<?php echo ($profileImg); ?>" class="user-top-avatar" alt="Avatar">
          <span class="user-top-name"><?php echo ($user["name"]); ?></span>
        </div>
      </div>

      <div class="user-top-center">
        <input class="user-top-search" type="text" placeholder="Search...">
        <button class="user-top-search-btn" type="submit">Search</button>
      </div>

      <div class="user-top-right">
        <span class="user-top-points"> <?php echo (int)$user["eco_points"]; ?> points</span>
        <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
        <a class="user-top-btn active" href="friends_add.php" title="Add Friend">👥</a>
        <a href="../login/logout.php" class="user-top-btn logout" title="Logout">❌</a>
      </div>

    </div>

    <div class="section-preview">
      <h2>Add a Friend</h2>
      <form action="" method="post">
        <input type="text" name="friend_username" placeholder="Enter username or email" required>
        <button type="submit" class="btn">Send Request</button>
      </form>
    </div>

    <div class="section-preview">
      <h2>Pending Requests</h2>
      <table>
        <tr>
          <th>Username</th>
          <th>Action</th>
        </tr>
        <tr>
          <td>ExampleUser</td>
          <td>
            <button class="btn">Accept</button>
            <button class="btn">Reject</button>
          </td>
        </tr>
      </table>
    </div>

    <div class="section-preview">
      <h2>Friend List</h2>
      <table>
        <tr>
          <th>Username</th>
          <th>Joined</th>
        </tr>
        <tr>
          <td>FriendUser1</td>
          <td>Jan 2026</td>
        </tr>
        <tr>
          <td>FriendUser2</td>
          <td>Dec 2025</td>
        </tr>
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
