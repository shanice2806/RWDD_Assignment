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

$role = strtolower(trim($_SESSION["role"] ?? ""));
if ($role === "admin") {
  header("Location: ../admin/admin_dashboard.php");
  exit();
}

$user_id = $_SESSION["user_id"];

function redirect_att($status) {
  header("Location: user_event.php?att=" . urlencode($status));
  exit();
}

function make_att_id() {
  return "att_" . date("YmdHis") . rand(10, 99);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  redirect_att("fail");
}

$code = strtoupper(trim($_POST["code"] ?? ""));
if ($code === "") {
  redirect_att("fail");
}

$evtStmt = mysqli_prepare($conn, "
  SELECT event_id
  FROM events
  WHERE event_attendance_code = ?
    AND event_status = 'Approved'
  LIMIT 1
");
if (!$evtStmt) redirect_att("fail");

mysqli_stmt_bind_param($evtStmt, "s", $code);
mysqli_stmt_execute($evtStmt);
$evtRes = mysqli_stmt_get_result($evtStmt);
$evtRow = mysqli_fetch_assoc($evtRes);
mysqli_stmt_close($evtStmt);

if (!$evtRow) {
  redirect_att("badcode");
}

$event_id = trim($evtRow["event_id"]);

$regStmt = mysqli_prepare($conn, "
  SELECT registration_status
  FROM registrations
  WHERE user_id = ?
    AND event_id = ?
  LIMIT 1
");
if (!$regStmt) redirect_att("fail");

mysqli_stmt_bind_param($regStmt, "ss", $user_id, $event_id);
mysqli_stmt_execute($regStmt);
$regRes = mysqli_stmt_get_result($regStmt);
$regRow = mysqli_fetch_assoc($regRes);
mysqli_stmt_close($regStmt);

if (!$regRow) {
  redirect_att("noreg");
}

$regStatus = strtolower(trim($regRow["registration_status"] ?? "registered"));
if ($regStatus === "cancelled") {
  redirect_att("noreg");
}

$dupStmt = mysqli_prepare($conn, "
  SELECT attendance_id
  FROM attendance
  WHERE user_id = ?
    AND event_id = ?
  LIMIT 1
");
if (!$dupStmt) redirect_att("fail");

mysqli_stmt_bind_param($dupStmt, "ss", $user_id, $event_id);
mysqli_stmt_execute($dupStmt);
$dupRes = mysqli_stmt_get_result($dupStmt);
$dup = (mysqli_num_rows($dupRes) > 0);
mysqli_stmt_close($dupStmt);

if ($dup) {
  redirect_att("dup");
}

$att_id = make_att_id();

$method = "Code";

$insStmt = mysqli_prepare($conn, "
  INSERT INTO attendance
    (attendance_id, event_id, user_id, attendance_check_in_time, attendance_method)
  VALUES
    (?, ?, ?, NOW(), ?)
");
if (!$insStmt) redirect_att("fail");

mysqli_stmt_bind_param($insStmt, "ssss", $att_id, $event_id, $user_id, $method);
$ok = mysqli_stmt_execute($insStmt);
mysqli_stmt_close($insStmt);

if (!$ok) {
  redirect_att("fail");
}

redirect_att("ok");
