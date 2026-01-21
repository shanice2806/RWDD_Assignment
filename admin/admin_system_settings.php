<?php
session_start();
require_once "../connect.php";

$page_title = "System Settings";
include "admin_header.php";

/* =====================
   GET FILTER VALUES
===================== */
$view   = $_GET['view'] ?? 'categories';
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';

/* =====================
   BUILD QUERY CONDITIONS
===================== */
$where  = [];
$params = [];
$types  = "";

/* Search */
if ($search !== '') {
    if ($view === 'eco_rules') {
        $where[] = "rule_key LIKE ?";
    } else {
        $where[] = "name LIKE ?";
    }
    $params[] = "%$search%";
    $types   .= "s";
}

/* Status */
if ($status === 'active') {
    $where[] = "is_active = 1";
} elseif ($status === 'inactive') {
    $where[] = "is_active = 0";
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* =====================
   FETCH DATA BY VIEW
===================== */
switch ($view) {

    case 'materials':
        $sql = "
            SELECT material_id AS id,
                   materials_name AS name,
                   material_description AS description,
                   is_active
            FROM materials
            $where_sql
            ORDER BY material_id
        ";
        break;

    case 'badges':
        $sql = "
            SELECT badge_id AS id,
                   badge_name AS name,
                   description,
                   required_points,
                   icon_path,
                   is_active
            FROM badges
            $where_sql
            ORDER BY required_points
        ";
        break;

    case 'eco_rules':
        $sql = "
            SELECT rule_id AS id,
                   rule_key,
                   description,
                   points,
                   is_active
            FROM eco_points_rules
            $where_sql
            ORDER BY rule_id
        ";
        break;

    default:
        $sql = "
            SELECT content_category_id AS id,
                   content_name AS name,
                   content_description AS description,
                   is_active
            FROM content_categories
            $where_sql
            ORDER BY content_category_id
        ";
}

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$data = $stmt->get_result();
?>

<main class="dashboard">
<div class="main-content">

<h2>System Settings</h2>

<!-- =====================
     CHOOSE TABLE
===================== -->
<div class="announcement-search-wrapper">
<form method="GET" class="announcement-search-form choose-table-form">

  <label class="choose-table-label">Choose Table</label>

  <select name="view" class="announcement-search-input"
          onchange="this.form.submit()">
    <option value="badges" <?= $view === 'badges' ? 'selected' : '' ?>>Badges</option>
    <option value="categories" <?= $view === 'categories' ? 'selected' : '' ?>>Content Categories</option>
    <option value="eco_rules" <?= $view === 'eco_rules' ? 'selected' : '' ?>>Eco-Rules</option>
    <option value="materials" <?= $view === 'materials' ? 'selected' : '' ?>>Materials</option>
  </select>

</form>
</div>

<!-- =====================
     SEARCH & FILTER BAR
===================== -->
<div class="announcement-search-wrapper">
<form method="GET" class="announcement-search-form">

  <input type="hidden" name="view" value="<?= $view ?>">

  <!-- Search -->
  <input
    type="text"
    name="search"
    value="<?= htmlspecialchars($search) ?>"
    placeholder="Search by name"
    class="announcement-search-input"
  >

  <!-- Status -->
  <select name="status" class="announcement-search-input">
    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Status</option>
    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
  </select>

  <!-- Search Button -->
  <button type="submit" class="announcement-search-btn">🔍</button>

  <!-- ADD BUTTON (DYNAMIC) -->
  <?php if ($view === 'materials'): ?>
    <a href="admin_material_add.php" class="announcement-search-btn">＋</a>

  <?php elseif ($view === 'badges'): ?>
    <a href="admin_badge_add.php" class="announcement-search-btn">＋</a>

  <?php elseif ($view === 'eco_rules'): ?>
    <a href="admin_eco_rule_add.php" class="announcement-search-btn">＋</a>

  <?php else: ?>
    <a href="admin_content_category_add.php" class="announcement-search-btn">＋</a>
  <?php endif; ?>

</form>
</div>


<!-- =====================
     TABLE
===================== -->
<table class="eco-table">
<thead>
<tr>
<?php if ($view === 'badges'): ?>
  <th>Badge ID</th>
  <th>Name</th>
  <th>Description</th>
  <th>Points</th>
  <th>Icon</th>
  <th>Status</th>
<?php elseif ($view === 'eco_rules'): ?>
  <th>Rule ID</th>
  <th>Rule Key</th>
  <th>Description</th>
  <th>Points</th>
  <th>Status</th>
<?php else: ?>
  <th>ID</th>
  <th>Name</th>
  <th>Description</th>
  <th>Status</th>
<?php endif; ?>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php if ($data->num_rows > 0): while ($row = $data->fetch_assoc()): ?>
<tr>

<?php if ($view === 'badges'): ?>
  <td><?= $row['id'] ?></td>
  <td><?= htmlspecialchars($row['name']) ?></td>
  <td><?= htmlspecialchars($row['description']) ?></td>
  <td><?= $row['required_points'] ?></td>
  <td><img src="<?= $row['icon_path'] ?>" width="40"></td>
  <td><?= $row['is_active'] ? 'Active' : 'Inactive' ?></td>

<?php elseif ($view === 'eco_rules'): ?>
  <td><?= $row['id'] ?></td>
  <td><?= htmlspecialchars($row['rule_key']) ?></td>
  <td><?= htmlspecialchars($row['description']) ?></td>
  <td><?= $row['points'] ?></td>
  <td><?= $row['is_active'] ? 'Active' : 'Inactive' ?></td>

<?php else: ?>
  <td><?= $row['id'] ?></td>
  <td><?= htmlspecialchars($row['name']) ?></td>
  <td><?= htmlspecialchars($row['description']) ?></td>
  <td><?= $row['is_active'] ? 'Active' : 'Inactive' ?></td>
<?php endif; ?>

<td>
<?php if ($view === 'materials'): ?>
  <a href="admin_material_edit.php?id=<?= $row['id'] ?>" class="action-btn">Edit</a>
<?php elseif ($view === 'badges'): ?>
  <a href="admin_badge_edit.php?id=<?= $row['id'] ?>" class="action-btn">Edit</a>
<?php elseif ($view === 'eco_rules'): ?>
  <a href="admin_eco_rule_edit.php?id=<?= $row['id'] ?>" class="action-btn">Edit</a>
<?php else: ?>
  <a href="admin_content_category_edit.php?id=<?= $row['id'] ?>" class="action-btn">Edit</a>
<?php endif; ?>
</td>

</tr>
<?php endwhile; else: ?>
<tr>
  <td colspan="7" style="text-align:center;">No results found</td>
</tr>
<?php endif; ?>
</tbody>
</table>

</div>
</main>

<?php include "admin_footer.php"; ?>
