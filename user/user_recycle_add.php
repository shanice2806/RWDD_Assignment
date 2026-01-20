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

// Step 2: confirm submit (insert)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["confirm_submit"])) {
  $material_id = trim($_POST["material_id"] ?? "");
  $weight_kg   = trim($_POST["weight_kg"] ?? "");

  if ($material_id === "" || $weight_kg === "") {
    header("Location: recycle_add.php?err=1");
    exit();
  }

  // default values (match your table)
  $log_id = "rl_" . str_pad((string)rand(1, 999999), 3, "0", STR_PAD_LEFT); // beginner id
  $event_id = NULL;
  $photo_path = NULL;  // later you can upload
  $submitted_at = date("Y-m-d H:i:s");
  $status = "PENDING";
  $points_awarded = 0;
  $is_flagged = 0;
  $flag_reason = NULL;

  $ins = mysqli_prepare($conn, "
    INSERT INTO recycling_log
      (log_id, user_id, material_id, event_id, weight_kg, photo_path, submitted_at, status, points_awarded, is_flagged, flag_reason)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  mysqli_stmt_bind_param(
    $ins,
    "ssssssssiis",
    $log_id,
    $user_id,
    $material_id,
    $event_id,
    $weight_kg,
    $photo_path,
    $submitted_at,
    $status,
    $points_awarded,
    $is_flagged,
    $flag_reason
  );
  mysqli_stmt_execute($ins);
  mysqli_stmt_close($ins);

  header("Location: user_recycle.php?submitted=1");
  exit();
}

// Step 1: preview submit -> show modal
$showConfirm = ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["preview_submit"]));
$matPrev = trim($_POST["material_id"] ?? "");
$wPrev   = trim($_POST["weight_kg"] ?? "");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recycle log details</title>
  <style>
    body{font-family:Arial,sans-serif;
    background:#f5f6f8;
    margin:0}

    .card{max-width:520px;
    margin:300px auto;
    background:#fff;
    border-radius:12px;
    box-shadow:0 6px 18px rgba(0,0,0,.08);
    padding:18px}

    label{display:block;
    margin:12px 0 6px;
    font-weight:700}

    input, select{width:100%;
    padding:10px 12px;
    border:1px solid #ddd;
    border-radius:10px;
    box-sizing:border-box}

    .btn{margin-top:14px;
    width:100%;padding:10px 12px;
    border:0;border-radius:10px;
    background:#111827;color:#fff;
    font-weight:800;
    cursor:pointer}

    .btn-outline{background:#fff;
    border:1px solid #ddd;
    color:#111827}

    .msg{padding:10px 12px;
    border-radius:10px;
    margin:10px 0}

    .msg-error{background:#ffe8e8;
    color:#b00020}

    .modal-backdrop{
      position:fixed; 
      inset:0;
       background:rgba(0,0,0,.45);
      display:none;
       align-items:center;
        justify-content:center;
         padding:18px;
          z-index:9999;
    }

    .modal{width:100%;
    max-width:420px;
    background:#fff;
    border-radius:14px;
    box-shadow:0 10px 30px rgba(0,0,0,.2);
    overflow:hidden}

    .modal-body{padding:18px;
    text-align:center}

    .modal-actions{display:flex;
    gap:10px;
    justify-content:center;
    padding:0 18px 18px}
    .half{width:120px}
  </style>
</head>
<body>

  <div class="card">
    <h2 style="margin:0 0 8px;">Recycle log details</h2>

    <?php if (isset($_GET["err"])): ?>
      <div class="msg msg-error">❌ Please fill in Material and Weight.</div>
    <?php endif; ?>

    <form method="POST">
      <label>TP number</label>
      <input type="text" value="<?php echo htmlspecialchars($user_id); ?>" readonly>

      <label for="weight_kg">Weight (kg)</label>
      <input id="weight_kg" name="weight_kg" type="number" step="0.01" placeholder="e.g. 1.20" value="<?php echo htmlspecialchars($wPrev); ?>" required>

      <label for="material_id">Materials</label>
      <select id="material_id" name="material_id" required>
        <option value="">-- Select Materials Type --</option>
        <option value="mat_001" <?php if($matPrev==="mat_001") echo "selected"; ?>>mat_001 (Plastic)</option>
        <option value="mat_002" <?php if($matPrev==="mat_002") echo "selected"; ?>>mat_002 (Tin)</option>
        <option value="mat_003" <?php if($matPrev==="mat_003") echo "selected"; ?>>mat_003 (Paper)</option>
        <option value="mat_004" <?php if($matPrev==="mat_004") echo "selected"; ?>>mat_004 (Glass)</option>
        <option value="mat_005" <?php if($matPrev==="mat_005") echo "selected"; ?>>mat_005 (Others)</option>
      </select>

      <label>Photo</label>
      <input type="file" disabled>
      <small style="color:#6b7280;">(Upload can be added later)</small>

      <h2>Location :<br> in front of Audi five</h2>

      <button class="btn" type="submit" name="preview_submit" value="1">Submit</button>
      <a class="btn btn-outline" href="user_recycle.php" style="display:block;text-align:center;margin-top:10px;text-decoration:none;">Back</a>
    </form>
  </div>

  <!-- Confirm popup -->
  <div class="modal-backdrop" id="confirmModal">
    <div class="modal">
      <div class="modal-body">
        <h3 style="margin:0 0 6px;">You save the planet, you are my Hero!</h3>
        <p style="margin:0;color:#6b7280;">Confirm submit this recycle log?</p>
      </div>
      <div class="modal-actions">
        <button class="btn btn-outline half" type="button" id="btnBack">Back</button>

        <form method="POST" style="margin:0;">
          <input type="hidden" name="material_id" value="<?php echo htmlspecialchars($matPrev); ?>">
          <input type="hidden" name="weight_kg" value="<?php echo htmlspecialchars($wPrev); ?>">
          <button class="btn half" type="submit" name="confirm_submit" value="1">Yes</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    const showConfirm = <?php echo $showConfirm ? "true" : "false"; ?>;
    const modal = document.getElementById("confirmModal");
    const btnBack = document.getElementById("btnBack");

    if (showConfirm) modal.style.display = "flex";
    btnBack.addEventListener("click", () => modal.style.display = "none");
    modal.addEventListener("click", (e) => { if (e.target === modal) modal.style.display = "none"; });
  </script>

</body>
</html>
