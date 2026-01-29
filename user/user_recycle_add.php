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
  header("Location: user_recycle_add.php?err=" . $code);
  exit();
}

$AUTO_APPROVE_MAX_KG = 5.0; 

$userSql = "SELECT name, eco_points, profile_image FROM users WHERE user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res) ?: ["name"=>"User","eco_points"=>0,"profile_image"=>""];
mysqli_stmt_close($stmt);

$profileImg = "../images/profile.png";
if (!empty($user["profile_image"])) {
  $try  = "../" . ltrim($user["profile_image"], "/");
  $disk = __DIR__ . "/../" . ltrim($user["profile_image"], "/");
  if (file_exists($disk)) $profileImg = $try;
}

$materials = [];
$matSql = "SELECT material_id, materials_name
           FROM materials
           WHERE is_active = 1
           ORDER BY materials_name ASC";
$matRes = mysqli_query($conn, $matSql);
if ($matRes) {
  while ($row = mysqli_fetch_assoc($matRes)) $materials[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $material_id = trim($_POST["material_id"] ?? "");
  $weight_kg   = trim($_POST["weight_kg"] ?? "");
  $location    = trim($_POST["location"] ?? "");

  if ($material_id === "" || $weight_kg === "" || $location === "") {
    redirectErr(1);
  }

  if (!is_numeric($weight_kg)) redirectErr(7);
  $weight_kg = (float)$weight_kg;
  if ($weight_kg <= 0) redirectErr(8);

  if (!isset($_FILES["photo"]) || $_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {
    redirectErr(2);
  }

  $allowedExt = ["jpg", "jpeg", "png"];
  $maxSize    = 5 * 1024 * 1024;

  $tmpPath  = $_FILES["photo"]["tmp_name"];
  $origName = $_FILES["photo"]["name"];
  $fileSize = (int)($_FILES["photo"]["size"] ?? 0);

  if ($fileSize <= 0 || $fileSize > $maxSize) redirectErr(3);

  $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExt)) redirectErr(4);

  if (@getimagesize($tmpPath) === false) redirectErr(5);


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

  if (!$ruleRow) {
    redirectErr(9);
  }

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

  $uploadDir = __DIR__ . "/../images/recycling_proof/";
if (!is_dir($uploadDir)) {
  die("Upload folder not found: ../images/recycling_proof/");
}
  $log_id = "rl_" . uniqid();

  $newFileName  = $log_id . "." . $ext;
  $destFullPath = $uploadDir . $newFileName;

  $photo_path = "imagess/recycling_proof/" . $newFileName;

  if (!move_uploaded_file($tmpPath, $destFullPath)) redirectErr(6);

  $event_id     = NULL;
  $submitted_at = date("Y-m-d H:i:s");

  mysqli_begin_transaction($conn);

  try {
    $insSql = "
      INSERT INTO recycling_log
      (log_id, user_id, material_id, rule_id, event_id, weight_kg, location, photo_path,
       submitted_at, status, points_awarded, is_flagged, flag_reason)
      VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $ins = mysqli_prepare($conn, $insSql);
    if (!$ins) throw new Exception("Prepare failed: " . mysqli_error($conn));

    mysqli_stmt_bind_param(
      $ins,
      "sssssdssssiis",
      $log_id,
      $user_id,
      $material_id,
      $rule_id,
      $event_id,
      $weight_kg,
      $location,
      $photo_path,
      $submitted_at,
      $status,
      $points_awarded,
      $is_flagged,
      $flag_reason
    );

    if (!mysqli_stmt_execute($ins)) throw new Exception("Insert log failed: " . mysqli_stmt_error($ins));
    mysqli_stmt_close($ins);

    if ($status === "VALID" && $points_awarded > 0) {

      $upSql = "UPDATE users SET eco_points = eco_points + ? WHERE user_id = ? LIMIT 1";
      $up = mysqli_prepare($conn, $upSql);
      mysqli_stmt_bind_param($up, "is", $points_awarded, $user_id);
      if (!mysqli_stmt_execute($up)) throw new Exception("Update points failed: " . mysqli_stmt_error($up));
      mysqli_stmt_close($up);

      $transaction_id = "ept_" . uniqid();
      $source_id = $log_id;
      $desc = "Points awarded for recycling " . $material_name . " (" . number_format($weight_kg, 2) . " kg).";

      $txSql = "
        INSERT INTO eco_points_transactions
        (transaction_id, user_id, source_id, points_change, description, created_at)
        VALUES (?, ?, ?, ?, ?, ?)
      ";
      $tx = mysqli_prepare($conn, $txSql);
      mysqli_stmt_bind_param($tx, "sssiss", $transaction_id, $user_id, $source_id, $points_awarded, $desc, $submitted_at);
      if (!mysqli_stmt_execute($tx)) throw new Exception("Insert transaction failed: " . mysqli_stmt_error($tx));
      mysqli_stmt_close($tx);
    }

    mysqli_commit($conn);
    header("Location: user_recycle.php?submitted=1");
    exit();

  } catch (Exception $e) {
    mysqli_rollback($conn);
    @unlink($destFullPath);
    die("Error: " . htmlspecialchars($e->getMessage()));
  }
}

