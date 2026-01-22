<?php
session_start();
require_once "../connect.php";

$page_title = "Eco Points Transaction History";
include "admin_header.php";

/* =====================
   VALIDATE USER ID
===================== */
if (!isset($_GET['id'])) {
    header("Location: admin_manage_user.php");
    exit();
}

$user_id = $_GET['id'];

/* =====================
   FETCH USER DETAILS
===================== */
$stmt = $conn->prepare("
    SELECT user_id, name, email, eco_points, badges
    FROM users
    WHERE user_id = ?
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
    die("User not found.");
}

$user = $user_result->fetch_assoc();

/* =====================
   FETCH ECO POINT TRANSACTIONS
===================== */
$stmt = $conn->prepare("
    SELECT transaction_id,
           source_id,
           points_change,
           description,
           created_at
    FROM eco_points_transactions
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$transactions = $stmt->get_result();
?>

<main class="dashboard">
<div class="main-content">

<!-- PAGE TITLE BAR -->
<div class="page-title-bar">
    <a href="admin_user_view.php?id=<?= urlencode($user_id) ?>"
       class="icon-btn back-btn">↩</a>
    <h2>Eco Points Transaction History</h2>
</div>

<!-- =====================
     USER DETAILS
===================== -->
<div class="profile-container" style="max-width: 1100px;">

  <div style="background:#e5e5e5; padding:25px; border-radius:8px; margin-bottom:30px;">
    <h3 style="margin-bottom:15px;">User Details</h3>

    <div class="form-group">
      <label>User ID</label>
      <input type="text" value="<?= htmlspecialchars($user['user_id']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Name</label>
      <input type="text" value="<?= htmlspecialchars($user['name']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Email</label>
      <input type="text" value="<?= htmlspecialchars($user['email']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Total Eco Points</label>
      <input type="text" value="<?= htmlspecialchars($user['eco_points']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Badges Earned</label>
      <input type="text" value="<?= htmlspecialchars($user['badges']) ?>" readonly>
    </div>
  </div>

  <!-- =====================
       TRANSACTION HISTORY
  ===================== -->
  <div style="background:#e5e5e5; padding:25px; border-radius:8px;">
    <h3 style="margin-bottom:15px;">Transaction History</h3>

    <table class="eco-table">
      <thead>
        <tr>
          <th>Transaction ID</th>
          <th>Source ID</th>
          <th>Points Change</th>
          <th>Date</th>
          <th>Description</th>
        </tr>
      </thead>

      <tbody>
        <?php if ($transactions->num_rows > 0): ?>
          <?php while ($row = $transactions->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['transaction_id']) ?></td>
              <td><?= htmlspecialchars($row['source_id']) ?></td>
              <td>
                <?= $row['points_change'] > 0 ? '+' : '' ?>
                <?= $row['points_change'] ?>
              </td>
              <td><?= htmlspecialchars($row['created_at']) ?></td>
              <td><?= htmlspecialchars($row['description']) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="text-align:center;">
              No transaction records found.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

  </div>

</div>

</div>
</main>

<?php include "admin_footer.php"; ?>
