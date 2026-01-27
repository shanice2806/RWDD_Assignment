<?php
require_once "../connect.php";

$page_title = "Forgot Password";

$success = "";
$error = "";
$temp_password = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email   = trim($_POST['email']);
    $user_id = trim($_POST['user_id']); // TP number

    if ($email === "" || $user_id === "") {
        $error = "Please enter your TP Number and email.";
    } else {

        /* =====================
           VERIFY USER ID + EMAIL MATCH
        ===================== */
        $stmt = $conn->prepare("
            SELECT user_id, email
            FROM users
            WHERE user_id = ?
              AND email = ?
        ");
        $stmt->bind_param("ss", $user_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "TP Number and email do not match.";
        } else {

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

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
<link rel="stylesheet" href="../css/style.css">

<style>
.wrap {
    max-width:420px;
    margin:220px auto;
    background:#fff;
    padding:24px;
    border-radius:12px;
    box-shadow:0 20px 30px rgba(0,0,0,0.5);
}
h1 {
    margin-bottom:14px;
    font-size:30px;
    text-align:center;
}
label {
    display:block;
    margin:12px 0 6px;
    font-weight:600;
}
input {
    width:95%;
    padding:10px;
    border:1px solid #ddd;
    border-radius:10px;
}

.btn {
    margin-top:16px;
    width:100%;
    font-weight:700;
}
.error {
    background:#ffe8e8;
    color:#b00020;
    padding:10px 12px;
    border-radius:10px;
    margin-bottom:12px;
}
.success {
    background:#e7f8ef;
    color:#1e7e34;
    padding:14px;
    border-radius:10px;
}
.login-top-img {
    text-align:center;
    margin-bottom:16px;
}
.login-top-img img {
    max-width:100%;
    border-radius:10px;
}
</style>
</head>

<body>

<div class="wrap">

  <div class="login-top-img">
    <img src="../images/apu.png" alt="ReLife Hub">
  </div>

  <h1>Forgot Password</h1>

  <!-- SUCCESS -->
  <?php if (!empty($success)): ?>
    <div class="success">
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

      <a href="login_forgot_password.php" class="btn">
        Login Now
      </a>
    </div>
  <?php endif; ?>

  <!-- ERROR -->
  <?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- FORM -->
  <?php if (empty($success)): ?>
    <form method="POST">

      <label>TP Number</label>
      <input type="text" name="user_id" placeholder="TP000123" required>

      <label>Email Address</label>
      <input type="email" name="email" placeholder="example@gmail.com" required>

      <button type="submit" class="btn">
        Generate Temporary Password
      </button>

    </form>
  <?php endif; ?>

</div>

<footer style="text-align:center;margin-top:150px;">
  <p>&copy; 2026 ReLife Hub</p>
</footer>

</body>
</html>


