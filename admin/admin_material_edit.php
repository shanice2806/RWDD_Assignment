<?php
session_start();
require_once "../connect.php";

$page_title = "Edit Materials";
include "admin_header.php";


if (!isset($_GET['id'])) {
    header("Location: admin_system_settings.php?view=materials");
    exit();
}

$material_id = $_GET['id'];


$stmt = $conn->prepare("
    SELECT material_id,
           materials_name,
           material_description,
           is_active
    FROM materials
    WHERE material_id = ?
");
$stmt->bind_param("s", $material_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_system_settings.php?view=materials");
    exit();
}

$material = $result->fetch_assoc();


$success = "";
$error   = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $materials_name = trim($_POST['materials_name']);
    $material_description = trim($_POST['material_description']);
    $status = $_POST['is_active'];

    if ($materials_name !== '') {

        $stmt = $conn->prepare("
            UPDATE materials
            SET materials_name = ?,
                material_description = ?,
                is_active = ?
            WHERE material_id = ?
        ");

        $stmt->bind_param(
            "ssis",
            $materials_name,
            $material_description,
            $status,
            $material_id
        );

        if ($stmt->execute()) {
            $success = "Material updated successfully.";


            $material['materials_name'] = $materials_name;
            $material['material_description'] = $material_description;
            $material['is_active'] = $status;

        } else {
            $error = "Failed to update material.";
        }

    } else {
        $error = "Material name cannot be empty.";
    }
}
?>

<main class="dashboard">
  <div class="main-content">

    <div class="page-title-bar">
      <a href="admin_system_settings.php?view=materials"
         class="icon-btn back-btn">↩</a>
      <h2><?= htmlspecialchars($material['material_id']) ?></h2>
    </div>


    <?php if (!empty($success)): ?>
      <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>


    <div class="profile-container">

      <form method="POST"
            style="
              background:#e5e5e5;
              padding:30px;
              border-radius:8px;
            ">


        <div class="form-group">
          <label>Material ID</label>
          <input type="text"
                 value="<?= htmlspecialchars($material['material_id']) ?>"
                 readonly>
        </div>


        <div class="form-group">
          <label>Material Name</label>
          <input type="text"
                 name="materials_name"
                 value="<?= htmlspecialchars($material['materials_name']) ?>"
                 required>
        </div>


        <div class="form-group">
          <label>Material Description</label>
          <input type="text"
                 name="material_description"
                 value="<?= htmlspecialchars($material['material_description']) ?>">
        </div>

        <div class="form-group">
          <label>Status</label>
          <select name="is_active">
            <option value="1" <?= $material['is_active'] == 1 ? 'selected' : '' ?>>
              Active
            </option>
            <option value="0" <?= $material['is_active'] == 0 ? 'selected' : '' ?>>
              Inactive
            </option>
          </select>
        </div>

        <div style="text-align:center; margin-top:25px;">
          <button type="submit" class="action-btn">
            Edit
          </button>
        </div>

      </form>

    </div>

  </div>
</main>

<?php include "admin_footer.php"; ?>
