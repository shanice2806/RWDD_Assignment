<?php
session_start();
require_once "../connect.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");
  exit();
}

$user_id = $_SESSION["user_id"];

$post_id = $_POST["post_id"];
$title   = $_POST["title"];
$body    = $_POST["body"];
$level   = $_POST["difficulty_level"];

$sql = "UPDATE posts
        SET title='$title',
            body='$body',
            difficulty_level='$level'
        WHERE post_id='$post_id' AND user_id='$user_id'";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_affected_rows($conn) > 0) {
  echo "<script>alert('Changed Successful'); window.location.href='tutorial_view.php';</script>";
} else {
  echo "<script>alert('Update Failed (not yours / not found)'); window.location.href='tutorial_view.php';</script>";
}
?>
