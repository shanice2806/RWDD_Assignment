<?php
session_start();
require_once "../connect.php";

$page_title = "Add Badge";
include "admin_header.php";


$success = "";
$error   = "";


$badge_name = "";
$description = "";
$required_points = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $badge_name      = trim($_POST['badge_name']);
    $description     = trim($_POST['description']);
    $required_points = (int) $_POST['required_points'];
    $is_active       = 1;

    if ($badge_name === "" || $required_points <= 0) {
        $error = "Badge name and points are required.";
    } elseif (!isset($_FILES['badge_icon']) || $_FILES['badge_icon']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please upload a valid badge icon.";
    } else {

        $upload_dir = realpath(__DIR__ . "/../images/badges");
        if ($upload_dir === false) {
            $error = "Upload folder not found.";
        } else {

            $upload_dir .= DIRECTORY_SEPARATOR;
            $tmp_name   = $_FILES['badge_icon']['tmp_name'];
            $file_name  = basename($_FILES['badge_icon']['name']);
            $extension  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed = ['png', 'jpg', 'jpeg', 'webp'];
            if (!in_array($extension, $allowed)) {
                $error = "Only PNG, JPG, JPEG, WEBP files are allowed.";
            } elseif ($_FILES['badge_icon']['size'] > 2 * 1024 * 1024) {
                $error = "File too large. Max 2MB.";
            } elseif (!is_writable($upload_dir)) {
                $error = "Upload folder is not writable.";
            } else {

                $target_path = $upload_dir . $file_name;

                if (!move_uploaded_file($tmp_name, $target_path)) {
                    $error = "Failed to upload image.";
                } else {

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
                        $file_name,
                        $description,
                        $is_active
                    );

                    if ($stmt->execute()) {
                        $success = "Badge added successfully.";

     
                        $badge_name = "";
                        $description = "";
                        $required_points = "";
                    } else {
                        $error = "Database error: " . $stmt->error;
                    }
                }
            }
        }
    }
}
?>

<main class="dashboard">
<div class="main-content">


  <div class="page-title-bar">
    <a href="admin_system_settings.php?view=badges"
       class="icon-btn back-btn">↩</a>
    <h2>Add Badge</h2>
  </div>


    <?php if (!empty($success)): ?>
      <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>


  <div class="profile-container">


    <div class="form-panel">
    <form method="POST" enctype="multipart/form-data" id="badgeForm">

      <div class="form-group">
        <label>Badge Name</label>
        <input type="text" name="badge_name"
               value="<?= htmlspecialchars($badge_name) ?>" required>
      </div>

      <div class="form-group">
        <label>Badge Description</label>
        <textarea name="description" rows="3" required><?= htmlspecialchars($description) ?></textarea>
      </div>

      <div class="form-group">
        <label>Points Required</label>
        <input type="number" name="required_points"
               min="1" value="<?= htmlspecialchars($required_points) ?>" required>
      </div>

      <div class="form-group">
        <label>Badge Icon</label>

        <label class="upload-box">
          <span id="plusIcon">+</span>
          <img id="previewImg">
          <input type="file"
                 name="badge_icon"
                 id="badgeIcon"
                 accept="image/*"
                 onchange="previewImage(event)"
                 required>
        </label>
      </div>

      <div style="text-align:center; margin-top:25px;">
        <button type="submit" class="action-btn">Add</button>
      </div>

    </form>
    </div>

  </div>

</div>
</main>

<?php include "admin_footer.php"; ?>

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


<?php if (!empty($success)): ?>
document.getElementById("badgeIcon").value = "";
document.getElementById("previewImg").style.display = "none";
document.getElementById("plusIcon").style.display = "block";
<?php endif; ?>
</script>
