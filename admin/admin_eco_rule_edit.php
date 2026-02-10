<?php
session_start();
require_once "../connect.php";

$page_title = "Edit Eco Point Rule";
include "admin_header.php";

if (!isset($_GET['id'])) {
    header("Location: admin_system_settings.php?view=eco_rules");
    exit();
}

$rule_id = $_GET['id'];


$stmt = $conn->prepare("
    SELECT rule_id,
           rule_key,
           rule_type,
           description,
           points_per_kg,
           is_active,
           material_id
    FROM eco_points_rules
    WHERE rule_id = ?
");
$stmt->bind_param("s", $rule_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_system_settings.php?view=eco_rules");
    exit();
}

$rule = $result->fetch_assoc();


$success = "";
$error   = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rule_key      = trim($_POST['rule_key']);
    $rule_type     = $_POST['rule_type'];
    $description   = trim($_POST['description']);
    $points_per_kg = (int) $_POST['points_per_kg'];
    $material_id   = $rule_type === 'RECYCLE' ? $_POST['material_id'] : NULL;
    $is_active     = (int) $_POST['is_active'];

    if ($rule_key !== '' && $points_per_kg > 0) {

        $stmt = $conn->prepare("
            UPDATE eco_points_rules
            SET rule_key = ?,
                rule_type = ?,
                description = ?,
                points_per_kg = ?,
                material_id = ?,
                is_active = ?
            WHERE rule_id = ?
        ");

        $stmt->bind_param(
            "sssisis",
            $rule_key,
            $rule_type,
            $description,
            $points_per_kg,
            $material_id,
            $is_active,
            $rule_id
        );

        if ($stmt->execute()) {
            $success = "Eco point rule updated successfully.";


            $rule['rule_key'] = $rule_key;
            $rule['rule_type'] = $rule_type;
            $rule['description'] = $description;
            $rule['points_per_kg'] = $points_per_kg;
            $rule['material_id'] = $material_id;
            $rule['is_active'] = $is_active;

        } else {
            $error = "Failed to update eco point rule.";
        }

    } else {
        $error = "Rule key and points must be valid.";
    }
}
?>

<main class="dashboard">
<div class="main-content">


  <div class="page-title-bar">
    <a href="admin_system_settings.php?view=eco_rules" class="icon-btn back-btn">↩</a>
    <h2><?= htmlspecialchars($rule_id) ?></h2>
  </div>


  <?php if (!empty($success)): ?>
    <div class="alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="profile-container">

    <form method="POST" style="background:#e5e5e5; padding:30px; border-radius:8px;">


      <div class="form-group">
        <label>Rule ID</label>
        <input type="text" value="<?= htmlspecialchars($rule['rule_id']) ?>" readonly>
      </div>


      <div class="form-group">
        <label>Rule Key</label>
        <input type="text" name="rule_key" value="<?= htmlspecialchars($rule['rule_key']) ?>" required>
      </div>


      <div class="form-group">
        <label>Rule Type</label>
        <select name="rule_type" id="ruleType" required>
          <option value="RECYCLE" <?= $rule['rule_type'] === 'RECYCLE' ? 'selected' : '' ?>>RECYCLE</option>
          <option value="EVENT" <?= $rule['rule_type'] === 'EVENT' ? 'selected' : '' ?>>EVENT</option>
          <option value="CONTENT" <?= $rule['rule_type'] === 'CONTENT' ? 'selected' : '' ?>>CONTENT</option>
        </select>
      </div>


      <div class="form-group" id="materialGroup">
        <label>Material ID</label>
        <input type="text" name="material_id" value="<?= htmlspecialchars($rule['material_id'] ?? '') ?>">
      </div>


      <div class="form-group">
        <label>Description</label>
        <input type="text" name="description" value="<?= htmlspecialchars($rule['description']) ?>">
      </div>

      <div class="form-group">
        <label>Points Per KG</label>
        <input type="number" name="points_per_kg" min="1" value="<?= $rule['points_per_kg'] ?>" required>
      </div>


      <div class="form-group">
        <label>Status</label>
        <select name="is_active">
          <option value="1" <?= $rule['is_active'] == 1 ? 'selected' : '' ?>>Active</option>
          <option value="0" <?= $rule['is_active'] == 0 ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>

      <div style="text-align:center; margin-top:25px;">
        <button type="submit" class="action-btn">Edit</button>
      </div>

    </form>

  </div>
</div>
</main>

<script>
const ruleType = document.getElementById('ruleType');
const materialGroup = document.getElementById('materialGroup');

function toggleMaterial() {
    materialGroup.style.display = ruleType.value === 'RECYCLE' ? 'block' : 'none';
}

ruleType.addEventListener('change', toggleMaterial);
toggleMaterial();
</script>

<?php include "admin_footer.php"; ?>
