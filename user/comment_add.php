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
$post_id = trim($_POST["post_id"] ?? "");
$comment = trim($_POST["comment"] ?? "");

$back = $_SERVER["HTTP_REFERER"] ?? "user_community.php";

if ($post_id === "" || $comment === "") {
  header("Location: $back");
  exit();
}

$checkSql = "SELECT post_id FROM posts WHERE post_id = ? LIMIT 1";
$checkStmt = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($checkStmt, "s", $post_id);
mysqli_stmt_execute($checkStmt);
$checkRes = mysqli_stmt_get_result($checkStmt);
$exists = mysqli_fetch_assoc($checkRes);
mysqli_stmt_close($checkStmt);

if (!$exists) {
  header("Location: $back");
  exit();
}

//will generate id
$getLastSql = "SELECT comment_id FROM community_comments ORDER BY comment_id DESC LIMIT 1";
$lastRes = mysqli_query($conn, $getLastSql);

$newCommentId = "c_001";
if ($lastRes && ($r = mysqli_fetch_assoc($lastRes))) {
  $last = $r["comment_id"];      
  $num  = (int)substr($last, 2);
  $num++;
  $newCommentId = "c_" . str_pad($num, 3, "0", STR_PAD_LEFT);
}

mysqli_begin_transaction($conn);

try {
  $insSql = "INSERT INTO community_comments (comment_id, post_id, user_id, comment_text, date_posted)
             VALUES (?, ?, ?, ?, NOW())";
  $insStmt = mysqli_prepare($conn, $insSql);
  mysqli_stmt_bind_param($insStmt, "ssss", $newCommentId, $post_id, $user_id, $comment);
  mysqli_stmt_execute($insStmt);
  mysqli_stmt_close($insStmt);

  $upSql = "UPDATE posts SET comment_count = comment_count + 1 WHERE post_id = ?";
  $upStmt = mysqli_prepare($conn, $upSql);
  mysqli_stmt_bind_param($upStmt, "s", $post_id);
  mysqli_stmt_execute($upStmt);
  mysqli_stmt_close($upStmt);

  mysqli_commit($conn);
} catch (Throwable $e) {
  mysqli_rollback($conn);
}

header("Location: $back");
exit();
