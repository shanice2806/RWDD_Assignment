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

$log_id = trim($_GET["log_id"] ?? "");
if ($log_id === "") {
  header("Location: user_recycle.php");
  exit();
}

// SAVE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_log"])) {
  $material_id = trim($_POST["material_id"] ?? "");
  $weight_kg   = trim($_POST["weight_kg"] ?? "");
  $location    = trim($_POST["location"] ?? "");

  if ($material_id === "" || $weight_kg === "") {
    header("Location: recycle_edit.php?log_id=" . urlencode($log_id) . "&err=1");
    exit();
  }

  $upd = mysqli_prepare($conn, "
    UPDATE recycling_log
    SET material_id=?, weight_kg=?, location=?
    WHERE log_id=? AND user_id=?
    LIMIT 1
  ");
  mysqli_stmt_bind_param($upd, "sssss", $material_id, $weight_kg, $location, $log_id, $user_id);
  mysqli_stmt_execute($upd);
  mysqli_stmt_close($upd);

  header("Location: user_recycle.php?updated=1&edit=1");
  exit();
}

// LOAD
$get = mysqli_prepare($conn, "
  SELECT log_id, material_id, weight_kg, location
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Recycle Log</title>
  <style>
    body{font-family:Arial,sans-serif;background:#f5f6f8;margin:0}
    .card{max-width:520px;margin:30px auto;background:#fff;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,.08);padding:18px}
    label{display:block;margin:12px 0 6px;font-weight:700}
    input, select{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;box-sizing:border-box}
    .btn{margin-top:14px;width:100%;padding:10px 12px;border:0;border-radius:10px;background:#111827;color:#fff;font-weight:800;cursor:pointer}
    .btn-outline{background:#fff;border:1px solid #ddd;color:#111827}
    .msg{padding:10px 12px;border-radius:10px;margin:10px 0}
    .msg-error{background:#ffe8e8;color:#b00020}
  </style>
</head>
<body>
  <div class="card">
    <h2 style="margin:0 0 8px;">Edit Submitted Recycle Item</h2>

    <?php if (isset($_GET["err"])): ?>
      <div class="msg msg-error">❌ Please fill in Material and Weight.</div>
    <?php endif; ?>

    <form method="POST">
      <label for="material_id">Materials</label>
      <select id="material_id" name="material_id" required>
        <option value="mat_001" <?php if($log["material_id"]==="mat_001") echo "selected"; ?>>mat_001 (Plastic)</option>
        <option value="mat_002" <?php if($log["material_id"]==="mat_002") echo "selected"; ?>>mat_002 (Tin)</option>
        <option value="mat_003" <?php if($log["material_id"]==="mat_003") echo "selected"; ?>>mat_003 (Paper)</option>
        <option value="mat_004" <?php if($log["material_id"]==="mat_004") echo "selected"; ?>>mat_004 (Glass)</option>
        <option value="mat_005" <?php if($log["material_id"]==="mat_005") echo "selected"; ?>>mat_005 (Others)</option>
      </select>

      <label for="weight_kg">Weight (kg)</label>
      <input id="weight_kg" name="weight_kg" type="number" step="0.01" value="<?php echo htmlspecialchars($log["weight_kg"]); ?>" required>

      <label for="location">Location</label>
      <input id="location" name="location" type="text" value="<?php echo htmlspecialchars($log["location"] ?? ""); ?>">

      <button class="btn" type="submit" name="save_log" value="1">Done</button>
      <a class="btn btn-outline" href="user_recycle.php?edit=1" style="display:block;text-align:center;margin-top:10px;text-decoration:none;">Back</a>
    </form>
  </div>
</body>
</html>
