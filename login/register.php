<?php
session_start();
require_once "../connect.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $user_id = strtoupper(trim($_POST["user_id"] ?? ""));
  $name    = trim($_POST["name"] ?? "");
  $email   = trim($_POST["email"] ?? "");
  $pass1   = $_POST["password"] ?? "";
  $pass2   = $_POST["confirm_password"] ?? "";

  if ($user_id === "" || $name === "" || $email === "" || $pass1 === "" || $pass2 === "") {
    $error = "Please fill in all fields.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Invalid email format.";
  } elseif ($pass1 !== $pass2) {
    $error = "Passwords do not match.";
  } elseif (strlen($pass1) < 6) {
    $error = "Password must be at least 6 characters.";
  } else {
    $check = "SELECT user_id FROM users WHERE user_id = ? OR email = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $check);

    if (!$stmt) {
      $error = "SQL error: " . mysqli_error($conn);
    } else {
      mysqli_stmt_bind_param($stmt, "ss", $user_id, $email);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);

      if (mysqli_fetch_assoc($res)) {
        $error = "TP number or email already exists.";
      } else {
        $hashed = password_hash($pass1, PASSWORD_DEFAULT);

        $role = "User";
        $eco_points = 0;
        $badges = NULL;
        $account_status = "Activated";
        $created_at = date("Y-m-d H:i:s");
        $last_login = NULL;
        $profile_image = NULL;

        $sql = "INSERT INTO users
                  (user_id, name, email, role, password, eco_points, badges, created_at, account_status, last_login, profile_image)
                VALUES
                  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $ins = mysqli_prepare($conn, $sql);

        if (!$ins) {
          $error = "SQL error: " . mysqli_error($conn);
        } else {

          mysqli_stmt_bind_param(
            $ins,
            "sssssisssss",
            $user_id,
            $name,
            $email,
            $role,
            $hashed,
            $eco_points,
            $badges,
            $created_at,
            $account_status,
            $last_login,
            $profile_image
          );

          $ok = mysqli_stmt_execute($ins);

          if ($ok) {
            $success = "Account created successfully. You can login now.";
          } else {
            $error = "Register failed: " . mysqli_error($conn);
          }
          mysqli_stmt_close($ins);
        }
      }
      mysqli_stmt_close($stmt);
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register</title>
  <link rel="stylesheet" href="../css/style.css">

  <style>
    body{font-family:Arial,sans-serif;
    background:#f5f6f8;
    margin:0}

    .wrap{max-width:460px;
    margin:50px auto;
    background:#fff;padding:24px;
    border-radius:12px;
    box-shadow:0 6px 18px rgba(0,0,0,.08)}

    h1{margin:0 0 14px;
    font-size:22px}

    label{display:block;
    margin:12px 0 6px;
    font-weight:600}

    input{width:100%;
    padding:10px 7px;
    border:1px solid #ddd;
    border-radius:10px}

    .btn{margin-top:16px;
    width:100%;}

    .msg{padding:10px 12px;border-radius:10px;margin:10px 0}

    .error{background:#ffe8e8;color:#b00020}

    .success{background:#e9ffef;color:#0b6b2b}

    a{text-decoration:none;color:#1f6feb}

        .login-top-img{
    text-align:center;
    margin-bottom:16px;}

    .login-top-img img{
    max-width:100%;
    height:auto;
    border-radius:10px;}

.password-wrapper{
  position: relative;
}

.password-wrapper input{
  padding-right: 5px;
}

.toggle-password{
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  font-size: 18px;
  user-select: none;
  color: #666;
}

.toggle-password:hover{
  color: #000;
}

  </style>
</head>
<body>
  <div class="wrap">
    
    <div class="login-top-img">
      <img src="../images/apu.png" alt="ReLife Hub">
</div>
    <h1>Create Account</h1>

    <?php if ($error !== ""): ?>
      <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
      <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST">
      <label for="user_id">TP Number</label>
      <input id="user_id" name="user_id" type="text" placeholder="example :TP123456" required>

      <label for="name">Full Name</label>
      <input id="name" name="name" type="text" placeholder="Your name" required>

      <label for="email">Email</label>
      <input id="email" name="email" type="email" placeholder="you@example.com" required>

      <label for="password">Password</label>
        <div class="password-wrapper">
          <input id="password" name="password" type="password" placeholder="Min 6 characters" required>
            <span class="toggle-password" onclick="togglePassword('password', this)">👁️</span>
        </div>

      <label for="confirm_password">Confirm Password</label>
        <div class="password-wrapper">
          <input id="confirm_password" name="confirm_password" type="password" required>
            <span class="toggle-password" onclick="togglePassword('confirm_password', this)">👁️</span>
        </div>


      <button class="btn" type="submit">Register</button>
    </form>

    <p style="margin-top:14px;font-size:14px;">
      Already have an account? <a href="login.php">Login</a>
    </p>
  </div>
  <div class="row" style="text-align:center;margin-top:150px;">
  <a href="about.php">About ReLife Hub</a>
</div>
<footer>
  <p>&copy; 2026 ReLife Hub</p>
</footer>
<script>
function togglePassword(inputId, iconEl) {
  const input = document.getElementById(inputId);
  if (!input) return;

  if (input.type === "password") {
    input.type = "text";
    iconEl.textContent = "👀";
  } else {
    input.type = "password";
    iconEl.textContent = "👁️";
  }
}
</script>

</body>
</html>
