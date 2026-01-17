<?php
include "../connect.php"; 

$page_title = "Recycling Log";
include "admin_header.php";

/* Total valid logs */
$total_verified = $conn->query("
    SELECT COUNT(*) AS total 
    FROM recycling_log 
    WHERE status = 'Valid'
")->fetch_assoc()['total'];

/* Flagged logs */
$total_flagged = $conn->query("
    SELECT COUNT(*) AS total 
    FROM recycling_log 
    WHERE is_flagged = 1
")->fetch_assoc()['total'];

/* Verified logs (kept separate for UI clarity) */
$total_verified_again = $total_verified;

/* Top recycled material */
$top_material_result = $conn->query("
    SELECT m.materials_name, COUNT(*) AS total
    FROM recycling_log rl
    JOIN materials m ON rl.material_id = m.material_id
    GROUP BY rl.material_id
    ORDER BY total DESC
    LIMIT 1
");

$top_material = "N/A";
if ($top_material_result && $top_material_result->num_rows > 0) {
    $top_material = $top_material_result->fetch_assoc()['materials_name'];
}

/* =====================
   GET FILTER VALUES
   ===================== */
$search   = $_GET['search']   ?? '';
$status   = $_GET['status']   ?? '';
$material = $_GET['material'] ?? '';

/* =====================
   BUILD CONDITIONS
   ===================== */
$where  = [];
$params = [];
$types  = "";

/* Search */
if ($search !== '') {
    $where[] = "(rl.user_id LIKE ? OR rl.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= "ss";
}

/* Status filter */
if ($status !== '') {
    $where[] = "rl.status = ?";
    $params[] = $status;
    $types   .= "s";
}

/* Material filter */
if ($material !== '') {
    $where[] = "rl.material_id = ?";
    $params[] = $material;
    $types   .= "s";
}

/* =====================
   BASE QUERY
   ===================== */
$sql = "
    SELECT 
        rl.log_id,
        rl.user_id,
        m.materials_name,
        rl.weight_kg,
        rl.location,
        rl.submitted_at,
        rl.status,
        rl.points_awarded,
        rl.is_flagged
    FROM recycling_log rl
    LEFT JOIN materials m 
        ON rl.material_id = m.material_id
";

/* Apply filters */
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY rl.submitted_at DESC";

/* =====================
   PREPARE & EXECUTE
   ===================== */
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="chart.js"></script>

  <div class="main-content">
    <main class="dashboard">
<h2 class="page-title">Recycling Log</h2>

<h3>📊 INSIGHTS OVERVIEW</h3>

<div class="card-grid">

  <div class="card">
    <div class="icon">✅</div>
    <h4>Total Verified Logs</h4>
    <p><strong><?= $total_verified ?></strong></p>
  </div>

  <div class="card">
    <div class="icon">⚠️</div>
    <h4>Flagged Log</h4>
    <p><strong><?= $total_flagged ?></strong></p>
  </div>

  <div class="card">
    <div class="icon">✔️</div>
    <h4>Verified Log</h4>
    <p><strong><?= $total_verified ?></strong></p>
  </div>

  <div class="card">
    <div class="icon">♻️</div>
    <h4>Top Recycled Item</h4>
    <p><strong><?= htmlspecialchars($top_material) ?></strong></p>
  </div>

</div>

<div class="chart-section">

  <!-- Bar Chart: Logs by Material -->
  <div class="chart-box">
    <h4>Logs by Material</h4>
    <canvas id="barLogsByMaterial" height="220"></canvas>
  </div>

  <!-- Line Chart: Logs Over Time -->
  <div class="chart-box">
    <h4>Logs Over Time</h4>
    <canvas id="lineLogsOverTime" height="220"></canvas>
  </div>


</div>

<!-- =====================
     SEARCH & FILTER BAR
     ===================== -->
<div class="announcement-search-wrapper">
  <form method="GET" class="announcement-search-form">

    <!-- Search -->
    <input
      type="text"
      name="search"
      value="<?= htmlspecialchars($search) ?>"
      placeholder="Search Recycling Log"
      class="announcement-search-input"
    >

    <!-- Status Filter -->
    <select name="status" class="announcement-search-input">
      <option value="">All Status</option>
      <option value="Pending"  <?= $status === 'Pending'  ? 'selected' : '' ?>>Pending</option>
      <option value="Valid" <?= $status === 'Valid' ? 'selected' : '' ?>>Valid</option>
      <option value="Invalid" <?= $status === 'Invalid' ? 'selected' : '' ?>>Invalid</option>
    </select>

    <!-- Material Filter -->
    <select name="material" class="announcement-search-input">
      <option value="">All Materials</option>
      <?php
      $materials = $conn->query("SELECT material_id, materials_name FROM materials WHERE is_active = 1");
      while ($m = $materials->fetch_assoc()):
      ?>
        <option value="<?= $m['material_id'] ?>"
          <?= $material == $m['material_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($m['materials_name']) ?>
        </option>
      <?php endwhile; ?>
    </select>

    <button type="submit" class="announcement-search-btn">🔍</button>

  </form>
</div>

<!-- =====================
     TABLE
     ===================== -->
<div class="table-wrapper">
<table class="eco-table">
  <thead>
    <tr>
      <th>No.</th>
      <th>Date</th>
      <th>User ID</th>
      <th>Material</th>
      <th>Weight (kg)</th>
      <th>Location</th>
      <th>Status</th>
      <th>Points</th>
      <th>Action</th>
    </tr>
  </thead>

  <tbody>
    <?php if ($result->num_rows > 0): ?>
      <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= date("Y-m-d", strtotime($row['submitted_at'])) ?></td>
          <td><?= htmlspecialchars($row['user_id']) ?></td>
          <td><?= htmlspecialchars($row['materials_name'] ?? 'Unknown') ?></td>
          <td><?= htmlspecialchars($row['weight_kg']) ?></td>
          <td><?= htmlspecialchars($row['location']) ?></td>

          <td>
            <?= htmlspecialchars($row['status']) ?>
            <?php if ($row['is_flagged']): ?>
              ⚠️
            <?php endif; ?>
          </td>

          <td><?= htmlspecialchars($row['points_awarded']) ?></td>

          <td class="action-col">
            <a href="admin_recycling_log_view.php?id=<?= $row['log_id'] ?>" class="btn">VIEW</a>
            <a href="admin_recycling_log_update.php?id=<?= $row['log_id'] ?>" class="btn">UPDATE</a>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td colspan="9" style="text-align:center;">No records found</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>
</div>
</div>
</main>


<?php
include "admin_footer.php";
?>