<?php
session_start();
require_once "../connect.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");
  exit();
}

$user_id = $_SESSION["user_id"];

// 从 DB 再确认 role（防止别人改 session）
$sql = "SELECT role FROM users WHERE user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$role = strtolower(trim($row["role"] ?? ""));

if ($role !== "organizer" && $role !== "event organizer") {
  header("Location: ../user/user_dashboard.php?err=not_organizer");
  exit();
}

header("Location: organizer_dashboard.php");
exit();
