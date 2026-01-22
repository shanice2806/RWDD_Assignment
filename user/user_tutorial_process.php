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

$title = trim($_POST["title"] ?? "");
$body  = trim($_POST["body"] ?? "");
$level = trim($_POST["difficulty_level"] ?? "");
$cat   = trim($_POST["content_category_id"] ?? "");

if ($title === "" || $body === "" || $level === "" || $cat === "") {
  header("Location: user_tutorial_add.php");
  exit();
}

$earnedPoints = 15;

mysqli_begin_transaction($conn);

try {
  $lastSql = "SELECT post_id FROM posts ORDER BY post_id DESC LIMIT 1 FOR UPDATE";
  $resLast = mysqli_query($conn, $lastSql);

  $newId = "p_001";
  if ($resLast && ($r = mysqli_fetch_assoc($resLast))) {
    $last = $r["post_id"];
    $num  = (int)substr($last, 2);
    $num++;
    $newId = "p_" . str_pad($num, 3, "0", STR_PAD_LEFT);
  }

  $sql = "INSERT INTO posts
          (post_id, user_id, content_category_id, title, body, difficulty_level, post_status, post_created_at)
          VALUES (?, ?, ?, ?, ?, ?, 'Public', NOW())";

  $stmt = mysqli_prepare($conn, $sql);
  if (!$stmt) throw new Exception("Prepare insert failed: " . mysqli_error($conn));

  mysqli_stmt_bind_param($stmt, "ssssss", $newId, $user_id, $cat, $title, $body, $level);
  if (!mysqli_stmt_execute($stmt)) throw new Exception("Execute insert failed: " . mysqli_stmt_error($stmt));
  mysqli_stmt_close($stmt);


  $pSql = "UPDATE users SET eco_points = COALESCE(eco_points,0) + ? WHERE user_id = ?";
  $pStmt = mysqli_prepare($conn, $pSql);
  if (!$pStmt) throw new Exception("Prepare points failed: " . mysqli_error($conn));

  mysqli_stmt_bind_param($pStmt, "is", $earnedPoints, $user_id);
  if (!mysqli_stmt_execute($pStmt)) throw new Exception("Execute points failed: " . mysqli_stmt_error($pStmt));
  mysqli_stmt_close($pStmt);

  mysqli_commit($conn);

  $_SESSION["flash_success"] = [
    "type"   => "upload",
    "points" => $earnedPoints
  ];

  header("Location: tutorial_view.php");
  exit();

} catch (Throwable $e) {
  mysqli_rollback($conn);



  header("Location: user_tutorial_add.php");
  exit();
}
