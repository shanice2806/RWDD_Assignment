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

$userSql = "SELECT user_id, name, role, eco_points, profile_image
            FROM users WHERE user_id = ? LIMIT 1";
$userStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($userStmt, "s", $user_id);
mysqli_stmt_execute($userStmt);
$userRes = mysqli_stmt_get_result($userStmt);
$user = mysqli_fetch_assoc($userRes);
mysqli_stmt_close($userStmt);

$log_id = trim($_GET["log_id"] ?? "");
if ($log_id === "") {
  header("Location: user_recycle.php");
  exit();
}

/* =========================
   GET EXISTING LOG (include photo_path)
========================= */
$get = mysqli_prepare($conn, "
  SELECT log_id, material_id, weight_kg, location, photo_path, status
  FROM recycling_log
  WHERE log_id=? AND user_id=?
  LIMIT 1
");
mysqli_stmt_bind_param($get, "ss", $log_id, $user_id);
mysqli_stmt_execute($get);
$res = mysqli_stmt_get_result($get);
$log = mysqli_fetch_assoc($res);
mysqli_stmt_close($get);

if (!$log) {
  header("Location: user_recycle.php");
  exit();
}

/* =========================
   (Optional) Prevent editing after approved
   If you want allow edit always, comment this block.
========================= */
$statusNow = strtoupper(trim($log["status"] ?? ""));
// if ($statusNow === "APPROVED") {
//   header("Location: user_recycle.php?edit_blocked=1");
//   exit();
// }

/* =========================
   SAVE EDIT
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_log"])) {

  $material_id = trim($_POST["material_id"] ?? "");
  $weight_kg   = trim($_POST["weight_kg"] ?? "");
  $location    = trim($_POST["location"] ?? "");

  if ($material_id === "" || $weight_kg === "" || $location === "") {
    header("Location: recycle_edit.php?log_id=" . urlencode($log_id) . "&err=1");
    exit();
  }

  // Default keep old photo
  $photo_path = $log["photo_path"] ?? null;

  /* =========================
     OPTIONAL: New photo upload
  ========================= */
  $hasNewPhoto = (isset($_FILES["photo"]) && $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE);

  if ($hasNewPhoto) {

    if ($_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {
      header("Location: recycle_edit.php?log_id=" . urlencode($log_id) . "&err=2");
      exit();
    }

    $allowedExt = ["jpg", "jpeg", "png"];
    $maxSize    = 5 * 1024 * 1024; // 5MB

    $tmpPath  = $_FILES["photo"]["tmp_name"];
    $origName = $_FILES["photo"]["name"];
    $fileSize = (int)($_FILES["photo"]["size"] ?? 0);

    if ($fileSize <= 0 || $fileSize > $maxSize) {
      header("Location: recycle_edit.php?log_id=" . urlencode($log_id) . "&err=3");
      exit();
    }

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
      header("Location: recycle_edit.php?log_id=" . urlencode($log_id) . "&err=4");
      exit();
    }

    $imgInfo = @getimagesize($tmpPath);
    if ($imgInfo === false) {
      header("Location: recycle_edit.php?log_id=" . urlencode($log_id) . "&err=5");
      exit();
    }

    // Ensure folder exists
    $uploadDir = __DIR__ . "/../uploads/logs/";
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0777, true);
    }

    // New filename (log_id + timestamp)
    $newFileName = $log_id . "_" . date("Ymd_His") . "." . $ext;
    $destFullPath = $uploadDir . $newFileName;

    $newPhotoPath = "uploads/logs/" . $newFileName;

    if (!move_uploaded_file($tmpPath, $destFullPath)) {
      header("Location: recycle_edit.php?log_id=" . urlencode($log_id) . "&err=6");
      exit();
    }

    // (Optional) delete old file if exists and is in uploads/logs/
    $old = trim($log["photo_path"] ?? "");
    if ($old !== "" && str_starts_with($old, "uploads/logs/")) {
      $oldFull = __DIR__ . "/../" . $old;
      if (is_file($oldFull)) {
        @unlink($oldFull);
      }
    }

    $photo_path = $newPhotoPath;
  }

  /* =========================
     UPDATE (include photo_path too)
  ========================= */
  $upd = mysqli_prepare($conn, "
    UPDATE recycling_log
    SET material_id=?, weight_kg=?, location=?, photo_path=?
    WHERE log_id=? AND user_id=?
    LIMIT 1
  ");
  mysqli_stmt_bind_param($upd, "ssssss", $material_id, $weight_kg, $location, $photo_path, $log_id, $user_id);
  mysqli_stmt_execute($upd);
  mysqli_stmt_close($upd);

  header("Location: user_recycle.php?updated=1&edit=1");
  exit();
}

/* For form defaults */
$matVal = $log["material_id"] ?? "";
$wVal   = $log["weight_kg"] ?? "";
$locVal = $log["location"] ?? "";
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
    .btn{margin-top:14px;width:100%;padding:10px 0px;border:0;border-radius:10px;background:#111827;color:#fff;font-weight:800;cursor:pointer}
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
          <span class="user-top-name"><span> Welcome!  <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?> </span>
        </div>
      </div>

      <div class="user-top-center">
        <h1 style="color: white;">Recycle</h1>
      </div>

      <div class="user-top-right">
        <span class="user-top-points">🪙 <?php echo (int)$user["eco_points"]; ?> points</span>
        <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
        <a class="user-top-btn" href="friends_add.php" title="Add Friend">👥</a>
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
      ?>
      <div class="msg msg-error"><?php echo $msg; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

      <label for="material_id">Materials</label>
      <select id="material_id" name="material_id" required>
        <option value="mat_001" <?php if($matVal==="mat_001") echo "selected"; ?>>mat_001 (Plastic)</option>
        <option value="mat_002" <?php if($matVal==="mat_002") echo "selected"; ?>>mat_002 (Tin)</option>
        <option value="mat_003" <?php if($matVal==="mat_003") echo "selected"; ?>>mat_003 (Paper)</option>
        <option value="mat_004" <?php if($matVal==="mat_004") echo "selected"; ?>>mat_004 (Glass)</option>
        <option value="mat_005" <?php if($matVal==="mat_005") echo "selected"; ?>>mat_005 (Others)</option>
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
    <footer>
    © 2026 ReLife Hub
  </footer>

  <script src="../user/user.js"></script>
</body>
</html>
