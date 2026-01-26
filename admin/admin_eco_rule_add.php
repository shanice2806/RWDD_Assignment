<?php
include "../connect.php";

$page_title = "Add Eco Rule";
include "admin_header.php";

/* =====================
   HANDLE FORM SUBMIT
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rule_key     = trim($_POST['rule_key']);
    $description  = trim($_POST['description']);
    $points       = (int) $_POST['points'];

    if ($rule_key !== '' && $points > 0) {

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
            (rule_id, rule_key, description, points, is_active)
            VALUES (?, ?, ?, ?, 1)
        ");

        $stmt->bind_param(
            "sssi",
            $rule_id,
            $rule_key,
            $description,
            $points
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

  <!-- CENTERED FORM -->
  <div class="profile-container">
    <div class="form-panel">
    <form method="POST">
      <div class="form-group">
        <label>Rule Key</label>
        <input type="text"
               name="rule_key"
               placeholder="e.g. recycle_plastic"
               required>
      </div>

      <div class="form-group">
        <label>Description</label>
        <input type="text"
               name="description"
               placeholder="Points awarded for submitting a valid plastic recycling log">
      </div>

      <div class="form-group">
        <label>Points Awarded</label>
        <input type="number"
               name="points"
               min="1"
               required>
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
