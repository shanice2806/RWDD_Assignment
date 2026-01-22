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
$reason  = trim($_POST["reason"] ?? "");

$back = $_SERVER["HTTP_REFERER"] ?? "user_community.php";

if ($post_id === "" || $reason === "") {
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

// generate report_id
$getLastSql = "SELECT report_id FROM post_reports ORDER BY report_id DESC LIMIT 1";
$lastRes = mysqli_query($conn, $getLastSql);

$newReportId = "rpt_001";
if ($lastRes && ($r = mysqli_fetch_assoc($lastRes))) {
  $last = $r["report_id"];         
  $num  = (int)substr($last, 4);    
  $num++;
  $newReportId = "rpt_" . str_pad($num, 3, "0", STR_PAD_LEFT);
}

mysqli_begin_transaction($conn);

try {
  $insSql = "INSERT INTO post_reports (report_id, post_id, reported_by, report_reason, reported_at)
             VALUES (?, ?, ?, ?, NOW())";
  $insStmt = mysqli_prepare($conn, $insSql);
  mysqli_stmt_bind_param($insStmt, "ssss", $newReportId, $post_id, $user_id, $reason);
  mysqli_stmt_execute($insStmt);
  mysqli_stmt_close($insStmt);

  $upSql = "UPDATE posts
            SET report_count = report_count + 1
            WHERE post_id = ?";
  $upStmt = mysqli_prepare($conn, $upSql);
  mysqli_stmt_bind_param($upStmt, "s", $post_id);
  mysqli_stmt_execute($upStmt);
  mysqli_stmt_close($upStmt);

  $flagSql = "UPDATE posts
              SET is_flagged = CASE WHEN report_count >= 3 THEN 1 ELSE is_flagged END
              WHERE post_id = ?";
  $flagStmt = mysqli_prepare($conn, $flagSql);
  mysqli_stmt_bind_param($flagStmt, "s", $post_id);
  mysqli_stmt_execute($flagStmt);
  mysqli_stmt_close($flagStmt);

  mysqli_commit($conn);
} catch (Throwable $e) {
  mysqli_rollback($conn);
}

header("Location: $back");
exit();
