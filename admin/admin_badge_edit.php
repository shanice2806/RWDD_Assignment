<?php
session_start();
require_once "../connect.php";

$page_title = "Edit Badge";
include "admin_header.php";

/* =====================
   VALIDATE ID
===================== */
if (!isset($_GET['id'])) {
    header("Location: admin_system_settings.php?view=badges");
    exit();
}

$badge_id = $_GET['id'];

/* =====================
   FETCH BADGE
===================== */
$stmt = $conn->prepare("
    SELECT badge_id,
           badge_name,
           description,
           required_points,
           icon_path,
           is_active
    FROM badges
    WHERE badge_id = ?
");
$stmt->bind_param("s", $badge_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_system_settings.php?view=badges");
    exit();
}

$badge = $result->fetch_assoc();

/* STATUS MESSAGE */
$success = "";
$error   = "";

/* =====================
   HANDLE FORM SUBMIT
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $badge_name      = trim($_POST['badge_name']);
    $description     = trim($_POST['description']);
    $required_points = (int) $_POST['required_points'];
    $status          = (int) $_POST['is_active'];

    if ($badge_name === "" || $required_points <= 0) {
        $error = "Badge name and points are required.";
    } else {

        /* Default: keep existing icon */
        $icon_path = $badge['icon_path'];

        /* =====================
           HANDLE ICON UPLOAD
        ===================== */
        if (!empty($_FILES['icon']['name'])) {

            $upload_dir  = "../images/badges/";
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $original_name = basename($_FILES['icon']['name']);
            $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            if (!in_array($file_ext, $allowed_ext)) {
                $error = "Invalid file type. Only JPG, JPEG, PNG, GIF, WEBP allowed.";
            } else {
                $target = $upload_dir . $original_name;

                if (move_uploaded_file($_FILES['icon']['tmp_name'], $target)) {
                    $icon_path = $original_name;
                } else {
                    $error = "Failed to upload badge icon.";
                }
            }
        }

        /* =====================
           UPDATE DATABASE
        ===================== */
        if ($error === "") {

            $stmt = $conn->prepare("
                UPDATE badges
                SET badge_name = ?,
                    description = ?,
                    required_points = ?,
                    icon_path = ?,
                    is_active = ?
                WHERE badge_id = ?
            ");

            $stmt->bind_param(
                "ssisis",
                $badge_name,
                $description,
                $required_points,
                $icon_path,
                $status,
                $badge_id
            );

            if ($stmt->execute()) {
                $success = "Badge updated successfully.";

                /* refresh local data */
                $badge['badge_name'] = $badge_name;
                $badge['description'] = $description;
                $badge['required_points'] = $required_points;
                $badge['icon_path'] = $icon_path;
                $badge['is_active'] = $status;
            } else {
                $error = "Failed to update badge.";
            }
        }
    }
}
?>

<main class="dashboard">
<div class="main-content">

  <!-- PAGE TITLE BAR -->
  <div class="page-title-bar">
    <a href="admin_system_settings.php?view=badges"
       class="icon-btn back-btn">↩</a>
    <h2><?= htmlspecialchars($badge_id) ?></h2>
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
    <form method="POST"
          enctype="multipart/form-data"
          class="form-panel">

      <div class="form-group">
        <label>Badge ID</label>
        <input type="text" value="<?= htmlspecialchars($badge['badge_id']) ?>" readonly>
      </div>

      <div class="form-group">
        <label>Badge Name</label>
        <input type="text" name="badge_name"
               value="<?= htmlspecialchars($badge['badge_name']) ?>" required>
      </div>

      <div class="form-group">
        <label>Description</label>
        <input type="text" name="description"
               value="<?= htmlspecialchars($badge['description']) ?>">
      </div>

      <div class="form-group">
        <label>Points Required</label>
        <input type="number" name="required_points"
               min="1" value="<?= $badge['required_points'] ?>" required>
      </div>

      <div class="form-group">
        <label>Status</label>
        <select name="is_active">
          <option value="1" <?= $badge['is_active'] == 1 ? 'selected' : '' ?>>Active</option>
          <option value="0" <?= $badge['is_active'] == 0 ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>

      <div class="form-group">
        <label>Badge Icon</label>

        <?php if (!empty($badge['icon_path'])): ?>
          <div style="margin-bottom:10px;">
            <img src="../images/badges/<?= htmlspecialchars($badge['icon_path']) ?>"
                 style="width:100px;border-radius:6px;">
          </div>
        <?php endif; ?>

        <input type="file" name="icon" accept="image/*">
      </div>

      <div style="display:flex;justify-content:center;gap:12px;margin-top:25px;">
        <a href="admin_system_settings.php?view=badges" class="action-btn">Cancel</a>
        <button type="submit" class="action-btn">Edit</button>
      </div>

    </form>

  </div>

</div>
</main>

<?php include "admin_footer.php"; ?>
