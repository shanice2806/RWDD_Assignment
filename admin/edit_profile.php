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
            $_SESSION["email"] = $new_email; // keep session in sync
        } else {
            $error = "Failed to update email.";
        }
    }
}

/* =====================
   FETCH USER DATA
   ===================== */
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

   <!-- STATUS MESSAGE -->
<?php if (!empty($success)): ?>
  <div class="alert-success">
    <?= htmlspecialchars($success) ?>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert-error">
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>    <div class="profile-container">



      <!-- Avatar -->
      <div class="profile-avatar-large">
        <?php if (!empty($user['profile_image'])): ?>
          <img src="../<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile">
        <?php else: ?>
          👤
        <?php endif; ?>
      </div>

      <!-- ACTION BUTTONS -->
    <div style="
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
">

  <a href="change_profile_photo.php"
     class="action-btn"
     style="width:auto; min-width:180px; padding:10px 16px;">
    Change Profile Photo
  </a>

  <a href="change_password.php"
     class="action-btn"
     style="width:auto; min-width:180px; padding:10px 16px;">
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

        <!-- ✅ EDITABLE EMAIL -->
        <div class="form-group">
          <label>Email :</label>
          <input type="email" name="email"
                 value="<?= htmlspecialchars($user['email']) ?>">
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
          <div style="display:flex; gap:10px; overflow-x:auto;">
            <?php foreach ($badges as $badge): ?>
              <div style="
                min-width:60px;
                height:60px;
                background:#e0e0e0;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:6px;
                font-size:12px;
                text-align:center;
              ">
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

        <!-- SAVE BUTTON -->
       <button
  type="submit"
  name="save_email"
  class="action-btn"
  style="
    display: flex;
    align-items: center;
    justify-content: center;
    height: 42px;
    line-height: normal;
  "
>
  Save
</button>


      </form>

    </div>
  </div>
</main>

<?php include "admin_footer.php"; ?>
