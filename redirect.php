<?php
session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");  
  exit();
}

$role = strtolower(trim($_SESSION["role"] ?? "user"));

if ($role === "admin") {
  header("Location: admin/admin_dashboard.php");
  exit();
}

header("Location: /RWDD_Assignment/user/user_dashboard.php");
exit();
