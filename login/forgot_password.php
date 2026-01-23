<?php
session_start();
require_once "../connect.php";

$page_title = "Forgot Password";
include "user_header.php"; // or admin_header.php if shared

$success = "";
$error = "";
$temp_password = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);

    if ($email === "") {
        $error = "Please enter your email.";
    } else {

        /* =====================
           CHECK USER EXISTS
        ===================== */
        $stmt = $conn->prepare("
            SELECT user_id, email
            FROM users
            WHERE email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "No account found with this email.";
        } else {

            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];

            /* =====================
               GENERATE TEMP PASSWORD
            ===================== */
            $temp_password = substr(
                str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"),
                0,
                8
            );

            $requested_at = date("Y-m-d H:i:s");
            $expires_at   = date("Y-m-d H:i:s", strtotime("+8 hours"));

            /* =====================
               GENERATE RESET ID
            ===================== */
            $idResult = $conn->query("
                SELECT reset_id
                FROM reset_password
                ORDER BY reset_id DESC
                LIMIT 1
            ");

            if ($row = $idResult->fetch_assoc()) {
                $num = (int) filter_var($row['reset_id'], FILTER_SANITIZE_NUMBER_INT) + 1;
            } else {
                $num = 1;
            }

            $reset_id = "rp_" . str_pad($num, 3, "0", STR_PAD_LEFT);

            /* =====================
               INSERT RESET RECORD
            ===================== */
            $stmt = $conn->prepare("
                INSERT INTO reset_password
                (reset_id, user_id, email, temporary_password, status, requested_at, expires_at)
                VALUES (?, ?, ?, ?, 1, ?, ?)
            ");

            $stmt->bind_param(
                "ssssss",
                $reset_id,
                $user_id,
                $email,
                $temp_password,
                $requested_at,
                $expires_at
            );

            if ($stmt->execute()) {
                $success = "Temporary password generated successfully.";
            } else {
                $error = "Failed to generate temporary password.";
            }
        }
    }
}
?>

<main class="dashboard">
<div class="main-content">

  <div class="page-title-bar">
    <a href="login.php" class="icon-btn back-btn">↩</a>
    <h2>Forgot Password</h2>
  </div>

  <div class="profile-container">

    <!-- SUCCESS MESSAGE -->
    <?php if (!empty($success)): ?>
      <div class="alert-success">
        <p><?= htmlspecialchars($success) ?></p>

        <p style="margin-top:10px;">
          <strong>Your Temporary Password:</strong><br>
          <span style="font-size:18px;">
            <?= htmlspecialchars($temp_password) ?>
          </span>
        </p>

        <p style="margin-top:10px;">
          Please log in using this temporary password and change it immediately.
        </p>

        <!-- LOGIN NOW BUTTON -->
        <div style="text-align:center;margin-top:20px;">
          <a href="login_forgot_password.php" class="action-btn">
            Login Now
          </a>
        </div>
      </div>
    <?php endif; ?>

    <!-- ERROR MESSAGE -->
    <?php if (!empty($error)): ?>
      <div class="alert-error">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- FORM (HIDDEN AFTER SUCCESS) -->
    <?php if (empty($success)): ?>
    <div class="form-panel">
      <form method="POST">

        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" required>
        </div>

        <div style="text-align:center;margin-top:20px;">
          <button type="submit" class="action-btn">
            GENERATE TEMP PASSWORD
          </button>
        </div>

      </form>
    </div>
    <?php endif; ?>

  </div>

</div>
</main>

<?php include "user_footer.php"; ?>
