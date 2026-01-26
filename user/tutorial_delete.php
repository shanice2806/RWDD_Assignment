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

$post_id = trim($_GET["id"] ?? "");
if ($post_id === "") {
  header("Location: tutorial_view.php");
  exit();
}

mysqli_begin_transaction($conn);

try {
  $ownSql = "SELECT post_id FROM posts WHERE post_id=? AND user_id=? LIMIT 1 FOR UPDATE";
  $ownStmt = mysqli_prepare($conn, $ownSql);
  mysqli_stmt_bind_param($ownStmt, "ss", $post_id, $user_id);
  mysqli_stmt_execute($ownStmt);
  $ownRes = mysqli_stmt_get_result($ownStmt);
  $ownRow = mysqli_fetch_assoc($ownRes);
  mysqli_stmt_close($ownStmt);

  if (!$ownRow) {
    mysqli_rollback($conn);
    header("Location: tutorial_view.php");
    exit();
  }

  $paths = [];
  $mSql = "SELECT file_path, media_type
           FROM post_media
           WHERE post_id=?
           FOR UPDATE";
  $mStmt = mysqli_prepare($conn, $mSql);
  mysqli_stmt_bind_param($mStmt, "s", $post_id);
  mysqli_stmt_execute($mStmt);
  $mRes = mysqli_stmt_get_result($mStmt);
  while ($r = mysqli_fetch_assoc($mRes)) {
    $paths[] = $r;
  }
  mysqli_stmt_close($mStmt);

  $delMediaSql = "DELETE FROM post_media WHERE post_id=?";
  $dmStmt = mysqli_prepare($conn, $delMediaSql);
  mysqli_stmt_bind_param($dmStmt, "s", $post_id);
  mysqli_stmt_execute($dmStmt);
  mysqli_stmt_close($dmStmt);

  $delPostSql = "DELETE FROM posts WHERE post_id=? AND user_id=? LIMIT 1";
  $dpStmt = mysqli_prepare($conn, $delPostSql);
  mysqli_stmt_bind_param($dpStmt, "ss", $post_id, $user_id);
  mysqli_stmt_execute($dpStmt);
  mysqli_stmt_close($dpStmt);

  mysqli_commit($conn);

  foreach ($paths as $p) {
    $type = $p["media_type"] ?? "";
    $filePath = trim($p["file_path"] ?? "");

    if ($type === "image" && str_starts_with($filePath, "../images/post_media/")) {
      $abs = realpath(__DIR__ . "/" . $filePath);
      $base = realpath(__DIR__ . "/../images/post_media");

      if ($abs && $base && str_starts_with($abs, $base) && file_exists($abs)) {
        @unlink($abs);
      }
    }
  }

  header("Location: tutorial_view.php");
  exit();

} catch (Throwable $e) {
  mysqli_rollback($conn);
  die("Delete failed: " . $e->getMessage());
}
