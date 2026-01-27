<?php
session_start();
require_once "../connect.php";

$page_title = "Edit Eco Point Rule";
include "admin_header.php";

/* =====================
   VALIDATE ID
===================== */
if (!isset($_GET['id'])) {
    header("Location: admin_system_settings.php?view=eco_rules");
    exit();
}

$rule_id = $_GET['id'];

/* =====================
   FETCH ECO RULE
===================== */
$stmt = $conn->prepare("
    SELECT rule_id,
           rule_key,
           description,
           points,
           is_active
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

/* =====================
   HANDLE FORM SUBMIT
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rule_key    = trim($_POST['rule_key']);
    $description = trim($_POST['description']);
    $points      = (int) $_POST['points'];
    $status      = (int) $_POST['is_active'];

    if ($rule_key !== '' && $points > 0) {

        $stmt = $conn->prepare("
            UPDATE eco_points_rules
            SET rule_key = ?,
                description = ?,
                points = ?,
                is_active = ?
            WHERE rule_id = ?
        ");

        $stmt->bind_param(
            "ssiss",
            $rule_key,
            $description,
            $points,
            $status,
            $rule_id
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

<main class="dashboard">
<div class="main-content">

  <!-- PAGE TITLE BAR -->
  <div class="page-title-bar">
    <a href="admin_system_settings.php?view=eco_rules"
       class="icon-btn back-btn">↩</a>

    <h2><?php echo htmlspecialchars($rule_id); ?></h2>
  </div>

  <!-- CENTERED FORM -->
  <div class="profile-container">

    <form method="POST"
          style="
            background:#e5e5e5;
            padding:30px;
            border-radius:8px;
          ">

      <!-- RULE ID (READ ONLY) -->
      <div class="form-group">
        <label>Rule ID</label>
        <input type="text"
               value="<?= htmlspecialchars($rule['rule_id']) ?>"
               readonly>
      </div>

      <!-- RULE KEY -->
      <div class="form-group">
        <label>Rule Key</label>
        <input type="text"
               name="rule_key"
               value="<?= htmlspecialchars($rule['rule_key']) ?>"
               required>
      </div>

      <!-- DESCRIPTION -->
      <div class="form-group">
        <label>Rule Description</label>
        <input type="text"
               name="description"
               value="<?= htmlspecialchars($rule['description']) ?>">
      </div>

      <!-- POINTS -->
      <div class="form-group">
        <label>Points</label>
        <input type="number"
               name="points"
               min="1"
               value="<?= $rule['points'] ?>"
               required>
      </div>

      <!-- STATUS -->
      <div class="form-group">
        <label>Status</label>
        <select name="is_active">
          <option value="1" <?= $rule['is_active'] == 1 ? 'selected' : '' ?>>
            Active
          </option>
          <option value="0" <?= $rule['is_active'] == 0 ? 'selected' : '' ?>>
            Inactive
          </option>
        </select>
      </div>


        <!-- EDIT BUTTON -->
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
