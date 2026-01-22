<?php
session_start();
require_once "../connect.php";

$page_title = "Reward Details";
include "admin_header.php";

/* =====================
   VALIDATE ID
===================== */
if (!isset($_GET['id'])) {
    header("Location: admin_rewards.php?table=redemptions");
    exit();
}

$redemption_id = $_GET['id'];

/* =====================
   FETCH REDEMPTION + REWARD + USER
===================== */
$stmt = $conn->prepare("
    SELECT 
        rr.redemption_id,
        rr.user_id,
        rr.reward_id,
        rr.redemption_date_time,
        rr.status,
        rr.notes,
        rr.points_spent,

        rc.reward_name,
        rc.description,
        rc.points_required,
        rc.stock,
        rc.image_path,

        u.name   AS user_name,
        u.email  AS user_email

    FROM reward_redemptions rr
    JOIN reward_catalog rc ON rr.reward_id = rc.reward_id
    JOIN users u ON rr.user_id = u.user_id
    WHERE rr.redemption_id = ?
");

$stmt->bind_param("s", $redemption_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_rewards.php?table=redemptions");
    exit();
}

$data = $result->fetch_assoc();
?>

<main class="dashboard">
<div class="main-content">

<!-- PAGE TITLE BAR -->
<div class="page-title-bar">
  <a href="admin_rewards.php?table=redemptions"
     class="icon-btn back-btn">↩</a>
  <h2><?= htmlspecialchars($data['redemption_id']); ?></h2>
</div>

<!-- =====================
     REDEMPTION INFORMATION
===================== -->
<div class="profile-container" style="max-width: 1100px;">

  <div style="background:#e5e5e5; padding:25px; border-radius:8px; margin-bottom:30px;">
    <h3 style="margin-bottom:15px;">Redemption Information</h3>

    <div class="form-group">
      <label>Redemption ID</label>
      <input type="text" value="<?= htmlspecialchars($data['redemption_id']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>User ID</label>
      <input type="text" value="<?= htmlspecialchars($data['user_id']) ?>" readonly>
    </div>

    <!-- ✅ NEW: USER NAME -->
    <div class="form-group">
      <label>User Name</label>
      <input type="text" value="<?= htmlspecialchars($data['user_name']) ?>" readonly>
    </div>

    <!-- ✅ NEW: USER EMAIL -->
    <div class="form-group">
      <label>User Email</label>
      <input type="text" value="<?= htmlspecialchars($data['user_email']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Redemption Date & Time</label>
      <input type="text" value="<?= $data['redemption_date_time'] ?>" readonly>
    </div>

    <div class="form-group">
      <label>Status</label>
      <input type="text" value="<?= $data['status'] ?>" readonly>
    </div>

    <div class="form-group">
      <label>Points Used</label>
      <input type="text" value="<?= $data['points_spent'] ?>" readonly>
    </div>

    <div class="form-group">
      <label>Notes</label>
      <input type="text" value="<?= $data['notes'] ?? '-' ?>" readonly>
    </div>
  </div>

  <!-- =====================
       REWARD DETAILS
  ===================== -->
  <div style="background:#e5e5e5; padding:25px; border-radius:8px;">
    <h3 style="margin-bottom:15px;">Reward Details</h3>

    <div class="form-group">
      <label>Reward ID</label>
      <input type="text" value="<?= htmlspecialchars($data['reward_id']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Reward Name</label>
      <input type="text" value="<?= htmlspecialchars($data['reward_name']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Description</label>
      <input type="text" value="<?= htmlspecialchars($data['description']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Points Required</label>
      <input type="text" value="<?= $data['points_required'] ?>" readonly>
    </div>

    <div class="form-group">
      <label>Current Stock</label>
      <input type="text" value="<?= $data['stock'] ?>" readonly>
    </div>

    <div class="form-group">
  <label>Reward Image</label>
  <div style="margin-top:10px;">
    <?php
        // If image_path is empty, show placeholder
        $rewardImage = !empty($data['image_path'])
            ? $data['image_path']
            : 'default.jpeg';
    ?>
    <img src="../images/reward/<?= htmlspecialchars($rewardImage) ?>"
         width="120"
         style="border-radius:6px;"
         alt="Reward Image">
  </div>
</div>

    </div>
  </div>

</div>

</div>
</main>

<?php include "admin_footer.php"; ?>
