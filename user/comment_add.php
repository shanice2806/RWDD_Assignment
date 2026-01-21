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

if ($post_id !== "" && $comment !== "") {

  $check = mysqli_prepare($conn, "SELECT post_id FROM posts WHERE post_id = ? LIMIT 1");
  mysqli_stmt_bind_param($check, "s", $post_id);
  mysqli_stmt_execute($check);
  $res = mysqli_stmt_get_result($check);

  if (mysqli_fetch_assoc($res)) {
    $sql = "INSERT INTO community_comments (post_id, user_id, comment_text, date_posted)
            VALUES (?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $post_id, $user_id, $comment);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_query($conn, "UPDATE posts SET comment_count = comment_count + 1 WHERE post_id = '$post_id'");
  }

  mysqli_stmt_close($check);
}

header("Location: user_community.php");
exit();
