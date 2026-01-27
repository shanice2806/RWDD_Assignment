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

$title = trim($_POST["title"] ?? "");
$body  = trim($_POST["body"] ?? "");
$level = trim($_POST["difficulty_level"] ?? "");
$cat   = trim($_POST["content_category_id"] ?? "");

if ($post_id === "" || $title === "" || $body === "" || $level === "" || $cat === "") {
  header("Location: tutorial_edit.php?id=" . urlencode($post_id));
  exit();
}

$uploadDir = __DIR__ . "/../images/post_media/";
$dbPrefix  = "../images/post_media/";

if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0777, true);
}

mysqli_begin_transaction($conn);

try {
  $chkSql = "SELECT post_id FROM posts WHERE post_id=? AND user_id=? LIMIT 1 FOR UPDATE";
  $chkStmt = mysqli_prepare($conn, $chkSql);
  if (!$chkStmt) throw new Exception("Prepare ownership failed: " . mysqli_error($conn));

  mysqli_stmt_bind_param($chkStmt, "ss", $post_id, $user_id);
  mysqli_stmt_execute($chkStmt);
  $chkRes = mysqli_stmt_get_result($chkStmt);
  $ownRow = mysqli_fetch_assoc($chkRes);
  mysqli_stmt_close($chkStmt);

  if (!$ownRow) {
    mysqli_rollback($conn);
    header("Location: tutorial_view.php");
    exit();
  }

  $upSql = "UPDATE posts
            SET content_category_id=?,
                title=?,
                body=?,
                difficulty_level=?
            WHERE post_id=? AND user_id=?
            LIMIT 1";

  $upStmt = mysqli_prepare($conn, $upSql);
  if (!$upStmt) throw new Exception("Prepare update failed: " . mysqli_error($conn));

  mysqli_stmt_bind_param($upStmt, "ssssss", $cat, $title, $body, $level, $post_id, $user_id);
  if (!mysqli_stmt_execute($upStmt)) throw new Exception("Execute update failed: " . mysqli_stmt_error($upStmt));
  mysqli_stmt_close($upStmt);

  $hasFiles = !empty($_FILES["media_files"]) && is_array($_FILES["media_files"]["name"]) && trim($_FILES["media_files"]["name"][0] ?? "") !== "";

  if ($hasFiles) {
    $allowed = ["image/jpeg", "image/png", "image/webp"];
    $maxSize = 3 * 1024 * 1024;

    $lastMediaSql = "SELECT media_id FROM post_media ORDER BY media_id DESC LIMIT 1 FOR UPDATE";
    $resMedia = mysqli_query($conn, $lastMediaSql);

    $mediaNo = 1;
    if ($resMedia && ($mr = mysqli_fetch_assoc($resMedia))) {
      $mediaNo = (int)substr($mr["media_id"], 3) + 1; 
    }

    $maxOrderSql = "SELECT COALESCE(MAX(order_number),0) AS max_no
                    FROM post_media
                    WHERE post_id=?
                    FOR UPDATE";
    $moStmt = mysqli_prepare($conn, $maxOrderSql);
    if (!$moStmt) throw new Exception("Prepare max order failed: " . mysqli_error($conn));

    mysqli_stmt_bind_param($moStmt, "s", $post_id);
    mysqli_stmt_execute($moStmt);
    $moRes = mysqli_stmt_get_result($moStmt);
    $moRow = mysqli_fetch_assoc($moRes);
    mysqli_stmt_close($moStmt);

    $orderNo = (int)($moRow["max_no"] ?? 0) + 1;

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

      $uniqueName = $post_id . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;

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
      if (!$mStmt) throw new Exception("Prepare insert media failed: " . mysqli_error($conn));

      mysqli_stmt_bind_param($mStmt, "sssi", $newMediaId, $post_id, $dbPath, $orderNo);
      if (!mysqli_stmt_execute($mStmt)) throw new Exception("Execute insert media failed: " . mysqli_stmt_error($mStmt));
      mysqli_stmt_close($mStmt);

      $orderNo++;
    }
  }

  mysqli_commit($conn);

  header("Location: tutorial_edit.php?id=" . urlencode($post_id));
  exit();

} catch (Throwable $e) {
  mysqli_rollback($conn);

  header("Location: tutorial_edit.php?id=" . urlencode($post_id));
  exit();
}
