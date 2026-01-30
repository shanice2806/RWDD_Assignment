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

function go($url){
  header("Location: " . $url);
  exit();
}

$post_id = trim($_POST["post_id"] ?? "");
$from    = trim($_POST["from"] ?? "view");

$title = trim($_POST["title"] ?? "");
$body  = trim($_POST["body"] ?? "");
$level = trim($_POST["difficulty_level"] ?? "");
$cat   = trim($_POST["content_category_id"] ?? "");

$video_url = trim($_POST["video_url"] ?? "");

if ($post_id === "" || $title === "" || $body === "" || $level === "" || $cat === "") {
  go("tutorial_edit.php?id=" . urlencode($post_id) . "&from=" . urlencode($from) . "&err=1");
}

$uploadDir = __DIR__ . "/../images/post_media/";
if (!is_dir($uploadDir)) {
  die("Upload folder not found: ../images/post_media/");
}
$dbPrefix = "images/post_media/"; 

mysqli_begin_transaction($conn);

$uploadedFull = []; 

try {
  $chkSql = "SELECT post_id FROM posts WHERE post_id=? AND user_id=? LIMIT 1 FOR UPDATE";
  $chk = mysqli_prepare($conn, $chkSql);
  mysqli_stmt_bind_param($chk, "ss", $post_id, $user_id);
  mysqli_stmt_execute($chk);
  $chkRes = mysqli_stmt_get_result($chk);
  $own = mysqli_fetch_assoc($chkRes);
  mysqli_stmt_close($chk);

  if (!$own) {
    mysqli_rollback($conn);
    go("tutorial_view.php");
  }

  $upSql = "UPDATE posts
            SET content_category_id=?,
                title=?,
                body=?,
                difficulty_level=?
            WHERE post_id=? AND user_id=?
            LIMIT 1";
  $up = mysqli_prepare($conn, $upSql);
  mysqli_stmt_bind_param($up, "ssssss", $cat, $title, $body, $level, $post_id, $user_id);
  if (!mysqli_stmt_execute($up)) throw new Exception("Update post failed: " . mysqli_stmt_error($up));
  mysqli_stmt_close($up);

  $mediaNo = 1;
  $lastMediaSql = "SELECT media_id FROM post_media ORDER BY media_id DESC LIMIT 1 FOR UPDATE";
  $resMedia = mysqli_query($conn, $lastMediaSql);
  if ($resMedia && ($mr = mysqli_fetch_assoc($resMedia))) {
    $mediaNo = (int)substr($mr["media_id"], 3) + 1;
  }

  $maxOrder = 0;
  $maxOrderSql = "SELECT COALESCE(MAX(order_number),0) AS mx
                  FROM post_media
                  WHERE post_id=?
                  FOR UPDATE";
  $mo = mysqli_prepare($conn, $maxOrderSql);
  mysqli_stmt_bind_param($mo, "s", $post_id);
  mysqli_stmt_execute($mo);
  $moRes = mysqli_stmt_get_result($mo);
  if ($row = mysqli_fetch_assoc($moRes)) $maxOrder = (int)($row["mx"] ?? 0);
  mysqli_stmt_close($mo);

  $orderNo = $maxOrder + 1;

  if ($video_url !== "") {

    if (!filter_var($video_url, FILTER_VALIDATE_URL)) {
      throw new Exception("Invalid video URL.");
    }

    $dupSql = "SELECT media_id FROM post_media
               WHERE post_id=? AND media_type='video' AND video_url=?
               LIMIT 1";
    $dup = mysqli_prepare($conn, $dupSql);
    mysqli_stmt_bind_param($dup, "ss", $post_id, $video_url);
    mysqli_stmt_execute($dup);
    $dupRes = mysqli_stmt_get_result($dup);
    $dupRow = mysqli_fetch_assoc($dupRes);
    mysqli_stmt_close($dup);

    if (!$dupRow) {
      $newMediaId = "pm_" . str_pad($mediaNo, 3, "0", STR_PAD_LEFT);
      $mediaNo++;

      $vSql = "INSERT INTO post_media
               (media_id, post_id, media_type, file_path, video_url, order_number)
               VALUES (?, ?, 'video', NULL, ?, ?)";
      $v = mysqli_prepare($conn, $vSql);
      mysqli_stmt_bind_param($v, "sssi", $newMediaId, $post_id, $video_url, $orderNo);
      if (!mysqli_stmt_execute($v)) throw new Exception("Insert video failed: " . mysqli_stmt_error($v));
      mysqli_stmt_close($v);

      $orderNo++;
    }
  }

  $hasFiles = !empty($_FILES["media_files"]) && is_array($_FILES["media_files"]["name"])
              && trim($_FILES["media_files"]["name"][0] ?? "") !== "";

  if ($hasFiles) {
    $allowed = ["image/jpeg", "image/png", "image/webp"];
    $maxSize = 3 * 1024 * 1024;

    $names = $_FILES["media_files"]["name"];
    $tmp   = $_FILES["media_files"]["tmp_name"];
    $errs  = $_FILES["media_files"]["error"];
    $sizes = $_FILES["media_files"]["size"];

    for ($i = 0; $i < count($names); $i++) {
      if (trim($names[$i]) === "") continue;
      if ($errs[$i] !== UPLOAD_ERR_OK) continue;
      if ($sizes[$i] <= 0 || $sizes[$i] > $maxSize) continue;

      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime  = finfo_file($finfo, $tmp[$i]);
      finfo_close($finfo);

      if (!in_array($mime, $allowed)) continue;

      $ext = "jpg";
      if ($mime === "image/png")  $ext = "png";
      if ($mime === "image/webp") $ext = "webp";

      $uniqueName = $post_id . "_" . date("Ymd_His") . "_" . bin2hex(random_bytes(3)) . "." . $ext;

      $destFull = $uploadDir . $uniqueName;
      $dbPath   = $dbPrefix . $uniqueName; // ✅ images/...

      if (!move_uploaded_file($tmp[$i], $destFull)) continue;
      $uploadedFull[] = $destFull;

      $newMediaId = "pm_" . str_pad($mediaNo, 3, "0", STR_PAD_LEFT);
      $mediaNo++;

      $mSql = "INSERT INTO post_media
               (media_id, post_id, media_type, file_path, video_url, order_number)
               VALUES (?, ?, 'image', ?, NULL, ?)";
      $m = mysqli_prepare($conn, $mSql);
      mysqli_stmt_bind_param($m, "sssi", $newMediaId, $post_id, $dbPath, $orderNo);
      if (!mysqli_stmt_execute($m)) throw new Exception("Insert image failed: " . mysqli_stmt_error($m));
      mysqli_stmt_close($m);

      $orderNo++;
    }
  }

  mysqli_commit($conn);

  go("tutorial_edit.php?id=" . urlencode($post_id) . "&from=" . urlencode($from) . "&saved=1");

} catch (Throwable $e) {
  mysqli_rollback($conn);

  foreach ($uploadedFull as $f) {
    if (is_file($f)) @unlink($f);
  }

  die("Update failed: " . htmlspecialchars($e->getMessage()));
}
