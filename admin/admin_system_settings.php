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
   BUILD QUERY
   ===================== */
$where  = [];
$params = [];
$types  = "";

/* Search by name */
if ($search !== '') {
    $where[] = "name LIKE ?";
    $params[] = "%$search%";
    $types   .= "s";
}

/* Filter by status */
if ($status === 'active') {
    $where[] = "is_active = 1";
} elseif ($status === 'inactive') {
    $where[] = "is_active = 0";
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* =====================
   FETCH DATA
   ===================== */
if ($view === 'materials') {
    $sql = "
        SELECT material_id AS id,
               materials_name AS name,
               material_description AS description,
               is_active
        FROM materials
        $where_sql
        ORDER BY material_id
    ";
} else {
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
         FILTER FORM
         ===================== -->
   <form method="GET"
      style="display:flex; gap:12px; flex-wrap:wrap; margin:20px 0;">

  <!-- View -->
  <select name="view">
    <option value="categories" <?= $view === 'categories' ? 'selected' : '' ?>>
      Content Categories
    </option>
    <option value="materials" <?= $view === 'materials' ? 'selected' : '' ?>>
      Materials
    </option>
  </select>

  <!-- Search -->
  <input type="text"
         name="search"
         placeholder="Search by name"
         value="<?= htmlspecialchars($search) ?>">

  <!-- Status -->
  <select name="status">
    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Status</option>
    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
  </select>

  <!-- Filter (SAME SIZE AS INPUTS) -->
  <button type="submit"
          style="
            height: 38px;
            padding: 0 16px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background-color: #3ba99c;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
          ">
    Apply
  </button>

   <?php if ($view === 'materials'): ?>
    <a href="admin_material_add.php"
       style="
         height:38px;
         padding:0 16px;
         display:inline-flex;
         align-items:center;
         justify-content:center;
         border-radius:6px;
         background-color: #3ba99c;
         color:#fff;
         font-weight:600;
         text-decoration:none;
       ">
      + Add Material
    </a>
  <?php else: ?>
    <a href="admin_content_category_add.php"
       style="
         height:38px;
         padding:0 16px;
         display:inline-flex;
         align-items:center;
         justify-content:center;
         border-radius:6px;
         background-color: #3ba99c;
         color:#fff;
         font-weight:600;
         text-decoration:none;
       ">
      + Add Category
    </a>
  <?php endif; ?>

</form>


    <!-- =====================
         TABLE
         ===================== -->
    <table class="eco-table">
      <thead>
        <tr>
          <th><?= $view === 'materials' ? 'Material ID' : 'Category ID' ?></th>
          <th><?= $view === 'materials' ? 'Material Name' : 'Category Name' ?></th>
          <th>Description</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>

      <tbody>
        <?php if ($data->num_rows > 0): ?>
          <?php while ($row = $data->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['id']) ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td>
                <?= $row['description']
                    ? htmlspecialchars($row['description'])
                    : '<em>—</em>' ?>
              </td>
              <td><?= $row['is_active'] ? 'Active' : 'Inactive' ?></td>
              <td>
                <?php if ($view === 'materials'): ?>
                  <a href="admin_material_edit.php?id=<?= urlencode($row['id']) ?>"
                     class="action-btn">Edit</a>
                <?php else: ?>
                  <a href="admin_content_category_edit.php?id=<?= urlencode($row['id']) ?>"
                     class="action-btn">Edit</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="text-align:center;">No results found</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

  </div>
</main>

<?php include "admin_footer.php"; ?>
