<?php
include "../connect.php";


/* Search logic */
$search = $_GET['search'] ?? '';

$sql = "SELECT user_id, name, email, role, account_status, eco_points, badges, created_at
        FROM users
        WHERE user_id LIKE ? OR name LIKE ? OR email LIKE ? OR role LIKE ?";

$stmt = $conn->prepare($sql);
$like = "%$search%";
$stmt->bind_param("ssss", $like, $like, $like,$like);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User</title>
    <?php include "admin_header.php";?>
</head>
<body>

<div class="main-content">

  <!-- Page Title -->
  <h2>Users</h2>

  <!-- Search Bar -->
  <div class="announcement-search-wrapper">
    <form method="GET" class="announcement-search-form">
      <input
        type="text"
        name="search"
        class="announcement-search-input"
        placeholder="Search user..."
        value="<?= htmlspecialchars($search) ?>"
      >
      <button class="announcement-search-btn">🔍</button>
    </form>
  </div>

  <!-- User Table -->
  <table class="eco-table">
    <thead>
      <tr>
        <th>No.</th>
        <th>User ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Eco Points</th>
        <th>Badges</th>
        <th>Created At</th>
        <th>Action</th>
      </tr>
    </thead>

    <tbody>
      <?php if ($result->num_rows > 0): ?>
        <?php $no = 1; ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($row['user_id']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['role']) ?></td>
            <td><?= htmlspecialchars($row['account_status']) ?></td>
            <td><?= htmlspecialchars($row['eco_points']) ?></td>
            <td><?= htmlspecialchars($row['badges']) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
            <td>
                <a href="admin_user_view.php?id=<?= $row['user_id'] ?>" class="btn">View</a>
                <a href="admin_user_edit.php?id=<?= $row['user_id'] ?>" class="btn">Edit</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="10" style="text-align:center;">No users found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

</div>

<?php include "admin_footer.php"; ?>

</body>
</html>
