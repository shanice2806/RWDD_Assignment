<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "../connect.php";

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

$rulePoints = 15;
$ruleSql = "SELECT points_per_kg
            FROM eco_points_rules
            WHERE rule_key='uploading_content' AND is_active=1
            LIMIT 1";
$ruleRes = mysqli_query($conn, $ruleSql);
if ($ruleRes && ($r = mysqli_fetch_assoc($ruleRes))) {
  $rulePoints = (int)($r["points_per_kg"] ?? 15);
  if ($rulePoints < 0) $rulePoints = 0;
}

mysqli_begin_transaction($conn);

try {
  $ownSql = "SELECT post_id, COALESCE(points_awarded, 0) AS points_awarded
             FROM posts
             WHERE post_id=? AND user_id=? LIMIT 1 FOR UPDATE";
  $ownStmt = mysqli_prepare($conn, $ownSql);
  mysqli_stmt_bind_param($ownStmt, "ss", $post_id, $user_id);
  mysqli_stmt_execute($ownStmt);
  $ownRes = mysqli_stmt_get_result($ownStmt);
  $postRow = mysqli_fetch_assoc($ownRes);
  mysqli_stmt_close($ownStmt);

  if (!$postRow) {
    mysqli_rollback($conn);
    header("Location: tutorial_view.php");
    exit();
  }

  $points_awarded = (int)($postRow["points_awarded"] ?? 0);
  if ($points_awarded <= 0) $points_awarded = $rulePoints;
  $deduct = 0 - $points_awarded;

  $paths = [];
  $mSql = "SELECT file_path
           FROM post_media
           WHERE post_id=? AND media_type='image'
           FOR UPDATE";
  $mStmt = mysqli_prepare($conn, $mSql);
  mysqli_stmt_bind_param($mStmt, "s", $post_id);
  mysqli_stmt_execute($mStmt);
  $mRes = mysqli_stmt_get_result($mStmt);
  while ($r2 = mysqli_fetch_assoc($mRes)) $paths[] = trim($r2["file_path"] ?? "");
  mysqli_stmt_close($mStmt);

  $delMediaSql = "DELETE FROM post_media WHERE post_id=?";
  $dm = mysqli_prepare($conn, $delMediaSql);
  mysqli_stmt_bind_param($dm, "s", $post_id);
  mysqli_stmt_execute($dm);
  mysqli_stmt_close($dm);

  $delPostSql = "DELETE FROM posts WHERE post_id=? AND user_id=? LIMIT 1";
  $dp = mysqli_prepare($conn, $delPostSql);
  mysqli_stmt_bind_param($dp, "ss", $post_id, $user_id);
  mysqli_stmt_execute($dp);
  mysqli_stmt_close($dp);

  if ($points_awarded > 0) {
    $upSql = "UPDATE users SET eco_points = eco_points + ? WHERE user_id=? LIMIT 1";
    $up = mysqli_prepare($conn, $upSql);
    mysqli_stmt_bind_param($up, "is", $deduct, $user_id);
    mysqli_stmt_execute($up);
    mysqli_stmt_close($up);

    $transaction_id = "ept_" . uniqid();
    $source_id = $post_id;
    $desc = "Points deducted for deleting tutorial content. (post: " . $post_id . ")";
    $created_at = date("Y-m-d H:i:s");

    $txSql = "INSERT INTO eco_points_transactions
              (transaction_id, user_id, source_id, points_change, description, created_at)
              VALUES (?, ?, ?, ?, ?, ?)";
    $tx = mysqli_prepare($conn, $txSql);
    mysqli_stmt_bind_param($tx, "sssiss", $transaction_id, $user_id, $source_id, $deduct, $desc, $created_at);
    mysqli_stmt_execute($tx);
    mysqli_stmt_close($tx);
  }

  mysqli_commit($conn);

  foreach ($paths as $p) {
    if ($p !== "" && str_starts_with($p, "images/post_media/")) {
      $full = __DIR__ . "/../" . $p;
      if (is_file($full)) @unlink($full);
    }
  }

  header("Location: tutorial_view.php?deleted=1");
  exit();

} catch (Throwable $e) {
  mysqli_rollback($conn);
  die("Delete failed: " . htmlspecialchars($e->getMessage()));
}
