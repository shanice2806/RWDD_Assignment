<?php
session_start();
require_once "../connect.php";

$error = "";

/* If already logged in via forgot flow, redirect */
if (isset($_SESSION['reset_user_id'])) {
    header("Location: reset_password.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? "");
    $temp_password = trim($_POST['temporary_password'] ?? "");

    if ($email === "" || $temp_password === "") {
        $error = "Please enter your email and temporary password.";
    } else {

        /* =====================
           VERIFY TEMP PASSWORD
        ===================== */
        $stmt = $conn->prepare("
            SELECT reset_id, user_id
            FROM reset_password
            WHERE email = ?
              AND temporary_password = ?
              AND status = 1
              AND expires_at > NOW()
            LIMIT 1
        ");

        if (!$stmt) {
            $error = "SQL error: " . $conn->error;
        } else {

            $stmt->bind_param("ss", $email, $temp_password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {

                /* Store session for reset step */
                $_SESSION['reset_id'] = $row['reset_id'];
                $_SESSION['reset_user_id'] = $row['user_id'];
                $_SESSION['reset_email'] = $email;

                header("Location: reset_password.php");
                exit();

            } else {
                $error = "Invalid or expired temporary password.";
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password Login</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .wrap {
      max-width:420px;
      margin:200px auto;
      background:#fff;
      padding:24px;
      border-radius:12px;
      box-shadow:0 20px 30px rgba(0,0,0,0.15);
    }
    h1 { margin-bottom:16px; }
    label { font-weight:600; display:block; margin-top:14px; }
    input {
      width:100%;
      padding:10px;
      border:1px solid #ddd;
      border-radius:8px;
      margin-top:6px;
    }
    .btn {
      margin-top:20px;
      width:100%;
      font-weight:700;
    }
    .error {
      background:#ffe8e8;
      color:#b00020;
      padding:10px;
      border-radius:8px;
      margin-bottom:12px;
    }
  </style>
</head>
<body>

<div class="wrap">
  <h1>Forgot Password Login</h1>

  <?php if ($error !== ""): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">

    <label>Email Address</label>
    <input type="email" name="email" required>

    <label>Temporary Password</label>
    <input type="text" name="temporary_password" required>

    <button class="btn" type="submit">Continue</button>

  </form>
</div>

</body>
</html>
