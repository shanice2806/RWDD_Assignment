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

function redirectErr($code) {
  header("Location: user_tutorial_add.php?err=" . (int)$code);
  exit();
}

$title = trim($_POST["title"] ?? "");
$body  = trim($_POST["body"] ?? "");
$level = trim($_POST["difficulty_level"] ?? "");
$cat   = trim($_POST["content_category_id"] ?? "");

$video_url = trim($_POST["video_url"] ?? "");

if ($title === "" || $body === "" || $level === "" || $cat === "") redirectErr(1);
//defaut 15 point if there is no rules 
$earnedPoints = 15;
$ruleSql = "SELECT points_per_kg
            FROM eco_points_rules
            WHERE rule_key='uploading_content' AND is_active=1
            LIMIT 1";
$ruleRes = mysqli_query($conn, $ruleSql);
if ($ruleRes && ($r = mysqli_fetch_assoc($ruleRes))) {
  $earnedPoints = (int)($r["points_per_kg"] ?? 15);
  if ($earnedPoints < 0) $earnedPoints = 0;
}

$uploadDir = __DIR__ . "/../images/post_media/";
if (!is_dir($uploadDir)) {
  die("Upload folder not found: ../images/post_media/");
}
$dbPrefix = "images/post_media/"; 

mysqli_begin_transaction($conn);

$destFullPaths = []; 

try {
  $lastSql = "SELECT post_id FROM posts ORDER BY post_id DESC LIMIT 1 FOR UPDATE";
  $resLast = mysqli_query($conn, $lastSql);

  $newPostId = "p_001";
  if ($resLast && ($r2 = mysqli_fetch_assoc($resLast))) {
    $last = $r2["post_id"];
    $num  = (int)substr($last, 2);
    $num++;
    $newPostId = "p_" . str_pad($num, 3, "0", STR_PAD_LEFT);
  }

  $sql = "INSERT INTO posts
          (post_id, user_id, content_category_id, title, body, difficulty_level, post_status, points_awarded, post_created_at)
          VALUES (?, ?, ?, ?, ?, ?, 'Public', ?, NOW())";
  $stmt = mysqli_prepare($conn, $sql);
  if (!$stmt) throw new Exception("Prepare posts insert failed: " . mysqli_error($conn));
  mysqli_stmt_bind_param($stmt, "ssssssi", $newPostId, $user_id, $cat, $title, $body, $level, $earnedPoints);
  if (!mysqli_stmt_execute($stmt)) throw new Exception("Execute posts insert failed: " . mysqli_stmt_error($stmt));
  mysqli_stmt_close($stmt);

  $lastMediaSql = "SELECT media_id FROM post_media ORDER BY media_id DESC LIMIT 1 FOR UPDATE";
  $resMedia = mysqli_query($conn, $lastMediaSql);

  $mediaNo = 1;
  if ($resMedia && ($mr = mysqli_fetch_assoc($resMedia))) {
    $mediaNo = (int)substr($mr["media_id"], 3) + 1;
  }

  $orderNo = 1;

  if ($video_url !== "") {
    $newMediaId = "pm_" . str_pad($mediaNo, 3, "0", STR_PAD_LEFT);
    $mediaNo++;

    $mSql = "INSERT INTO post_media
             (media_id, post_id, media_type, file_path, video_url, order_number)
             VALUES (?, ?, 'video', NULL, ?, ?)";
    $mStmt = mysqli_prepare($conn, $mSql);
    if (!$mStmt) throw new Exception("Prepare post_media video insert failed: " . mysqli_error($conn));
    mysqli_stmt_bind_param($mStmt, "sssi", $newMediaId, $newPostId, $video_url, $orderNo);
    if (!mysqli_stmt_execute($mStmt)) throw new Exception("Execute post_media video insert failed: " . mysqli_stmt_error($mStmt));
    mysqli_stmt_close($mStmt);

    $orderNo++;
  }

  if (!empty($_FILES["media_files"]) && is_array($_FILES["media_files"]["name"])) {

    $allowed = ["image/jpeg", "image/png", "image/webp"];
    $maxSize = 3 * 1024 * 1024;

    $names = $_FILES["media_files"]["name"];
    $tmp   = $_FILES["media_files"]["tmp_name"];
    $errs  = $_FILES["media_files"]["error"];
    $sizes = $_FILES["media_files"]["size"];

    for ($i = 0; $i < count($names); $i++) {
      if (trim($names[$i]) === "") continue;
      if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
      if (($sizes[$i] ?? 0) <= 0 || ($sizes[$i] ?? 0) > $maxSize) continue;

      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime  = finfo_file($finfo, $tmp[$i]);
      finfo_close($finfo);

      if (!in_array($mime, $allowed)) continue;

      $ext = "jpg";
      if ($mime === "image/png")  $ext = "png";
      if ($mime === "image/webp") $ext = "webp";

      $uniqueName = $newPostId . "_" . date("Ymd_His") . "_" . bin2hex(random_bytes(3)) . "." . $ext;

      $destFull = $uploadDir . $uniqueName; 
      $dbPath   = $dbPrefix . $uniqueName; 

      if (!move_uploaded_file($tmp[$i], $destFull)) continue;

      $destFullPaths[] = $destFull;

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

  if ($earnedPoints > 0) {
    $upSql = "UPDATE users SET eco_points = COALESCE(eco_points,0) + ? WHERE user_id=? LIMIT 1";
    $up = mysqli_prepare($conn, $upSql);
    mysqli_stmt_bind_param($up, "is", $earnedPoints, $user_id);
    if (!mysqli_stmt_execute($up)) throw new Exception("Update points failed: " . mysqli_stmt_error($up));
    mysqli_stmt_close($up);

    $transaction_id = "ept_" . uniqid();
    $source_id = $newPostId;
    $desc = "Points awarded for uploading tutorial content. (post: " . $newPostId . ")";
    $created_at = date("Y-m-d H:i:s");

    $txSql = "INSERT INTO eco_points_transactions
              (transaction_id, user_id, source_id, points_change, description, created_at)
              VALUES (?, ?, ?, ?, ?, ?)";
    $tx = mysqli_prepare($conn, $txSql);
    if (!$tx) throw new Exception("Prepare transaction failed: " . mysqli_error($conn));

    mysqli_stmt_bind_param($tx, "sssiss", $transaction_id, $user_id, $source_id, $earnedPoints, $desc, $created_at);
    if (!mysqli_stmt_execute($tx)) throw new Exception("Insert transaction failed: " . mysqli_stmt_error($tx));
    mysqli_stmt_close($tx);
  }

  mysqli_commit($conn);

  $_SESSION["flash_success"] = ["type" => "upload", "points" => $earnedPoints];
  header("Location: tutorial_view.php");
  exit();

} catch (Throwable $e) {
  mysqli_rollback($conn);

  foreach ($destFullPaths as $p) {
    if (is_file($p)) @unlink($p);
  }

  die("Error: " . htmlspecialchars($e->getMessage()));
}
