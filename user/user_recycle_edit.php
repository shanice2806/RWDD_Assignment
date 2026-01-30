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

function go($url) {
  header("Location: " . $url);
  exit();
}
function goErr($log_id, $errCode) {
  go("user_recycle_edit.php?log_id=" . urlencode($log_id) . "&err=" . (int)$errCode);
}


$AUTO_APPROVE_MAX_KG = 5.0;

$userSql = "SELECT name, eco_points, profile_image FROM users WHERE user_id = ? LIMIT 1";
$uStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($uStmt, "s", $user_id);
mysqli_stmt_execute($uStmt);
$uRes  = mysqli_stmt_get_result($uStmt);
$user  = mysqli_fetch_assoc($uRes) ?: ["name" => "User", "eco_points" => 0, "profile_image" => ""];
mysqli_stmt_close($uStmt);

$profileImg = "../images/profile.png";
if (!empty($user["profile_image"])) {
  $try = "../" . ltrim($user["profile_image"], "/");
  $disk = __DIR__ . "/../" . ltrim($user["profile_image"], "/");
  if (file_exists($disk)) $profileImg = $try;
}

$materials = [];
$matSql = "SELECT material_id, materials_name FROM materials WHERE is_active=1 ORDER BY materials_name ASC";
$matRes = mysqli_query($conn, $matSql);
if ($matRes) {
  while ($row = mysqli_fetch_assoc($matRes)) $materials[] = $row;
}

$log_id = trim($_GET["log_id"] ?? "");
if ($log_id === "") go("user_recycle.php");

$getSql = "SELECT log_id, material_id, rule_id, weight_kg, location, photo_path, status, points_awarded
           FROM recycling_log
           WHERE log_id=? AND user_id=?
           LIMIT 1";
$getStmt = mysqli_prepare($conn, $getSql);
mysqli_stmt_bind_param($getStmt, "ss", $log_id, $user_id);
mysqli_stmt_execute($getStmt);
$getRes = mysqli_stmt_get_result($getStmt);
$log = mysqli_fetch_assoc($getRes);
mysqli_stmt_close($getStmt);

