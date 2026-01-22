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
$title   = trim($_POST["title"] ?? "");
$body    = trim($_POST["body"] ?? "");
$level   = trim($_POST["difficulty_level"] ?? "");

if ($post_id === "" || $title === "" || $body === "" || $level === "") {
  header("Location: tutorial_view.php");
  exit();
}

$sql = "UPDATE posts
        SET title=?, body=?, difficulty_level=?
        WHERE post_id=? AND user_id=?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssss", $title, $body, $level, $post_id, $user_id);
mysqli_stmt_execute($stmt);
$ok = (mysqli_stmt_affected_rows($stmt) >= 0); 
mysqli_stmt_close($stmt);

if ($ok) {
  header("Location: tutorial_detail.php?id=" . urlencode($post_id) . "&edit=success");
  exit();
}

header("Location: tutorial_edit.php?id=" . urlencode($post_id));
exit();
