<?php
session_start();
require_once "../connect.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");
  exit();
}

$user_id = $_SESSION["user_id"];

$title = $_POST["title"];
$body  = $_POST["body"];
$level = $_POST["difficulty_level"];
$cat   = $_POST["content_category_id"];

$getLast = "SELECT post_id FROM posts ORDER BY post_id DESC LIMIT 1";
$resLast = mysqli_query($conn, $getLast);

$newId = "p_001";
if ($resLast && ($r = mysqli_fetch_assoc($resLast))) {
  $last = $r["post_id"];
  $num  = (int)substr($last, 2);
  $num++;
  $newId = "p_" . str_pad($num, 3, "0", STR_PAD_LEFT);
}

$sql = "INSERT INTO posts (post_id, user_id, content_category_id, title, body, difficulty_level, post_status, post_created_at)
        VALUES ('$newId', '$user_id', '$cat', '$title', '$body', '$level', 'Public', NOW())";

$result = mysqli_query($conn, $sql);

if ($result) {
  echo "<script>alert('Upload Successful'); window.location.href='tutorial_view.php';</script>";
} else {
  echo "<script>alert('Upload Failed'); window.location.href='user_tutorial_add.php';</script>";
}
?>
