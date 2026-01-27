<?php
session_start();
require_once "../connect.php";

$page_title = "Change Profile Photo";
include "admin_header.php";

/* =====================
   AUTH CHECK
===================== */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$success = "";
$error   = "";

/* =====================
   HANDLE UPLOAD
===================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please select a valid image.";
    } else {

        $upload_dir = realpath(__DIR__ . "/../images/profile");
        if ($upload_dir === false) {
            $error = "Profile image folder not found.";
        } else {

            $upload_dir .= DIRECTORY_SEPARATOR;
            $tmp_name   = $_FILES['profile_image']['tmp_name'];
            $file_name  = basename($_FILES['profile_image']['name']);
            $extension  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed = ['png', 'jpg', 'jpeg', 'webp'];

            if (!in_array($extension, $allowed)) {
                $error = "Only PNG, JPG, JPEG, WEBP files are allowed.";
            } elseif ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                $error = "File too large. Max 2MB.";
            } elseif (!is_writable($upload_dir)) {
                $error = "Upload folder is not writable.";
            } else {

                /* Rename file to avoid overwrite */
                $new_name = $user_id . "_" . time() . "." . $extension;
                $target   = $upload_dir . $new_name;

                if (!move_uploaded_file($tmp_name, $target)) {
                    $error = "Failed to upload image.";
                } else {

                    /* Update DB */
                    $stmt = $conn->prepare("
                        UPDATE users
                        SET profile_image = ?
                        WHERE user_id = ?
                    ");
                    $stmt->bind_param("ss", $new_name, $user_id);

                    if ($stmt->execute()) {
                        $_SESSION['profile_image'] = $new_name;
                        $success = "Profile photo updated successfully.";
                    } else {
                        $error = "Database update failed.";
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
    <a href="edit_profile.php" class="icon-btn back-btn">↩</a>
    <h2>Change Profile Photo</h2>
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="profile-container">

    <form method="POST" enctype="multipart/form-data" style="text-align:center;">

      <label class="upload-box">
        <span id="plusIcon">+</span>
        <img id="previewImg">
        <input type="file"
               name="profile_image"
               accept="image/*"
               onchange="previewImage(event)"
               required>
      </label>

      <button type="submit" class="action-btn" style="margin-top:20px;">
        Save
      </button>

    </form>

  </div>

</div>
</main>

<?php include "admin_footer.php"; ?>

<style>
.upload-box {
  width: 200px;
  height: 200px;
  border: 2px dashed #bbb;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  margin: 0 auto;
  overflow: hidden;
}

.upload-box img {
  max-width: 100%;
  max-height: 100%;
  display: none;
  object-fit: cover;
}

.upload-box span {
  font-size: 32px;
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
