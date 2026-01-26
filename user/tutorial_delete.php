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
$id = trim($_GET["id"] ?? "");

if ($id === "") {
  header("Location: tutorial_view.php");
  exit();
}

$check = mysqli_prepare($conn, "SELECT post_id FROM posts WHERE post_id=? AND user_id=? LIMIT 1");
mysqli_stmt_bind_param($check, "ss", $id, $user_id);
mysqli_stmt_execute($check);
$checkRes = mysqli_stmt_get_result($check);

if (!mysqli_fetch_assoc($checkRes)) {
  mysqli_stmt_close($check);
  echo "<script>alert('Delete Failed (not yours / not found)'); window.location.href='tutorial_view.php';</script>";
  exit();
}
mysqli_stmt_close($check);

mysqli_begin_transaction($conn);

try {
  $d1 = mysqli_prepare($conn, "DELETE FROM post_reports WHERE post_id=?");
  mysqli_stmt_bind_param($d1, "s", $id);
  mysqli_stmt_execute($d1);
  mysqli_stmt_close($d1);

  $d2 = mysqli_prepare($conn, "DELETE FROM post_media WHERE post_id=?");
  mysqli_stmt_bind_param($d2, "s", $id);
  mysqli_stmt_execute($d2);
  mysqli_stmt_close($d2);

  $d3 = mysqli_prepare($conn, "DELETE FROM community_comments WHERE post_id=?");
  mysqli_stmt_bind_param($d3, "s", $id);
  mysqli_stmt_execute($d3);
  mysqli_stmt_close($d3);

  $d4 = mysqli_prepare($conn, "DELETE FROM posts WHERE post_id=? AND user_id=?");
  mysqli_stmt_bind_param($d4, "ss", $id, $user_id);
  mysqli_stmt_execute($d4);

  $deleted = (mysqli_stmt_affected_rows($d4) > 0);
  mysqli_stmt_close($d4);

  if (!$deleted) {
    throw new Exception("Delete failed");
  }

  mysqli_commit($conn);
  header("Location: tutorial_view.php?del=success");
  exit();

} catch (Throwable $e) {
  mysqli_rollback($conn);
  echo "<script>alert('Delete Failed'); window.location.href='tutorial_view.php';</script>";
  exit();
}
