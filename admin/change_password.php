<?php
session_start();
require_once "../connect.php";

/* =====================
   CHECK LOGIN
   ===================== */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$error = "";
$success = "";

/* =====================
   WHEN FORM IS SUBMITTED
   ===================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $current_password = trim($_POST["current_password"]);
    $new_password     = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    /* 1️⃣ Check empty fields */
    if ($current_password == "" || $new_password == "" || $confirm_password == "") {
        $error = "All fields are required.";

    } else {

        /* 2️⃣ Get password from database */
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            $error = "User not found.";

        } else {

            $db_password = trim($row["password"]);

            /* 3️⃣ Check current password */
            if ($current_password !== $db_password) {
                $error = "Current password is incorrect.";

            /* 4️⃣ New password must be different */
            } elseif ($new_password === $current_password) {
                $error = "New password cannot be the same as current password.";

            /* 5️⃣ New password confirmation */
            } elseif ($new_password !== $confirm_password) {
                $error = "New password and re-enter password do not match.";

            } else {

                /* 6️⃣ Update password */
                $update = $conn->prepare("
                    UPDATE users
                    SET password = ?
                    WHERE user_id = ?
                ");
                $update->bind_param("ss", $new_password, $user_id);

                if ($update->execute()) {
                    $success = "Password updated successfully.";
                } else {
                    $error = "Failed to update password.";
                }
            }
        }
    }
}
?>

<?php include "admin_header.php"; ?>

<main class="dashboard">
  <div class="main-content">

    <div class="page-title-bar">
      <a href="view_profile.php" class="icon-btn back-btn">↩</a>
      <h2>Change Password</h2>
    </div>

    <div style="
      max-width:420px;
      margin:40px auto;
      padding:30px;
      background:#e0e0e0;
      border-radius:8px;
      text-align:center;
    ">

      <!-- MESSAGE -->
      <?php if ($error): ?>
        <div style="background:#fdecea;color:#b02a37;padding:10px;border-radius:6px;margin-bottom:15px;">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div style="background:#e6f4ea;color:#1e7e34;padding:10px;border-radius:6px;margin-bottom:15px;">
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <!-- FORM -->
      <form method="POST">

        <div class="form-group">
          <label>Current Password</label>
          <input type="password" name="current_password" placeholder="Enter current password">
        </div>

        <div class="form-group">
          <label>New Password</label>
          <input type="password" name="new_password" placeholder="Enter new password">
        </div>

        <div class="form-group">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" placeholder="Re-enter new password">
        </div>

        <div class="form-actions">
        <div style="display:flex; justify-content:center; gap:12px; margin-top:20px;">          
          <button type="submit"
                  class="action-btn">
            Change
          </button>
        
        </div>

      </form>
    </div>

  </div>
</main>

<?php include "admin_footer.php"; ?>
