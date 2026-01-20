<?php
session_start();
require_once "../connect.php";

$page_title = "Add Material";
include "admin_header.php";

/* =====================
   HANDLE FORM SUBMIT
   ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $materials_name = trim($_POST['materials_name']);
    $material_description = trim($_POST['material_description']);

    if ($materials_name !== '') {

        /* Generate next material_id */
        $result = $conn->query("
            SELECT material_id
            FROM materials
            ORDER BY material_id DESC
            LIMIT 1
        ");

        if ($row = $result->fetch_assoc()) {
            $last_num = (int) substr($row['material_id'], 4);
            $new_num  = $last_num + 1;
        } else {
            $new_num = 1;
        }

        $material_id = 'mat_' . str_pad($new_num, 3, '0', STR_PAD_LEFT);

        /* Insert material */
        $stmt = $conn->prepare("
            INSERT INTO materials
            (material_id, materials_name, material_description, is_active)
            VALUES (?, ?, ?, 1)
        ");

        $stmt->bind_param(
            "sss",
            $material_id,
            $materials_name,
            $material_description
        );

        if ($stmt->execute()) {
            header("Location: admin_system_settings.php?view=materials");
            exit();
        }
    }
}
?>

<main class="dashboard">
  <div class="main-content">

<div class="page-title-bar">
    <a href="admin_system_settings.php?view=materials" class="icon-btn back-btn">↩</a>

      <h2>Add Material</h2>
    </div>

    <!-- CENTERED FORM -->
    <div class="profile-container">

      <form method="POST"
            style="
              background:#e5e5e5;
              padding:30px;
              border-radius:8px;
            ">

        <div class="form-group">
          <label>Material Name</label>
          <input type="text"
                 name="materials_name"
                 required>
        </div>

        <div class="form-group">
          <label>Material Description</label>
          <input type="text"
                 name="material_description">
        </div>

        <!-- ADD BUTTON -->
        <div style="text-align:center; margin-top:25px;">
          <button type="submit" class="action-btn">
            ADD
          </button>
        </div>

      </form>

    </div>

  </div>
</main>

<?php include "admin_footer.php"; ?>
