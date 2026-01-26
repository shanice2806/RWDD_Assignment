<?php
session_start();
require_once "../connect.php";

$page_title = "My Profile";
include "admin_header.php";

/* Auth check */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

/* Fetch user */
$stmt = $conn->prepare("
    SELECT user_id, name, email, role, eco_points, badges,
           created_at, account_status, last_login, profile_image
    FROM users
    WHERE user_id = ?
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* Split badges */
$badges = array_filter(array_map('trim', explode(',', $user['badges'])));
?>

<main class="dashboard">
  <div class="main-content">

    <!-- PAGE TITLE -->
    <div class="page-title-bar">
      <h2>My Profile</h2>
    </div>

    <div class="profile-container">

      <!-- Avatar -->
      <div class="profile-avatar-large">
        <?php if (!empty($user['profile_image'])): ?>
          <img src="../<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile">
        <?php else: ?>
          👤
        <?php endif; ?>
      </div>

      <!-- Actions -->
                <div style="text-align:center; margin-bottom:20px;">
            <a href="edit_profile.php?id=<?= urlencode($user['user_id']) ?>" class="btn">
                Edit Profile
            </a>
        </div>

      <!-- Form -->
      <form class="profile-form">

        <div class="form-group">
          <label>User ID :</label>
          <input type="text" value="<?= htmlspecialchars($user['user_id']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Name :</label>
          <input type="text" value="<?= htmlspecialchars($user['name']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Email :</label>
          <input type="text" value="<?= htmlspecialchars($user['email']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Role :</label>
          <input type="text" value="<?= htmlspecialchars($user['role']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Eco Points :</label>
          <input type="text" value="<?= htmlspecialchars($user['eco_points']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Badges :</label>
          <div style="display:flex; gap:10px; overflow-x:auto;">
            <?php foreach ($badges as $badge): ?>
              <div style="min-width:60px;height:60px;background:#e0e0e0;
                          display:flex;align-items:center;justify-content:center;
                          border-radius:6px;font-size:12px;text-align:center;">
                <?= htmlspecialchars($badge) ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-group">
          <label>Last Login :</label>
          <input type="text" value="<?= htmlspecialchars($user['last_login']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Account Created On :</label>
          <input type="text" value="<?= htmlspecialchars($user['created_at']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Account Status :</label>
          <input type="text" value="<?= htmlspecialchars($user['account_status']) ?>" readonly>
        </div>

      </form>

    </div>
  </div>
</main>

<?php include "admin_footer.php"; ?>
