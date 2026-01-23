<?php
session_start();
require_once "../connect.php";

$page_title = "Add Badge";
include "admin_header.php";

/* =====================
   HANDLE ADD BADGE
===================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $badge_name      = trim($_POST['badge_name']);
    $description     = trim($_POST['description']);
    $required_points = (int) $_POST['required_points'];
    $is_active       = 1;

    /* =====================
       IMAGE UPLOAD
    ===================== */
    if (!isset($_FILES['badge_icon'])) {
        die("No file uploaded.");
    }

    if ($_FILES['badge_icon']['error'] !== UPLOAD_ERR_OK) {
        die("Upload error code: " . $_FILES['badge_icon']['error']);
    }

    $upload_dir = realpath(__DIR__ . "/../images/badges");
    if ($upload_dir === false) {
        die("Upload folder not found.");
    }
    $upload_dir .= DIRECTORY_SEPARATOR;

    $tmp_name  = $_FILES['badge_icon']['tmp_name'];
    $extension = strtolower(pathinfo($_FILES['badge_icon']['name'], PATHINFO_EXTENSION));

    $allowed = ['png', 'jpg', 'jpeg', 'webp'];
    if (!in_array($extension, $allowed)) {
        die("Only PNG, JPG, JPEG, WEBP files are allowed.");
    }

    if ($_FILES['badge_icon']['size'] > 2 * 1024 * 1024) {
        die("File too large. Max 2MB.");
    }

    if (!is_writable($upload_dir)) {
        die("Upload folder is not writable.");
    }

    $new_filename = uniqid("badge_") . "." . $extension;
    $target_path  = $upload_dir . $new_filename;

    if (!move_uploaded_file($tmp_name, $target_path)) {
        die("Failed to upload image.");
    }

    /* =====================
       INSERT INTO DATABASE
    ===================== */
    $badge_id = uniqid("b_");

    $stmt = $conn->prepare("
        INSERT INTO badges
        (badge_id, badge_name, required_points, icon_path, description, is_active)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssissi",
        $badge_id,
        $badge_name,
        $required_points,
        $new_filename,
        $description,
        $is_active
    );

    if ($stmt->execute()) {
        header("Location: admin_badge.php?added=1");
        exit();
    } else {
        die("Database error: " . $stmt->error);
    }
}
?>

<main class="dashboard">
<div class="main-content">

  <!-- PAGE TITLE BAR -->
  <div class="page-title-bar">
    <a href="http://localhost/RWDD_Assignment/admin/admin_system_settings.php?view=badges" class="icon-btn back-btn">↩</a>
    <h2>Add Badge</h2>
  </div>

  <!-- CENTERED FORM -->
  <div class="profile-container">
    <div class="form-panel">
    <form method="POST" enctype="multipart/form-data">

      <div class="form-group">
        <label>Badge Name</label>
        <input type="text" name="badge_name" required>
      </div>

      <div class="form-group">
        <label>Badge Description</label>
        <textarea name="description" rows="3" required></textarea>
      </div>

      <div class="form-group">
        <label>Points Required</label>
        <input type="number" name="required_points" min="1" required>
      </div>

      <div class="form-group">
        <label>Badge Icon</label>

        <!-- Upload box stays unchanged -->
        <label class="upload-box">
          <span id="plusIcon">+</span>
          <img id="previewImg">
          <input type="file"
                 name="badge_icon"
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
</main>

<?php include "admin_footer.php"; ?>

<!-- Upload box styles ONLY -->
<style>
.upload-box {
    width: 180px;
    height: 120px;
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
    font-size: 28px;
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
