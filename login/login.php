<?php
session_start();
require_once "../connect.php"; 

if (isset($_SESSION["user_id"])) {
  header("Location: ../redirect.php");
  exit();
}

$error = "";

function is_hashed_password($value) {
  return is_string($value) && str_starts_with($value, '$2');
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $identifier = trim($_POST["identifier"] ?? "");
  $password   = $_POST["password"] ?? "";

  if ($identifier === "" || $password === "") {
    $error = "Please enter TP number/email and password.";
  } else {
    $sql = "SELECT user_id, name, email, role, password, account_status, profile_image
            FROM users
            WHERE email = ? OR user_id = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
      $error = "SQL error: " . mysqli_error($conn);
    } else {
      mysqli_stmt_bind_param($stmt, "ss", $identifier, $identifier);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);

      if ($row = mysqli_fetch_assoc($result)) {

        if (strtolower($row["account_status"]) === "deactivated") {
          $error = "Your account is deactivated. Please contact admin.";
        } else {
          $stored = $row["password"];
          $ok = false;

          if (is_hashed_password($stored)) {
            $ok = password_verify($password, $stored);
          } else {
            $ok = hash_equals($stored, $password);
          }

          if ($ok) {
            $_SESSION["user_id"] = $row["user_id"];
            $_SESSION["name"]    = $row["name"];
            $_SESSION["email"]   = $row["email"];
            $_SESSION["role"]    = $row["role"];
            $_SESSION['profile_image'] = $row['profile_image'];



            $upd = mysqli_prepare($conn, "UPDATE users SET last_login = NOW() WHERE user_id = ?");
            if ($upd) {
              mysqli_stmt_bind_param($upd, "s", $row["user_id"]);
              mysqli_stmt_execute($upd);
              mysqli_stmt_close($upd);
            }

            header("Location: ../redirect.php");
            exit();
          } else {
            $error = "Invalid login details.";
          }
        }
      } else {
        $error = "Invalid login details.";
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
  <title>Login</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .wrap{max-width:420px;
    margin:220px auto;
    background:#fff;
    padding:24px;
    border-radius:12px;
    box-shadow:0 20px 30px rgba(0, 0, 0, 0.5)}

    h1{margin:0 0 14px;
    font-size:30px}

    label{display:block;
    margin:12px 0 6px;
    font-weight:600}

    input{width:100%;
    padding:10px 7px;
    border:1px solid #ddd;
    border-radius:10px}

    .btn{margin-top:16px;
    width:100%;
    font-weight:700;
  }

    .row{display:flex;
    justify-content:space-between;
    margin-top:14px;
    font-size:14px}

    .error{background:#ffe8e8;
    color:#b00020;
    padding:10px 12px;
    border-radius:10px;
    margin:10px 0}

    a{text-decoration:none;
    color:#1f6feb}
    
    .login-top-img{
    text-align:center;
    margin-bottom:16px;}
    .login-top-img img{
    max-width:100%;
    height:auto;
    border-radius:10px;}

    .password-wrapper {
  position: relative;
}

.password-wrapper input {
  padding-right: 5px;
}

.toggle-password {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  font-size: 18px;
  user-select: none;
  color: #666;
}

.toggle-password:hover {
  color: #000;
}

  </style>
</head>
<body>
  <div class="wrap">
<div class="login-top-img">
  <img src="../images/apu.png" alt="ReLife Hub">
</div>

    <h1>Login</h1>

    <?php if ($error !== ""): ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
      <label for="identifier">TP Number / Email</label>
      <input id="identifier" name="identifier" type="text" placeholder="TP000002 or adamtan@gmail.com" required>

 <label for="password">Password</label>

<div class="password-wrapper">
  <input id="password" name="password" type="password" required>
  <span class="toggle-password" onclick="togglePassword()">👁️</span>
</div>


      <button class="btn" type="submit">Sign In</button>
    </form>

    <div class="row">
      <span>Dont' have an account?
      <a href="register.php">Register</a>
    </span>
      <a href="forgot_password.php">Forgot password?</a>
    </div>
  </div>

<div class="row" style="justify-content:center;margin-top:150px;">
  <a href="about.php">About ReLife Hub</a>
</div>
<footer>
  <p>&copy; 2026 ReLife Hub</p>
</footer>

<script>
function togglePassword() {
  const pwd = document.getElementById("password");
  const icon = event.target;

  if (pwd.type === "password") {
    pwd.type = "text";
    icon.textContent = "👀";
  } else {
    pwd.type = "password";
    icon.textContent = "👁️";
  }
}
</script>
</body>
</html>