if (!$log) go("user_recycle.php");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_photo"])) {

  $current = trim($log["photo_path"] ?? "");

  if ($current !== "" && str_starts_with($current, "images/recycling_proof/")) {
    $full = __DIR__ . "/../" . $current;
    if (is_file($full)) {
      @unlink($full);
    }
  }

  $updPhotoSql = "UPDATE recycling_log
                  SET photo_path=NULL
                  WHERE log_id=? AND user_id=?
                  LIMIT 1";
  $pStmt = mysqli_prepare($conn, $updPhotoSql);
  mysqli_stmt_bind_param($pStmt, "ss", $log_id, $user_id);
  mysqli_stmt_execute($pStmt);
  mysqli_stmt_close($pStmt);

  go("user_recycle_edit.php?log_id=" . urlencode($log_id) . "&photo_deleted=1");
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_log"])) {

  $material_id = trim($_POST["material_id"] ?? "");
  $weight_kg   = trim($_POST["weight_kg"] ?? "");
  $location    = trim($_POST["location"] ?? "");

  if ($material_id === "" || $weight_kg === "" || $location === "") {
    goErr($log_id, 1);
  }

  if (!is_numeric($weight_kg)) goErr($log_id, 7);
  $weight_kg = (float)$weight_kg;
  if ($weight_kg <= 0) goErr($log_id, 8);

  $oldStatus = strtoupper(trim($log["status"] ?? "PENDING"));
  $oldPoints = (int)($log["points_awarded"] ?? 0);

  $rule_id = "";
  $points_per_kg = 0;

  $ruleSql = "SELECT rule_id, points_per_kg
              FROM eco_points_rules
              WHERE is_active = 1
                AND rule_type = 'RECYCLE'
                AND material_id = ?
              LIMIT 1";
  $rStmt = mysqli_prepare($conn, $ruleSql);
  mysqli_stmt_bind_param($rStmt, "s", $material_id);
  mysqli_stmt_execute($rStmt);
  $rRes = mysqli_stmt_get_result($rStmt);
  $ruleRow = mysqli_fetch_assoc($rRes);
  mysqli_stmt_close($rStmt);

  if (!$ruleRow) goErr($log_id, 9);

  $rule_id = $ruleRow["rule_id"];
  $points_per_kg = (float)$ruleRow["points_per_kg"];

  $material_name = $material_id;
  $mnSql = "SELECT materials_name FROM materials WHERE material_id=? LIMIT 1";
  $mnStmt = mysqli_prepare($conn, $mnSql);
  mysqli_stmt_bind_param($mnStmt, "s", $material_id);
  mysqli_stmt_execute($mnStmt);
  $mnRes = mysqli_stmt_get_result($mnStmt);
  if ($mnRow = mysqli_fetch_assoc($mnRes)) $material_name = $mnRow["materials_name"];
  mysqli_stmt_close($mnStmt);

  $status = "VALID";
  $is_flagged = 0;
  $flag_reason = NULL;

  if ($weight_kg > $AUTO_APPROVE_MAX_KG) {
    $status = "PENDING";
    $is_flagged = 1;
    $flag_reason = "Weight exceeds " . $AUTO_APPROVE_MAX_KG . "kg. Pending admin approval.";
  }

  $points_awarded = 0;
  if ($status === "VALID") {
    $points_awarded = (int) round($weight_kg * $points_per_kg);
    if ($points_awarded < 0) $points_awarded = 0;
  }

  $photo_path = $log["photo_path"] ?? "";
  $hasNewPhoto = isset($_FILES["photo"]) && $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE;

  if ($hasNewPhoto) {
    if ($_FILES["photo"]["error"] !== UPLOAD_ERR_OK) goErr($log_id, 2);

    $allowedExt = ["jpg", "jpeg", "png"];
    $maxSize    = 5 * 1024 * 1024;

    $tmpPath  = $_FILES["photo"]["tmp_name"];
    $origName = $_FILES["photo"]["name"];
    $fileSize = (int)($_FILES["photo"]["size"] ?? 0);

    if ($fileSize <= 0 || $fileSize > $maxSize) goErr($log_id, 3);

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) goErr($log_id, 4);

    if (@getimagesize($tmpPath) === false) goErr($log_id, 5);

    $uploadDir = __DIR__ . "/../images/recycling_proof/";
    if (!is_dir($uploadDir)) {
      die("Upload folder not found: ../images/recycling_proof/");
    }

    $newFileName  = $log_id . "_" . date("Ymd_His") . "." . $ext;
    $destFullPath = $uploadDir . $newFileName;

    $newPhotoPath = "images/recycling_proof/" . $newFileName;

    if (!move_uploaded_file($tmpPath, $destFullPath)) goErr($log_id, 6);

    $old = trim($log["photo_path"] ?? "");
    if ($old !== "" && str_starts_with($old, "images/recycling_proof/")) {
      $oldFull = __DIR__ . "/../" . $old;
      if (is_file($oldFull)) @unlink($oldFull);
    }

    $photo_path = $newPhotoPath;
  }

  mysqli_begin_transaction($conn);

  try {
    $updSql = "UPDATE recycling_log
               SET material_id=?,
                   rule_id=?,
                   weight_kg=?,
                   location=?,
                   photo_path=?,
                   status=?,
                   points_awarded=?,
                   is_flagged=?,
                   flag_reason=?
               WHERE log_id=? AND user_id=?
               LIMIT 1";
    $updStmt = mysqli_prepare($conn, $updSql);
    if (!$updStmt) throw new Exception("Prepare update failed: " . mysqli_error($conn));
mysqli_stmt_bind_param(
  $updStmt,
  "ssdsssiisss",
  $material_id,
  $rule_id,
  $weight_kg,
  $location,
  $photo_path,
  $status,
  $points_awarded,
  $is_flagged,
  $flag_reason,
  $log_id,
  $user_id
);

    if (!mysqli_stmt_execute($updStmt)) throw new Exception("Update failed: " . mysqli_stmt_error($updStmt));
    mysqli_stmt_close($updStmt);

    $newStatus = strtoupper($status);

    $diff = 0;
    if ($oldStatus === "VALID") {
      if ($newStatus === "VALID") $diff = $points_awarded - $oldPoints;
      else $diff = 0 - $oldPoints; 
    } else {
      if ($newStatus === "VALID") $diff = $points_awarded;
      else $diff = 0;
    }

    if ($diff != 0) {
      $upSql = "UPDATE users SET eco_points = eco_points + ? WHERE user_id=? LIMIT 1";
      $up = mysqli_prepare($conn, $upSql);
      mysqli_stmt_bind_param($up, "is", $diff, $user_id);
      if (!mysqli_stmt_execute($up)) throw new Exception("Update user points failed: " . mysqli_stmt_error($up));
      mysqli_stmt_close($up);

      $transaction_id = "ept_" . uniqid();
      $source_id = $log_id;

      $desc = "Points adjustment for recycle log " . $log_id . " after edit. Material: " .
              $material_name . ", Weight: " . number_format($weight_kg, 2) . " kg, Status: " . $newStatus;

      $created_at = date("Y-m-d H:i:s");

      $txSql = "INSERT INTO eco_points_transactions
                (transaction_id, user_id, source_id, points_change, description, created_at)
                VALUES (?, ?, ?, ?, ?, ?)";
      $tx = mysqli_prepare($conn, $txSql);
      mysqli_stmt_bind_param($tx, "sssiss", $transaction_id, $user_id, $source_id, $diff, $desc, $created_at);
      if (!mysqli_stmt_execute($tx)) throw new Exception("Insert transaction failed: " . mysqli_stmt_error($tx));
      mysqli_stmt_close($tx);
    }

    mysqli_commit($conn);
    go("user_recycle.php?updated=1&edit=1");

  } catch (Exception $e) {
    mysqli_rollback($conn);
    die("Error: " . htmlspecialchars($e->getMessage()));
  }
}

