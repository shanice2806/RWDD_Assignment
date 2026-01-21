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
$id = $_GET["id"] ?? "";

if ($id === "") {
  echo "<script>alert('Error: No ID provided.'); window.location.href='tutorial_view.php';</script>";
  exit();
}

$delComments = "DELETE FROM community_comments WHERE post_id='$id'";
mysqli_query($conn, $delComments);

$delPost = "DELETE FROM posts WHERE post_id='$id' AND user_id='$user_id'";
mysqli_query($conn, $delPost);

if (mysqli_affected_rows($conn) > 0) {
  echo "<script>alert('Deleted Successful'); window.location.href='tutorial_view.php';</script>";
} else {
  echo "<script>alert('Delete Failed (not yours / not found)'); window.location.href='tutorial_view.php';</script>";
}
?>
