<?php
session_start();
require_once "../connect.php";

$page_title = "Edit Reward";
include "admin_header.php";

/* =====================
   VALIDATE ID
===================== */
if (!isset($_GET['id'])) {
    header("Location: admin_rewards.php?table=catalog");
    exit();
}

$reward_id = $_GET['id'];

/* =====================
   FETCH REWARD
===================== */
$stmt = $conn->prepare("
    SELECT reward_id,
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
    header("Location: admin_rewards.php?table=catalog");
    exit();
}

$reward = $result->fetch_assoc();

/* =====================
   HANDLE FORM SUBMIT
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reward_name     = trim($_POST['reward_name']);
    $description     = trim($_POST['description']);
    $points_required = (int) $_POST['points_required'];
    $stock           = (int) $_POST['stock'];
    $status          = (int) $_POST['is_active'];

    /* Keep old image by default */
    $image_path = $reward['image_path'];

    /* =====================
       HANDLE IMAGE UPLOAD
    ===================== */
    if (!empty($_FILES['image']['name'])) {

        $upload_dir  = "../images/reward/";
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_ext)) {
            die("Invalid file type. Only JPG, JPEG, PNG & GIF allowed.");
        }

        /* Rename image using reward ID */
        $file_name = $reward_id . "." . $file_ext;
        $target    = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_path = $file_name; // store ONLY filename
        }
    }

    /* =====================
       UPDATE DATABASE
    ===================== */
    if ($reward_name !== '' && $points_required > 0 && $stock >= 0) {

        $stmt = $conn->prepare("
            UPDATE reward_catalog
            SET reward_name = ?,
                description = ?,
                points_required = ?,
                stock = ?,
                image_path = ?,
                is_active = ?
            WHERE reward_id = ?
        ");

        $stmt->bind_param(
            "ssiisis",
            $reward_name,
            $description,
            $points_required,
            $stock,
            $image_path,
            $status,
            $reward_id
        );

        if ($stmt->execute()) {
            header("Location: admin_rewards.php?table=catalog");
            exit();
        } else {
            die("Database Error: " . $stmt->error);
        }
    }
}
?>

<main class="dashboard">
<div class="main-content">

  <!-- PAGE TITLE BAR -->
  <div class="page-title-bar">
    <a href="admin_rewards.php?table=catalog"
       class="icon-btn back-btn">↩</a>
    <h2><?= htmlspecialchars($reward_id) ?></h2>
  </div>

  <!-- FORM CONTAINER -->
  <div class="profile-container">

    <form method="POST"
          enctype="multipart/form-data"
          style="background:#e5e5e5;padding:30px;border-radius:8px;">

      <!-- REWARD ID -->
      <div class="form-group">
        <label>Reward ID</label>
        <input type="text"
               value="<?= htmlspecialchars($reward['reward_id']) ?>"
               readonly>
      </div>

      <!-- REWARD NAME -->
      <div class="form-group">
        <label>Reward Name</label>
        <input type="text"
               name="reward_name"
               value="<?= htmlspecialchars($reward['reward_name']) ?>"
               required>
      </div>

      <!-- DESCRIPTION -->
      <div class="form-group">
        <label>Description</label>
        <input type="text"
               name="description"
               value="<?= htmlspecialchars($reward['description']) ?>">
      </div>

      <!-- POINTS REQUIRED -->
      <div class="form-group">
        <label>Points Required</label>
        <input type="number"
               name="points_required"
               min="1"
               value="<?= $reward['points_required'] ?>"
               required>
      </div>

      <!-- STOCK -->
      <div class="form-group">
        <label>Stock</label>
        <input type="number"
               name="stock"
               min="0"
               value="<?= $reward['stock'] ?>"
               required>
      </div>

      <!-- STATUS -->
      <div class="form-group">
        <label>Status</label>
        <select name="is_active">
          <option value="1" <?= $reward['is_active'] == 1 ? 'selected' : '' ?>>Active</option>
          <option value="0" <?= $reward['is_active'] == 0 ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>

      <!-- REWARD IMAGE -->
      <div class="form-group">
        <label>Reward Image</label>

        <?php
          $currentImage = !empty($reward['image_path'])
              ? $reward['image_path']
              : 'default.jpeg';
        ?>

        <div style="margin-bottom:10px;">
          <img src="../images/reward/<?= htmlspecialchars($currentImage) ?>"
               style="width:120px;border-radius:6px;">
        </div>

        <input type="file"
               name="image"
               accept="image/*">
      </div>

      <!-- ACTION BUTTONS -->
      <div style="display:flex;justify-content:center;gap:12px;margin-top:25px;">
        <a href="admin_rewards.php?table=catalog"
           class="action-btn">CANCEL</a>

        <button type="submit" class="action-btn">
          SAVE
        </button>
      </div>

    </form>

  </div>

</div>
</main>

<?php include "admin_footer.php"; ?>