$matVal   = $log["material_id"] ?? "";
$wVal     = $log["weight_kg"] ?? "";
$locVal   = $log["location"] ?? "";
$photoVal = $log["photo_path"] ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Recycle Log</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">

  <style>
    .card{max-width:520px;margin:30px auto;background:#fff;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,.08);padding:18px}
    label{display:block;margin:12px 0 6px;font-weight:700}
    input, select{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;box-sizing:border-box}
    .btn{margin-top:14px;width:100%;padding:10px 0px;border:0;border-radius:10px;;font-weight:800}
    .btn-outline{background:#fff;border:1px solid #ddd;color:#111827}
    .msg{padding:10px 12px;border-radius:10px;margin:10px 0}
    .msg-error{background:#ffe8e8;color:#b00020}
    .hint{color:#6b7280;font-size:13px}
    .photo-preview{margin-top:8px}
    .photo-preview img{width:100%;max-height:220px;object-fit:cover;border-radius:10px;border:1px solid #eee}
  </style>
</head>

<body class="sidebar-collapsed">

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="user-layout">

  <?php include __DIR__ . "/user_sidebar.php"; ?>

  <div class="user-content">

    <div class="user-top-bar">

      <div class="user-top-left">
        <button class="sidebar-toggle" id="userSidebarToggle" type="button">☰</button>

        <div class="user-top-user">
          <span class="user-top-name"> Welcome! <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?> </span>
        </div>
      </div>

      <div class="user-top-center">
        <h1 style="color: white;">Recycle</h1>
      </div>

      <div class="user-top-right">
        <span class="user-top-points">🪙 <?php echo (int)$user["eco_points"]; ?> points</span>
        <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
        <a class="user-top-btn" href="add_friends.php" title="Add Friend">👥</a>
        <a href="../login/logout.php" class="user-top-btn logout" title="Logout">❌</a>
      </div>

    </div>

    <div class="card">
      <h2 style="margin:0 0 8px;">Edit Submitted Recycle Item</h2>

      <?php if (isset($_GET["err"])): ?>
        <?php
          $err = (int)$_GET["err"];
          $msg = "❌ Something went wrong.";
          if ($err === 1) $msg = "❌ Please fill in Material, Weight and Location.";
          if ($err === 2) $msg = "❌ Photo upload error.";
          if ($err === 3) $msg = "❌ Photo must be less than 5MB.";
          if ($err === 4) $msg = "❌ Only JPG / JPEG / PNG allowed.";
          if ($err === 5) $msg = "❌ File is not a valid image.";
          if ($err === 6) $msg = "❌ Upload failed, please try again.";
          if ($err === 7) $msg = "❌ Weight must be a number.";
          if ($err === 8) $msg = "❌ Weight must be more than 0.";
          if ($err === 9) $msg = "❌ No points rule found for this material. (Admin need set eco_points_rules)";
        ?>
        <div class="msg msg-error"><?php echo $msg; ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">

        <label for="material_id">Materials</label>
        <select id="material_id" name="material_id" required>
          <option value="">-- Select Materials Type --</option>
          <?php foreach ($materials as $m): ?>
            <option value="<?php echo htmlspecialchars($m["material_id"]); ?>"
              <?php if ($matVal === $m["material_id"]) echo "selected"; ?>>
              <?php echo htmlspecialchars($m["material_id"] . " (" . $m["materials_name"] . ")"); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="weight_kg">Weight (kg)</label>
        <input id="weight_kg" name="weight_kg" type="number" step="0.01"
               value="<?php echo htmlspecialchars($wVal); ?>" required>

        <label for="location">Location (Recycle Point)</label>
        <select id="location" name="location" required>
          <option value="">-- Select Location --</option>
          <option value="APU Bin 1" <?php if($locVal==="APU Bin 1") echo "selected"; ?>>APU Bin 1</option>
          <option value="APU Bin 2" <?php if($locVal==="APU Bin 2") echo "selected"; ?>>APU Bin 2</option>
          <option value="APU Bin 3" <?php if($locVal==="APU Bin 3") echo "selected"; ?>>APU Bin 3</option>
          <option value="APU Bin 4" <?php if($locVal==="APU Bin 4") echo "selected"; ?>>APU Bin 4</option>
          <option value="Library Bin" <?php if($locVal==="Library Bin") echo "selected"; ?>>Library Bin</option>
          <option value="Cafeteria Bin" <?php if($locVal==="Cafeteria Bin") echo "selected"; ?>>Cafeteria Bin</option>
          <option value="Main Lobby" <?php if($locVal==="Main Lobby") echo "selected"; ?>>Main Lobby</option>
          <option value="Innovation Hall" <?php if($locVal==="Innovation Hall") echo "selected"; ?>>Innovation Hall</option>
          <option value="Green Zone" <?php if($locVal==="Green Zone") echo "selected"; ?>>Green Zone</option>
        </select>

        <label>Current Photo</label>
        <?php if (trim($photoVal) !== ""): ?>
          <div class="photo-preview">
            <img src="<?php echo "../" . htmlspecialchars($photoVal); ?>" alt="Recycle proof photo">
          </div>
          <?php if (trim($photoVal) !== ""): ?>
            <form method="POST" style="margin-top:10px;" onsubmit="return confirm('Delete this photo?');">
              <button class="btn btn-outline" type="submit" name="delete_photo" value="1">Delete Photo</button>
            </form>
          <?php endif; ?>

        <?php else: ?>
          <p class="hint">No photo uploaded.</p>
        <?php endif; ?>

        <label for="photo">Change Photo (Optional)</label>
        <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png">
        <div class="hint">Leave empty if you don't want to change photo.</div>

        <button class="btn" type="submit" name="save_log" value="1">Done</button>

        <a class="btn btn-outline" href="user_recycle.php?edit=1"
           style="display:block;text-align:center;margin-top:10px;text-decoration:none;">
          Back
        </a>
      </form>
    </div>
  </div>
</div>
    <footer>
      © 2026 ReLife Hub
    </footer>
<script src="../user/user.js"></script>
</body>
</html>
