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

/* =====================
   HANDLE FORM SUBMIT
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $badge_name       = trim($_POST['badge_name']);
    $description      = trim($_POST['description']);
    $required_points  = (int) $_POST['required_points'];
    $status           = (int) $_POST['is_active'];

    $icon_path = $badge['icon_path']; // default: keep old icon

    /* Handle new icon upload */
    if (!empty($_FILES['icon']['name'])) {

        $upload_dir = "../images/badges/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext  = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
        $file_name = $badge_id . "." . $file_ext;
        $target    = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['icon']['tmp_name'], $target)) {
            $icon_path = "/images/badges/" . $file_name;
        }
    }

    if ($badge_name !== '' && $required_points > 0) {

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
            header("Location: admin_system_settings.php?view=badges");
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
    <a href="admin_system_settings.php?view=badges"
       class="icon-btn back-btn">↩</a>
    <h2><?= htmlspecialchars($badge_id) ?></h2>
  </div>

  <!-- CENTERED FORM -->
  <div class="profile-container">

    <form method="POST"
          enctype="multipart/form-data"
          style="
            background:#e5e5e5;
            padding:30px;
            border-radius:8px;
          ">

      <!-- BADGE ID -->
      <div class="form-group">
        <label>Badge ID</label>
        <input type="text"
               value="<?= htmlspecialchars($badge['badge_id']) ?>"
               readonly>
      </div>

      <!-- BADGE NAME -->
      <div class="form-group">
        <label>Badge Name</label>
        <input type="text"
               name="badge_name"
               value="<?= htmlspecialchars($badge['badge_name']) ?>"
               required>
      </div>

      <!-- DESCRIPTION -->
      <div class="form-group">
        <label>Badge Description</label>
        <input type="text"
               name="description"
               value="<?= htmlspecialchars($badge['description']) ?>">
      </div>

      <!-- POINTS -->
      <div class="form-group">
        <label>Points Required</label>
        <input type="number"
               name="required_points"
               min="1"
               value="<?= $badge['required_points'] ?>"
               required>
      </div>

      <!-- STATUS -->
      <div class="form-group">
        <label>Status</label>
        <select name="is_active">
          <option value="1" <?= $badge['is_active'] == 1 ? 'selected' : '' ?>>
            Active
          </option>
          <option value="0" <?= $badge['is_active'] == 0 ? 'selected' : '' ?>>
            Inactive
          </option>
        </select>
      </div>

      <!-- BADGE ICON -->
      <div class="form-group">
        <label>Badge Icon</label>

        <?php if ($badge['icon_path']): ?>
          <div style="margin-bottom:10px;">
            <img src="<?= $badge['icon_path'] ?>"
                 style="width:100px;border-radius:6px;">
          </div>
        <?php endif; ?>

        <input type="file"
               name="icon"
               accept="image/*">
      </div>

      <!-- ACTION BUTTONS -->
      <div style="display:flex; justify-content:center; gap:12px; margin-top:25px;">
        <a href="admin_system_settings.php?view=badges"
           class="action-btn">CANCEL</a>

        <button type="submit" class="action-btn">
          EDIT
        </button>
      </div>

    </form>

  </div>

</div>
</main>

<?php include "admin_footer.php"; ?>
/