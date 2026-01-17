<?php
include "../connect.php";

/* Page title */
$page_title = "View User";

/* Header opens <html> <body> <div class="main-content"> */
include "admin_header.php";

if (!isset($_GET['id'])) {
    die("User ID not provided.");
}

$user_id = $_GET['id'];

$stmt = $conn->prepare("
    SELECT user_id, name, email, role, eco_points, badges,
           created_at, account_status, last_login, profile_image
    FROM users
    WHERE user_id = ?
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();
?>

<!-- ⚠️ CONTENT MUST START HERE (INSIDE .main-content) -->

<div class="page-title-bar">
  <a href="admin_manage_user.php" class="icon-btn back-btn">←</a>
</div>

<div class="profile-container">

  <div class="profile-avatar-large">
    <?php if (!empty($user['profile_image'])): ?>
      <img src="../uploads/<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile">
    <?php else: ?>
      👤
    <?php endif; ?>
  </div>

  <div class="profile-actions">
    <a href="admin_user_edit.php?id=<?= $user['user_id'] ?>" class="btn-primary">
      Edit Profile
    </a>
  </div>

  <div class="profile-form">

    <div class="form-group">
      <label>Name</label>
      <input type="text" value="<?= htmlspecialchars($user['name']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>User ID</label>
      <input type="text" value="<?= htmlspecialchars($user['user_id']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Email</label>
      <input type="text" value="<?= htmlspecialchars($user['email']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Role</label>
      <input type="text" value="<?= htmlspecialchars($user['role']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Eco Points</label>
      <input type="text" value="<?= htmlspecialchars($user['eco_points']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Badges</label>
      <input type="text" value="<?= htmlspecialchars($user['badges']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Last Login</label>
      <input type="text" value="<?= htmlspecialchars($user['last_login']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Account Created On</label>
      <input type="text" value="<?= htmlspecialchars($user['created_at']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Account Status</label>
      <input type="text" value="<?= htmlspecialchars($user['account_status']) ?>" readonly>
    </div>

  </div>
</div>

<?php
/* Footer closes </div> </body> </html> */
include "admin_footer.php";
?>
