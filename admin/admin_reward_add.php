<?php
session_start();
require_once "../connect.php";

$page_title = "Add Reward";
include "admin_header.php";

/* =====================
   STATUS MESSAGE
===================== */
$success = "";
$error   = "";

/* =====================
   HANDLE ADD REWARD
===================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $reward_name     = trim($_POST['reward_name'] ?? '');
    $description     = trim($_POST['description'] ?? '');
    $points_required = (int) ($_POST['points_required'] ?? 0);
    $stock           = (int) ($_POST['stock'] ?? 0);
    $is_active       = 1; // default active

    /* =====================
       BASIC VALIDATION
    ===================== */
    if ($reward_name === "" || $points_required <= 0 || $stock <= 0) {
        $error = "Reward name, points required, and stock must be greater than 0.";
    }
    elseif (!isset($_FILES['reward_image']) || $_FILES['reward_image']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please upload a valid reward image.";
    }
    else {

        /* =====================
           IMAGE UPLOAD
        ===================== */
        $upload_dir = realpath(__DIR__ . "/../images/reward");

        if ($upload_dir === false) {
            $error = "Reward image folder not found.";
        }
        else {

            $upload_dir .= DIRECTORY_SEPARATOR;
            $tmp_name   = $_FILES['reward_image']['tmp_name'];
            $file_name  = basename($_FILES['reward_image']['name']);
            $extension  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed = ['png', 'jpg', 'jpeg', 'webp'];

            if (!in_array($extension, $allowed)) {
                $error = "Only PNG, JPG, JPEG, WEBP files are allowed.";
            }
            elseif ($_FILES['reward_image']['size'] > 2 * 1024 * 1024) {
                $error = "Image too large. Max 2MB.";
            }
            elseif (!is_writable($upload_dir)) {
                $error = "Reward image folder is not writable.";
            }
            else {

                $target_path = $upload_dir . $file_name;

                if (!move_uploaded_file($tmp_name, $target_path)) {
                    $error = "Failed to upload reward image.";
                }
                else {

                    /* =====================
                       GENERATE REWARD ID
                    ===================== */
                    $idQuery = $conn->query("
                        SELECT reward_id 
                        FROM reward_catalog 
                        ORDER BY reward_id DESC 
                        LIMIT 1
                    ");

                    if (!$idQuery) {
                        die("SQL ERROR (ID QUERY): " . $conn->error);
                    }

                    if ($row = $idQuery->fetch_assoc()) {
                        $num = (int) filter_var($row['reward_id'], FILTER_SANITIZE_NUMBER_INT) + 1;
                    } else {
                        $num = 1;
                    }

                    $reward_id = "rwd_" . str_pad($num, 3, "0", STR_PAD_LEFT);

                    /* =====================
                       INSERT INTO DATABASE
                       (DEBUG ENABLED)
                    ===================== */
                    $stmt = $conn->prepare("
                        INSERT INTO reward_catalog
                        (reward_id, reward_name, description, points_required, stock, is_active, image_path, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");

                    if (!$stmt) {
                        die("PREPARE ERROR: " . $conn->error);
                    }

                    $stmt->bind_param(
                        "sssiiis",
                        $reward_id,
                        $reward_name,
                        $description,
                        $points_required,
                        $stock,
                        $is_active,
                        $file_name
                    );

                    if ($stmt->execute()) {
                        $success = "Reward added successfully.";

                        // Clear form values
                        $reward_name = $description = "";
                        $points_required = $stock = "";
                    } else {
                        die("EXECUTE ERROR: " . $stmt->error);
                    }
                }
            }
        }
    }
}
?>

<main class="dashboard">
<div class="main-content">

  <!-- PAGE TITLE BAR -->
  <div class="page-title-bar">
    <a href="admin_rewards.php" class="icon-btn back-btn">↩</a>
    <h2>Add Reward</h2>
  </div>

  <div class="profile-container">

    <!-- STATUS MESSAGE -->
    <?php if (!empty($success)): ?>
      <div class="alert-success">
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert-error">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- FORM -->
    <div class="form-panel">
    <form method="POST" enctype="multipart/form-data">

      <div class="form-group">
        <label>Reward Name</label>
        <input type="text" name="reward_name"
               value="<?= htmlspecialchars($reward_name ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label>Product Description</label>
        <textarea name="description" rows="3" required><?= htmlspecialchars($description ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Points Required</label>
        <input type="number" name="points_required" min="1"
               value="<?= htmlspecialchars($points_required ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label>Stock</label>
        <input type="number" name="stock" min="1"
               value="<?= htmlspecialchars($stock ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label>Product Image</label>
        <label class="upload-box">
          <span id="plusIcon">+</span>
          <img id="previewImg">
          <input type="file"
                 name="reward_image"
                 accept="image/*"
                 onchange="previewImage(event)"
                 required>
        </label>
      </div>

      <div style="text-align:center; margin-top:25px;">
        <button type="submit" class="action-btn">ADD</button>
      </div>

    </form>
    </div>

  </div>

</div>
</main>

<?php include "admin_footer.php"; ?>

<style>
.upload-box {
    width: 220px;
    height: 140px;
    border: 2px dashed #bbb;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    margin: 0 auto;
}
.upload-box img {
    max-width: 100%;
    max-height: 100%;
    display: none;
}
.upload-box span {
    font-size: 30px;
    color: #888;
}
input[type="file"] {
    display: none;
}
</style>

<script>
function previewImage(event) {
    const img  = document.getElementById("previewImg");
    const plus = document.getElementById("plusIcon");

    img.src = URL.createObjectURL(event.target.files[0]);
    img.style.display = "block";
    plus.style.display = "none";
}
</script>
