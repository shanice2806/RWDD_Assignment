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

$userSql = "SELECT name, eco_points FROM users WHERE user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res) ?: ["name"=>"User","eco_points"=>0];
mysqli_stmt_close($stmt);

$profileImg = trim($user["profile_image"] ?? "");
$profileImg = ($profileImg !== "") ? ("../" . $profileImg) : "../images/apu.png";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $material_id = trim($_POST["material_id"] ?? "");
  $weight_kg   = trim($_POST["weight_kg"] ?? "");
  $location    = trim($_POST["location"] ?? "");

  if ($material_id === "" || $weight_kg === "" || $location === "") {
    redirectErr(1); 
  }

  if (!isset($_FILES["photo"]) || $_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {
    redirectErr(2); 
  }

  $allowedExt = ["jpg", "jpeg", "png"];
  $maxSize    = 5 * 1024 * 1024;

  $tmpPath  = $_FILES["photo"]["tmp_name"];
  $origName = $_FILES["photo"]["name"];
  $fileSize = (int)($_FILES["photo"]["size"] ?? 0);

  if ($fileSize <= 0 || $fileSize > $maxSize) {
    redirectErr(3);
  }

  $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExt)) {
    redirectErr(4);
  }

  if (@getimagesize($tmpPath) === false) {
    redirectErr(5);
  }

  $uploadDir = __DIR__ . "/../images/recycling_proof/";
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
  }

  $log_id = "rl_" . uniqid();

  $newFileName   = $log_id . "." . $ext;
  $destFullPath  = $uploadDir . $newFileName;


  $photo_path = "images/recycling_proof/" . $newFileName;

  if (!move_uploaded_file($tmpPath, $destFullPath)) {
    redirectErr(6);
  }

  $event_id        = NULL;
  $submitted_at    = date("Y-m-d H:i:s");
  $status          = "pending"; 
  $points_awarded  = 0;
  $is_flagged      = 0;
  $flag_reason     = NULL;

  $sql = "
    INSERT INTO recycling_log
    (log_id, user_id, material_id, event_id, weight_kg, location, photo_path,
     submitted_at, status, points_awarded, is_flagged, flag_reason)
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ";

  $ins = mysqli_prepare($conn, $sql);
  if (!$ins) {
    @unlink($destFullPath);
    die("SQL prepare error: " . mysqli_error($conn));
  }

  mysqli_stmt_bind_param(
    $ins,
    "sssssssssiis",
    $log_id,
    $user_id,
    $material_id,
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

  if (!mysqli_stmt_execute($ins)) {
    @unlink($destFullPath);
    die("Insert error: " . mysqli_stmt_error($ins));
  }

  mysqli_stmt_close($ins);

  header("Location: user_recycle.php?submitted=1");
  exit();
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
          <option value="mat_001" <?php if($matPrev==="mat_001") echo "selected"; ?>>mat_001 (Plastic)</option>
          <option value="mat_002" <?php if($matPrev==="mat_002") echo "selected"; ?>>mat_002 (Tin)</option>
          <option value="mat_003" <?php if($matPrev==="mat_003") echo "selected"; ?>>mat_003 (Paper)</option>
          <option value="mat_004" <?php if($matPrev==="mat_004") echo "selected"; ?>>mat_004 (Glass)</option>
          <option value="mat_005" <?php if($matPrev==="mat_005") echo "selected"; ?>>mat_005 (Others)</option>
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
