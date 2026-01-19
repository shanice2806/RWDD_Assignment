<?php
session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login.php");
  exit();
}

$role = strtolower($_SESSION["role"] ?? "user");

if ($role === "admin") {
header("Location: admin/admin_dashboard.php");
} elseif ($role === "event organizer" || $role === "event_organizer" || $role === "organizer") {
  header("Location: event organizer/organizer_dashboard.php");
} else {
  header("Location: user/user_dashboard.php");
}
exit();
