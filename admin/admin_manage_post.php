<?php
include "../connect.php";

$page_title = "Manage Posts";
include "admin_header.php";

$total_posts = $conn->query("
    SELECT COUNT(*) AS total 
    FROM posts
")->fetch_assoc()['total'];

$total_reported = $conn->query("
    SELECT COUNT(*) AS total 
    FROM posts 
    WHERE report_count > 0 OR is_flagged = 1
")->fetch_assoc()['total'];


$total_active = $conn->query("
    SELECT COUNT(*) AS total 
    FROM posts 
    WHERE post_status = 'Public'
")->fetch_assoc()['total'];


$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where  = [];
$params = [];
$types  = "";


if ($search !== '') {
    $where[] = "(p.title LIKE ? OR p.user_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= "ss";
}

if ($status !== '') {

    if ($status === 'Reported') {
        $where[] = "(p.report_count > 0 OR p.is_flagged = 1)";
    } else {

        $where[] = "p.post_status = ?";
        $params[] = $status;
        $types   .= "s";
    }

}

$sql = "
    SELECT
        p.post_id,
        p.user_id,
        p.title,
        p.post_created_at,
        p.post_status,
        p.report_count,
        p.is_flagged
    FROM posts p
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.post_created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="main-content">
<main class="dashboard">

<h2 class="page-title">Manage Posts</h2>

<h3>📊 INSIGHTS OVERVIEW</h3>

<div class="card-grid">

  <div class="card">
    <div class="icon">📝</div>
    <h4>Total Posts</h4>
    <p><strong><?= $total_posts ?></strong></p>
  </div>

  <div class="card">
    <div class="icon">⚠️</div>
    <h4>Reported Posts</h4>
    <p><strong><?= $total_reported ?></strong></p>
  </div>

  <div class="card">
    <div class="icon">✅</div>
    <h4>Active Posts</h4>
    <p><strong><?= $total_active ?></strong></p>
  </div>

</div>


<div class="announcement-search-wrapper">
  <form method="GET" class="announcement-search-form">

    <input
      type="text"
      name="search"
      value="<?= htmlspecialchars($search) ?>"
      placeholder="Search post title or user ID"
      class="announcement-search-input"
    >

    <select name="status" class="announcement-search-input">
      <option value="">All Posts</option>
      <option value="Public" <?= $status === 'Public' ? 'selected' : '' ?>>Public</option>
      <option value="Hidden" <?= $status === 'Hidden' ? 'selected' : '' ?>>Hidden</option>
      <option value="Reported" <?= $status === 'Reported' ? 'selected' : '' ?>>Reported</option>
    </select>

    <button type="submit" class="announcement-search-btn">🔍</button>

  </form>
</div>

<table class="eco-table">
  <thead>
    <tr>
      <th>No.</th>
      <th>Post ID</th>
      <th>Title</th>
      <th>User ID</th>
      <th>Date Posted</th>
      <th>Status</th>
      <th>Reports</th>
      <th>Action</th>
    </tr>
  </thead>

  <tbody>
    <?php if ($result->num_rows > 0): ?>
      <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($row['post_id']) ?></td>
          <td><?= htmlspecialchars($row['title']) ?></td>
          <td><?= htmlspecialchars($row['user_id']) ?></td>
          <td><?= date("Y-m-d H:i", strtotime($row['post_created_at'])) ?></td>

          <td><?= htmlspecialchars($row['post_status']) ?></td>

          <td><?= (int)$row['report_count'] ?></td>

          <td class="action-col">
            <a href="admin_post_view.php?id=<?= $row['post_id'] ?>" class="btn">View</a>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td colspan="8" style="text-align:center;">No records found</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

</main>
</div>

<?php include "admin_footer.php"; ?>
