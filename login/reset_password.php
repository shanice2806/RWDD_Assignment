<?php
require_once "../connect.php";

$error = "";
$success = "";


$email = $_POST['email'] ?? "";
$temp_password = $_POST['temporary_password'] ?? "";


if ($email === "" || $temp_password === "") {
    header("Location: forgot_password.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT user_id, reset_id
    FROM reset_password
    WHERE email = ?
      AND temporary_password = ?
      AND status = 1
      AND expires_at > NOW()
    LIMIT 1
");
$stmt->bind_param("ss", $email, $temp_password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid or expired reset request.");
}

$row = $result->fetch_assoc();
$user_id = $row['user_id'];
$reset_id = $row['reset_id'];


if (isset($_POST['new_password'])) {

    $new_password = $_POST['new_password'];
    $confirm      = $_POST['confirm_password'];

    if ($new_password === "" || $confirm === "") {
        $error = "Please fill in all fields.";
    }
    elseif ($new_password !== $confirm) {
        $error = "Passwords do not match.";
    }
    elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
    }
    else {

        $plain_password = $new_password;

        $stmt = $conn->prepare("
            UPDATE users
            SET password = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param("ss", $plain_password, $user_id);

        if ($stmt->execute()) {

            $stmt2 = $conn->prepare("
                UPDATE reset_password
                SET status = 0
                WHERE reset_id = ?
            ");
            $stmt2->bind_param("s", $reset_id);
            $stmt2->execute();

            $success = "Password reset successful. You may now log in.";
        } else {
            $error = "Failed to reset password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
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
h1 { text-align:center; margin-bottom:16px; }
label { font-weight:600; display:block; margin-top:14px; }
input {
  width: 95%;
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
  padding:10px;
  border-radius:10px;
  margin-bottom:12px;
}
.success {
  background:#e7f8ef;
  color:#1e7e34;
  padding:14px;
  border-radius:10px;
}
</style>
</head>

<body>

<div class="wrap">
<h1>Reset Password</h1>

<?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
  <div class="success">
    <p><?= htmlspecialchars($success) ?></p>
    <a href="login.php" class="btn">Login</a>
  </div>
<?php else: ?>
<form method="POST">

  <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
  <input type="hidden" name="temporary_password" value="<?= htmlspecialchars($temp_password) ?>">

  <label>New Password</label>
  <input type="password" name="new_password" required>

  <label>Confirm Password</label>
  <input type="password" name="confirm_password" required>

  <button class="btn">Reset Password</button>

</form>
<?php endif; ?>

</div>

</body>
</html>
