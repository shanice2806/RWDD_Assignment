<?php
session_start();
require_once "../connect.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");
  exit();
}

$user_id = $_SESSION["user_id"];

$code1 = strtoupper(trim($_POST["code1"] ?? ""));
$code2 = strtoupper(trim($_POST["code2"] ?? ""));
$code3 = strtoupper(trim($_POST["code3"] ?? ""));

if ($code1 === "" || $code2 === "" || $code3 === "") {
  header("Location: user_event.php?att=err");
  exit();
}

function getAllEventCodes() {
  return [
    "evt_001" => ["A1B2", "C3D4", "E5F6"],
    "evt_002" => ["1111", "2222", "3333"],
    "evt_003" => ["APU1", "APU2", "APU3"],
    "evt_004" => ["QWER", "ASDF", "ZXCV"],
    "evt_005" => ["9999", "8888", "7777"],
  ];
}

$event_id = "";
$map = getAllEventCodes();

foreach ($map as $evtId => $codes) {
  if ($code1 === $codes[0] && $code2 === $codes[1] && $code3 === $codes[2]) {
    $event_id = $evtId;
    break;
  }
}

if ($event_id === "") {
  header("Location: user_event.php?att=err");
  exit();
}

$chkEvt = mysqli_prepare($conn, "SELECT 1 FROM events WHERE event_id=? LIMIT 1");
mysqli_stmt_bind_param($chkEvt, "s", $event_id);
mysqli_stmt_execute($chkEvt);
$evtExists = mysqli_stmt_get_result($chkEvt)->num_rows;
mysqli_stmt_close($chkEvt);

if ($evtExists == 0) {
  header("Location: user_event.php?att=err");
  exit();
}

$chk = mysqli_prepare($conn, "SELECT 1 FROM attendance WHERE user_id=? AND event_id=? LIMIT 1");
mysqli_stmt_bind_param($chk, "ss", $user_id, $event_id);
mysqli_stmt_execute($chk);
$already = mysqli_stmt_get_result($chk)->num_rows;
mysqli_stmt_close($chk);

if ($already > 0) {
  header("Location: user_event.php?att=dup");
  exit();
}

$newId = "att_" . str_pad((string)rand(1, 999999), 6, "0", STR_PAD_LEFT);

$method = "CODE:$code1-$code2-$code3";

$ins = mysqli_prepare($conn, "
  INSERT INTO attendance (attendance_id, event_id, user_id, attendance_check_in_time, attendance_method)
  VALUES (?, ?, ?, NOW(), ?)
");
mysqli_stmt_bind_param($ins, "ssss", $newId, $event_id, $user_id, $method);
$ok = mysqli_stmt_execute($ins);
mysqli_stmt_close($ins);

if ($ok) {
  header("Location: user_event.php?att=ok");
  exit();
}

header("Location: user_event.php?att=err");
exit();