$matPrev = trim($_POST["material_id"] ?? "");
$wPrev   = trim($_POST["weight_kg"] ?? "");
$locPrev = trim($_POST["location"] ?? "");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recycle log details</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">

  <style>
    .card{
      max-width:520px;
      margin:200px auto;
      background:#fff;
      border-radius:12px;
      box-shadow:0 6px 18px rgba(0,0,0,.08);
      padding:18px
    }
    label{display:block;margin:12px 0 6px;font-weight:700}
    input, select{
      width:100%;
      padding:10px 12px;
      border:1px solid #ddd;
      border-radius:10px;
      box-sizing:border-box
    }
    .page-btn{
      margin-top:14px;
      width:100%;
      padding:10px 12px;
      border-radius:10px;
      font-weight:800;
    }
    .btn-outline{
      background:#fff;
      border:1px solid #ddd;
      padding:8px 0px;
      color:#111827
    }
    .msg{
      padding:10px 12px;
      border-radius:10px;
      margin:10px 0
    }
    .msg-error{background:#ffe8e8;color:#b00020}

    .confirm-backdrop{
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.45);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 18px;
      z-index: 999999;
    }
    .confirm-modal{
      width: 100%;
      max-width: 420px;
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 10px 30px rgba(0,0,0,.2);
      overflow: hidden;
    }
    .modal-body{padding:18px;text-align:center}
    .modal-actions{
      display:flex;
      gap:10px;
      justify-content:center;
      padding:0 18px 18px
    }
    .half{width:120px}
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
        <span class="user-top-points">
          🪙 <?php echo (int)$user["eco_points"]; ?> points
        </span>
        <a href="user_dashboard.php" class="user-top-btn">🏠</a>
        <a href="friends_add.php" class="user-top-btn">👥</a>
        <a href="../login/logout.php" class="user-top-btn logout">❌</a>
      </div>

    </div>

    <div class="card">
      <h2 style="margin:0 0 8px;">Recycle log details</h2>

      <?php if (isset($_GET["err"])): ?>
        <?php
          $err = (int)$_GET["err"];
          $msg = "❌ Something went wrong.";
          if ($err === 1) $msg = "❌ Please fill in Material, Weight and Location.";
          if ($err === 2) $msg = "❌ Please upload a photo.";
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

      <form id="recycleForm" method="POST" enctype="multipart/form-data">

        <label>TP number</label>
        <input type="text" value="<?php echo htmlspecialchars($user_id); ?>" readonly>

        <label for="weight_kg">Weight (kg)</label>
        <input id="weight_kg" name="weight_kg" type="number" step="0.01"
               placeholder="e.g. 1.20"
               value="<?php echo htmlspecialchars($wPrev); ?>" required>

        <label for="material_id">Materials</label>
        <select id="material_id" name="material_id" required>
          <option value="">-- Select Materials Type --</option>
          <?php foreach ($materials as $m): ?>
            <option value="<?php echo htmlspecialchars($m["material_id"]); ?>"
              <?php if ($matPrev === $m["material_id"]) echo "selected"; ?>>
              <?php echo htmlspecialchars($m["material_id"] . " (" . $m["materials_name"] . ")"); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="location">Location (Recycle Point)</label>
        <select id="location" name="location" required>
          <option value="">-- Select Location --</option>
          <option value="APU Bin 1" <?php if($locPrev==="APU Bin 1") echo "selected"; ?>>APU Bin 1</option>
          <option value="APU Bin 2" <?php if($locPrev==="APU Bin 2") echo "selected"; ?>>APU Bin 2</option>
          <option value="APU Bin 3" <?php if($locPrev==="APU Bin 3") echo "selected"; ?>>APU Bin 3</option>
          <option value="APU Bin 4" <?php if($locPrev==="APU Bin 4") echo "selected"; ?>>APU Bin 4</option>
          <option value="Library Bin" <?php if($locPrev==="Library Bin") echo "selected"; ?>>Library Bin</option>
          <option value="Cafeteria Bin" <?php if($locPrev==="Cafeteria Bin") echo "selected"; ?>>Cafeteria Bin</option>
          <option value="Main Lobby" <?php if($locPrev==="Main Lobby") echo "selected"; ?>>Main Lobby</option>
          <option value="Innovation Hall" <?php if($locPrev==="Innovation Hall") echo "selected"; ?>>Innovation Hall</option>
          <option value="Green Zone" <?php if($locPrev==="Green Zone") echo "selected"; ?>>Green Zone</option>
        </select>

        <label for="photo">Photo (Proof)</label>
        <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png" required>
        <small style="color:#6b7280;">(Upload a clear photo to prove!)</small>

        <button class="btn page-btn" type="submit">Submit</button>

        <a class="btn btn-outline page-btn"
           href="user_recycle.php"
           style="display:block;text-align:center;margin-top:10px;text-decoration:none;">
          Back
        </a>
      </form>
    </div>

    <div class="confirm-backdrop" id="confirmModal">
      <div class="confirm-modal">
        <div class="modal-body">
          <h3 style="margin:0 0 6px;">You save the planet, you are my Hero!</h3>
          <p style="margin:0;color:#6b7280;">Confirm submit this recycle log?</p>
        </div>
        <div class="modal-actions">
          <button class="btn btn-outline half" type="button" id="btnBack">Back</button>
          <button class="btn half" type="button" id="btnYes">Yes</button>
        </div>
      </div>
    </div>

  </div>
</div>

<footer>
  <p>&copy; 2026 ReLife Hub</p>
</footer>

<script src="../user/user.js"></script>

<script>
  const form = document.getElementById("recycleForm");
  const modal = document.getElementById("confirmModal");
  const btnBack = document.getElementById("btnBack");
  const btnYes = document.getElementById("btnYes");

  let allowSubmit = false;

  form.addEventListener("submit", function(e) {
    if (!allowSubmit) {
      e.preventDefault();
      modal.style.display = "flex";
    }
  });

  btnBack.addEventListener("click", () => {
    modal.style.display = "none";
  });

  btnYes.addEventListener("click", () => {
    allowSubmit = true;
    modal.style.display = "none";
    form.submit();
  });

  modal.addEventListener("click", (e) => {
    if (e.target === modal) modal.style.display = "none";
  });
</script>

</body>
</html>
