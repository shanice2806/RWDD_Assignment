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

$uploadDir = __DIR__ . "/../images/post_media/"; 
$dbPrefix  = "../images/post_media/";           

if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0777, true);
}


mysqli_begin_transaction($conn);

try {

  $lastSql = "SELECT post_id FROM posts ORDER BY post_id DESC LIMIT 1 FOR UPDATE";
  $resLast = mysqli_query($conn, $lastSql);

  $newPostId = "p_001";
  if ($resLast && ($r = mysqli_fetch_assoc($resLast))) {
    $last = $r["post_id"];
    $num  = (int)substr($last, 2);
    $num++;
    $newPostId = "p_" . str_pad($num, 3, "0", STR_PAD_LEFT);
  }

  $sql = "INSERT INTO posts
          (post_id, user_id, content_category_id, title, body, difficulty_level, post_status, post_created_at)
          VALUES (?, ?, ?, ?, ?, ?, 'Public', NOW())";
  $stmt = mysqli_prepare($conn, $sql);
  if (!$stmt) throw new Exception("Prepare posts insert failed: " . mysqli_error($conn));

  mysqli_stmt_bind_param($stmt, "ssssss", $newPostId, $user_id, $cat, $title, $body, $level);
  if (!mysqli_stmt_execute($stmt)) throw new Exception("Execute posts insert failed: " . mysqli_stmt_error($stmt));
  mysqli_stmt_close($stmt);


  if (!empty($_FILES["media_files"]) && is_array($_FILES["media_files"]["name"])) {

    $allowed = ["image/jpeg", "image/png", "image/webp"];
    $maxSize = 3 * 1024 * 1024; 

    $lastMediaSql = "SELECT media_id FROM post_media ORDER BY media_id DESC LIMIT 1 FOR UPDATE";
    $resMedia = mysqli_query($conn, $lastMediaSql);

    $mediaNo = 1;
    if ($resMedia && ($mr = mysqli_fetch_assoc($resMedia))) {
      $mediaNo = (int)substr($mr["media_id"], 3) + 1; 
    }

    $orderNo = 1;

    $names = $_FILES["media_files"]["name"];
    $tmp   = $_FILES["media_files"]["tmp_name"];
    $errs  = $_FILES["media_files"]["error"];
    $sizes = $_FILES["media_files"]["size"];

    for ($i = 0; $i < count($names); $i++) {

      if (trim($names[$i]) === "") continue;
      if ($errs[$i] !== UPLOAD_ERR_OK) continue;
      if ($sizes[$i] > $maxSize) continue;

      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime  = finfo_file($finfo, $tmp[$i]);
      finfo_close($finfo);

      if (!in_array($mime, $allowed)) continue;

      $ext = "jpg";
      if ($mime === "image/png")  $ext = "png";
      if ($mime === "image/webp") $ext = "webp";

      $uniqueName = $newPostId . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;

      $serverPath = $uploadDir . $uniqueName; 
      $dbPath     = $dbPrefix . $uniqueName;  

      if (!move_uploaded_file($tmp[$i], $serverPath)) {
        continue;
      }

      $newMediaId = "pm_" . str_pad($mediaNo, 3, "0", STR_PAD_LEFT);
      $mediaNo++;

      $mSql = "INSERT INTO post_media
               (media_id, post_id, media_type, file_path, video_url, order_number)
               VALUES (?, ?, 'image', ?, NULL, ?)";
      $mStmt = mysqli_prepare($conn, $mSql);
      if (!$mStmt) throw new Exception("Prepare post_media insert failed: " . mysqli_error($conn));

      mysqli_stmt_bind_param($mStmt, "sssi", $newMediaId, $newPostId, $dbPath, $orderNo);
      if (!mysqli_stmt_execute($mStmt)) throw new Exception("Execute post_media insert failed: " . mysqli_stmt_error($mStmt));
      mysqli_stmt_close($mStmt);

      $orderNo++;
    }
  }

  $pSql = "UPDATE users SET eco_points = COALESCE(eco_points,0) + ? WHERE user_id = ?";
  $pStmt = mysqli_prepare($conn, $pSql);
  if (!$pStmt) throw new Exception("Prepare points failed: " . mysqli_error($conn));

  mysqli_stmt_bind_param($pStmt, "is", $earnedPoints, $user_id);
  if (!mysqli_stmt_execute($pStmt)) throw new Exception("Execute points failed: " . mysqli_stmt_error($pStmt));
  mysqli_stmt_close($pStmt);

  mysqli_commit($conn);

  $_SESSION["flash_success"] = ["type" => "upload", "points" => $earnedPoints];

  header("Location: tutorial_view.php");
  exit();

} catch (Throwable $e) {
  mysqli_rollback($conn);

  header("Location: user_tutorial_add.php");
  exit();
}
