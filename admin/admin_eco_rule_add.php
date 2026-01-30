<?php
include "../connect.php";

$page_title = "Add Eco Rule";
include "admin_header.php";

/* =====================
   HANDLE FORM SUBMIT
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rule_key     = trim($_POST['rule_key']);
    $rule_type    = $_POST['rule_type'];
    $description  = trim($_POST['description']);
    $points_per_kg = (int) $_POST['points_per_kg'];
    $material_id  = $_POST['material_id'] ?: NULL;

    /* Safety: material_id only for RECYCLE */
    if ($rule_type !== 'RECYCLE') {
        $material_id = NULL;
    }

    if ($rule_key !== '' && $points_per_kg > 0 && $rule_type !== '') {

        /* =====================
           GENERATE RULE ID
        ===================== */
        $result = $conn->query("
            SELECT rule_id
            FROM eco_points_rules
            ORDER BY rule_id DESC
            LIMIT 1
        ");

        if ($row = $result->fetch_assoc()) {
            $last_num = (int) filter_var($row['rule_id'], FILTER_SANITIZE_NUMBER_INT);
            $new_num  = $last_num + 1;
        } else {
            $new_num = 1;
        }

        $rule_id = 'epr_' . str_pad($new_num, 3, '0', STR_PAD_LEFT);

        /* =====================
           INSERT ECO RULE
        ===================== */
        $stmt = $conn->prepare("
            INSERT INTO eco_points_rules
                (rule_id, rule_key, rule_type, description, points_per_kg, material_id, is_active)
            VALUES
                (?, ?, ?, ?, ?, ?, 1)
        ");

        $stmt->bind_param(
            "ssssds",
            $rule_id,
            $rule_key,
            $rule_type,
            $description,
            $points_per_kg,
            $material_id
        );

        if ($stmt->execute()) {
            header("Location: admin_system_settings.php?view=eco_rules");
            exit();
        } else {
            die("Database Error: " . $stmt->error);
        }
    }
}
?>

<div class="main-content">
<main class="dashboard">

  <!-- PAGE TITLE BAR -->
  <div class="page-title-bar">
    <a href="admin_system_settings.php?view=eco_rules"
       class="icon-btn back-btn">↩</a>
    <h2>Add Eco Rule</h2>
  </div>

  <!-- FORM -->
  <div class="profile-container">
    <div class="form-panel">

    <form method="POST">

      <!-- RULE KEY -->
      <div class="form-group">
        <label>Rule Key</label>
        <input type="text"
               name="rule_key"
               placeholder="e.g. recycle_plastic"
               required>
      </div>

      <!-- RULE TYPE -->
      <div class="form-group">
        <label>Rule Type</label>
        <select name="rule_type" id="ruleType" required>
          <option value="">-- Select Type --</option>
          <option value="RECYCLE">RECYCLE</option>
          <option value="EVENT">EVENT</option>
          <option value="CONTENT">CONTENT</option>
        </select>
      </div>

      <!-- DESCRIPTION -->
      <div class="form-group">
        <label>Description</label>
        <input type="text"
               name="description"
               placeholder="Describe how points are awarded">
      </div>

      <!-- POINTS PER KG -->
      <div class="form-group">
        <label>Points Per KG</label>
        <input type="number"
               name="points_per_kg"
               min="1"
               required>
      </div>

      <!-- MATERIAL ID (RECYCLE ONLY) -->
      <div class="form-group" id="materialGroup" style="display:none;">
        <label>Material ID</label>
        <input type="text"
               name="material_id"
               placeholder="e.g. mat_001">
      </div>

      <!-- BUTTON -->
      <div style="text-align:center; margin-top:25px;">
        <button type="submit" class="action-btn">
          Add
        </button>
      </div>

    </form>
    </div>
  </div>

</main>
</div>

<script>
const ruleType = document.getElementById('ruleType');
const materialGroup = document.getElementById('materialGroup');

function toggleMaterial() {
    if (ruleType.value === 'RECYCLE') {
        materialGroup.style.display = 'block';
    } else {
        materialGroup.style.display = 'none';
    }
}

ruleType.addEventListener('change', toggleMaterial);
toggleMaterial();
</script>

<?php include "admin_footer.php"; ?>
