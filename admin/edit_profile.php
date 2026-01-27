<?php
session_start();
require_once "../connect.php";

$page_title = "Edit My Profile";
include "admin_header.php";

/* =====================
   AUTH CHECK
===================== */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$success = "";
$error   = "";

/* =====================
   HANDLE EMAIL UPDATE
===================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_email'])) {
    $new_email = trim($_POST['email']);

    if ($new_email === "") {
        $error = "Email cannot be empty.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $upd = $conn->prepare("
            UPDATE users
            SET email = ?
            WHERE user_id = ?
        ");
        $upd->bind_param("ss", $new_email, $user_id);

        if ($upd->execute()) {
            $success = "Email updated successfully.";
            $_SESSION["email"] = $new_email;
        } else {
            $error = "Failed to update email.";
        }
    }
}

/* =====================
   FETCH USER
===================== */
$userStmt = $conn->prepare("
    SELECT user_id, name, email, role, eco_points,
           created_at, account_status, last_login, profile_image
    FROM users
    WHERE user_id = ?
");
$userStmt->bind_param("s", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

/* =====================
   FETCH USER BADGES
===================== */
$badgeStmt = $conn->prepare("
    SELECT 
        b.badge_name,
        b.icon_path,
        ub.date_awarded
    FROM user_badges ub
    JOIN badges b ON ub.badge_id = b.badge_id
    WHERE ub.user_id = ?
      AND b.is_active = 1
    ORDER BY ub.date_awarded ASC
");
$badgeStmt->bind_param("s", $user_id);
$badgeStmt->execute();
$badgeResult = $badgeStmt->get_result();

$badges = [];
while ($row = $badgeResult->fetch_assoc()) {
    $badges[] = $row;
}
?>

<main class="dashboard">
  <div class="main-content">

    <div class="page-title-bar">
      <h2>My Profile</h2>
    </div>

    <!-- STATUS MESSAGES -->
    <?php if (!empty($success)): ?>
      <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="profile-container">

      <!-- PROFILE PHOTO -->
      <div class="profile-avatar-large">
        <?php if (!empty($user['profile_image'])): ?>
          <img
            src="../images/profile/<?= htmlspecialchars($user['profile_image']) ?>"
            alt="Profile"
          >
        <?php else: ?>
          👤
        <?php endif; ?>
      </div>

      <!-- ACTION BUTTONS -->
      <div style="display:flex;justify-content:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
        <a href="change_profile.php" class="action-btn" style="min-width:180px;">
          Change Profile Photo
        </a>
        <a href="change_password.php" class="action-btn" style="min-width:180px;">
          Change Password
        </a>
      </div>

      <!-- PROFILE FORM -->
      <form class="profile-form" method="POST">

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
          <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
        </div>

        <div class="form-group">
          <label>Role :</label>
          <input type="text" value="<?= htmlspecialchars($user['role']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Eco Points :</label>
          <input type="text" value="<?= htmlspecialchars($user['eco_points']) ?>" readonly>
        </div>

        <!-- BADGES -->
        <div class="form-group">
          <label>Badges :</label>

          <div style="display:flex; gap:14px; overflow-x:auto;">
            <?php if (!empty($badges)): ?>
              <?php foreach ($badges as $badge): ?>
                <div style="
                  width:100px;
                  height:80px;
                  background:#f2f2f2;
                  border-radius:10px;
                  display:flex;
                  align-items:center;
                  justify-content:center;
                ">
                  <img
                    src="../images/badges/<?= htmlspecialchars($badge['icon_path']) ?>"
                    alt="<?= htmlspecialchars($badge['badge_name']) ?>"
                    title="<?= htmlspecialchars($badge['badge_name']) ?> (<?= htmlspecialchars($badge['date_awarded']) ?>)"
                    style="max-width:80px; max-height:80px; object-fit:contain;"
                  >
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <span style="color:#777;">No badges earned yet</span>
            <?php endif; ?>
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

        <button type="submit" name="save_email" class="action-btn" style="margin-top:10px;">
          Save
        </button>

      </form>

    </div>
  </div>
</main>

<?php include "admin_footer.php"; ?>
