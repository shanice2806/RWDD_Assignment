<?php
session_start();
require_once "../connect.php";

$page_title = "Edit Reward";
include "admin_header.php";

/* =====================
   VALIDATE ID
===================== */
if (!isset($_GET['id'])) {
    header("Location: admin_rewards.php");
    exit();
}

$reward_id = $_GET['id'];

/* =====================
   FETCH REWARD
===================== */
$stmt = $conn->prepare("
    SELECT 
        reward_id,
        reward_name,
        description,
        points_required,
        stock,
        is_active,
        image_path
    FROM reward_catalog
    WHERE reward_id = ?
");
$stmt->bind_param("s", $reward_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_rewards.php");
    exit();
}

$reward = $result->fetch_assoc();

/* =====================
   STATUS MESSAGE
===================== */
$success = "";
$error   = "";

/* =====================
   HANDLE FORM SUBMIT
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reward_name     = trim($_POST['reward_name']);
    $description     = trim($_POST['description']);
    $points_required = (int) $_POST['points_required'];
    $stock           = (int) $_POST['stock'];
    $status          = (int) $_POST['is_active'];

    if ($reward_name === "" || $points_required <= 0) {
        $error = "Reward name, points required, and stock must be greater than 0.";
    } else {

        /* Default: keep existing image */
        $image_path = $reward['image_path'];

        /* =====================
           HANDLE IMAGE UPLOAD
        ===================== */
        if (!empty($_FILES['image']['name'])) {

            $upload_dir  = "../images/reward/";
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $original_name = basename($_FILES['image']['name']);
            $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            if (!in_array($file_ext, $allowed_ext)) {
                $error = "Invalid image type. Only JPG, JPEG, PNG, GIF, WEBP allowed.";
            } else {

                $target = $upload_dir . $original_name;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $image_path = $original_name;
                } else {
                    $error = "Failed to upload reward image.";
                }
            }
        }

        /* =====================
           UPDATE DATABASE
        ===================== */
        if ($error === "") {

            $stmt = $conn->prepare("
                UPDATE reward_catalog
                SET reward_name = ?,
                    description = ?,
                    points_required = ?,
                    stock = ?,
                    is_active = ?,
                    image_path = ?
                WHERE reward_id = ?
            ");

            $stmt->bind_param(
                "ssiiiss",
                $reward_name,
                $description,
                $points_required,
                $stock,
                $status,
                $image_path,
                $reward_id
            );

            if ($stmt->execute()) {
                $success = "Reward updated successfully.";

                /* Refresh local data */
                $reward['reward_name']     = $reward_name;
                $reward['description']     = $description;
                $reward['points_required'] = $points_required;
                $reward['stock']           = $stock;
                $reward['is_active']       = $status;
                $reward['image_path']      = $image_path;
            } else {
                $error = "Failed to update reward.";
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
    <h2><?= htmlspecialchars($reward_id) ?></h2>
  </div>

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

  <div class="profile-container">

    

    <!-- FORM -->
    <form method="POST"
          enctype="multipart/form-data"
          class="form-panel">

      <div class="form-group">
        <label>Reward ID</label>
        <input type="text"
               value="<?= htmlspecialchars($reward['reward_id']) ?>"
               readonly>
      </div>

      <div class="form-group">
        <label>Reward Name</label>
        <input type="text"
               name="reward_name"
               value="<?= htmlspecialchars($reward['reward_name']) ?>"
               required>
      </div>

      <div class="form-group">
        <label>Product Description</label>
        <textarea name="description" rows="3" required><?= htmlspecialchars($reward['description']) ?></textarea>
      </div>

      <div class="form-group">
        <label>Points Required</label>
        <input type="number"
               name="points_required"
               min="1"
               value="<?= $reward['points_required'] ?>"
               required>
      </div>

      <div class="form-group">
        <label>Stock</label>
        <input type="number"
               name="stock"
               value="<?= $reward['stock'] ?>"
               required>
      </div>

      <div class="form-group">
        <label>Status</label>
        <select name="is_active">
          <option value="1" <?= $reward['is_active'] == 1 ? 'selected' : '' ?>>Active</option>
          <option value="0" <?= $reward['is_active'] == 0 ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>

      <div class="form-group">
        <label>Product Image</label>

        <?php if (!empty($reward['image_path'])): ?>
          <div style="margin-bottom:10px;">
            <img src="../images/reward/<?= htmlspecialchars($reward['image_path']) ?>"
                 style="width:120px;border-radius:6px;">
          </div>
        <?php endif; ?>

        <input type="file" name="image" accept="image/*">
      </div>

      <div style="display:flex;justify-content:center;gap:12px;margin-top:25px;">
  
        <button type="submit" class="action-btn">Edit</button>
      </div>

    </form>

  </div>

</div>
</main>

<?php include "admin_footer.php"; ?>

