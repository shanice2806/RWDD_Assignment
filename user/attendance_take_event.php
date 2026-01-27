<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "../connect.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");
  exit();
}

$user_id = $_SESSION["user_id"];

function make_id($prefix) {
  return $prefix . "_" . date("YmdHis") . rand(10, 99);
}

$code = strtoupper(trim($_POST["code"] ?? ""));
if ($code === "") {
  header("Location: user_event.php?att=fail");
  exit();
}

$stmt = mysqli_prepare($conn, "
  SELECT event_id
  FROM events
  WHERE event_attendance_code = ?
    AND event_status = 'Approved'
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "s", $code);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$row) {
  header("Location: user_event.php?att=fail");
  exit();
}

$event_id = $row["event_id"];


$regStmt = mysqli_prepare($conn, "SELECT 1 FROM registrations WHERE user_id=? AND event_id=? LIMIT 1");
mysqli_stmt_bind_param($regStmt, "ss", $user_id, $event_id);
mysqli_stmt_execute($regStmt);
$regRes = mysqli_stmt_get_result($regStmt);
$registered = (mysqli_num_rows($regRes) > 0);
mysqli_stmt_close($regStmt);

if (!$registered) {
  header("Location: user_event.php?att=noreg");
  exit();
}

$dupStmt = mysqli_prepare($conn, "SELECT 1 FROM attendance WHERE user_id=? AND event_id=? LIMIT 1");
mysqli_stmt_bind_param($dupStmt, "ss", $user_id, $event_id);
mysqli_stmt_execute($dupStmt);
$dupRes = mysqli_stmt_get_result($dupStmt);
$exists = (mysqli_num_rows($dupRes) > 0);
mysqli_stmt_close($dupStmt);

if ($exists) {
  header("Location: user_event.php?att=dup");
  exit();
}

$att_id = make_id("att");
$method = "ABC12"; // 之后要改名字再改这里

$ins = mysqli_prepare($conn, "
  INSERT INTO attendance (attendance_id, event_id, user_id, attendance_check_in_time, attendance_method)
  VALUES (?, ?, ?, NOW(), ?)
");
mysqli_stmt_bind_param($ins, "ssss", $att_id, $event_id, $user_id, $method);
$ok = mysqli_stmt_execute($ins);
mysqli_stmt_close($ins);

if (!$ok) {
  header("Location: user_event.php?att=fail");
  exit();
}

header("Location: user_event.php?att=ok");
exit();
