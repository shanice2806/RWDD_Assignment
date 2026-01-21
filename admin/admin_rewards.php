<?php
include "../connect.php";
$page_title = "Eco-Points & Rewards";
include "admin_header.php";

/* =====================
   GET PARAMETERS
===================== */
$table = $_GET['table'] ?? 'catalog';
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$stock_filter = $_GET['stock'] ?? '';
$date_filter = $_GET['date'] ?? '';

$search_esc = $conn->real_escape_string($search);

/* =====================
   ECO REWARD INSIGHTS
===================== */

// Low stock (active only)
$low_stock_items = $conn->query("
    SELECT COUNT(*) AS total
    FROM reward_catalog
    WHERE stock <= 10 AND is_active = 1
")->fetch_assoc()['total'];

// Redeemed this month
$total_redeemed_month = $conn->query("
    SELECT COUNT(*) AS total
    FROM reward_redemptions
    WHERE status = 'Approved'
    AND MONTH(redemption_date_time) = MONTH(CURRENT_DATE())
    AND YEAR(redemption_date_time) = YEAR(CURRENT_DATE())
")->fetch_assoc()['total'];

// Most redeemed reward
$most_redeemed = $conn->query("
    SELECT rc.reward_name, COUNT(rr.redemption_id) AS total
    FROM reward_redemptions rr
    JOIN reward_catalog rc ON rr.reward_id = rc.reward_id
    WHERE rr.status = 'Approved'
    GROUP BY rr.reward_id
    ORDER BY total DESC
    LIMIT 1
")->fetch_assoc();

$most_redeemed_name = $most_redeemed['reward_name'] ?? 'N/A';
?>

<main class="dashboard">
<div class="main-content">

<h2>Eco-Points & Rewards</h2>

<!-- =====================
     QUICK VIEW
===================== -->
<h2>Quick View</h2>

<div class="card-grid">

  <div class="card">
    <div class="icon">⚠️</div>
    <p>Low Stock Items</p>
    <h3><?= $low_stock_items ?></h3>
    <button onclick="location.href='eco_rewards_view.php?table=catalog&stock=low'">
      View Catalog
    </button>
  </div>

  <div class="card">
    <div class="icon">🔥</div>
    <p>Most Redeemed Reward</p>
    <h3><?= htmlspecialchars($most_redeemed_name) ?></h3>
    <button onclick="location.href='eco_rewards_view.php?table=redemptions'">
      View Redemptions
    </button>
  </div>

  <div class="card">
    <div class="icon">🎁</div>
    <p>Redeemed This Month</p>
    <h3><?= $total_redeemed_month ?></h3>
    <button onclick="location.href='eco_rewards_view.php?table=redemptions&date=this_month'">
      View Log
    </button>
  </div>

</div>

<!-- =====================
     TABLE SELECTOR
===================== -->
<div class="announcement-search-wrapper">
  <form method="GET" class="announcement-search-form choose-table-form">

    <label class="choose-table-label">Choose Table</label>

    <select name="table" class="announcement-search-input"
            onchange="this.form.submit()">
      <option value="catalog" <?= $table === 'catalog' ? 'selected' : '' ?>>
        Reward Catalog
      </option>
      <option value="redemptions" <?= $table === 'redemptions' ? 'selected' : '' ?>>
        Reward Redemptions
      </option>
    </select>

  </form>
</div>

<!-- =====================
     SEARCH & FILTER BAR
===================== -->
<div class="announcement-search-wrapper">
<form method="GET" class="announcement-search-form">

  <input type="hidden" name="table" value="<?= $table ?>">

  <!-- SEARCH -->
  <input
    type="text"
    name="search"
    value="<?= htmlspecialchars($search) ?>"
    placeholder="<?= $table === 'catalog' ? 'Search reward name' : 'Search user ID' ?>"
    class="announcement-search-input"
  >

  <?php if ($table === 'catalog'): ?>
    <select name="status" class="announcement-search-input">
      <option value="">All Status</option>
      <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Active</option>
      <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Inactive</option>
    </select>

    <select name="stock" class="announcement-search-input">
      <option value="">All Stock</option>
      <option value="low" <?= $stock_filter === 'low' ? 'selected' : '' ?>>
        Low Stock (≤10)
      </option>
    </select>
  <?php endif; ?>

  <?php if ($table === 'redemptions'): ?>
    <select name="date" class="announcement-search-input">
      <option value="">All Time</option>
      <option value="this_month" <?= $date_filter === 'this_month' ? 'selected' : '' ?>>This Month</option>
      <option value="last_month" <?= $date_filter === 'last_month' ? 'selected' : '' ?>>Last Month</option>
      <option value="7_days" <?= $date_filter === '7_days' ? 'selected' : '' ?>>Last 7 Days</option>
      <option value="30_days" <?= $date_filter === '30_days' ? 'selected' : '' ?>>Last 30 Days</option>
    </select>
  <?php endif; ?>

  <button type="submit" class="announcement-search-btn">🔍</button>

</form>
</div>

<!-- =====================
     REWARD CATALOG
===================== -->
<?php if ($table === 'catalog'): ?>

<?php
$sql = "SELECT * FROM reward_catalog WHERE 1";

if ($search !== '') {
  $sql .= " AND reward_name LIKE '%$search_esc%'";
}

if ($status_filter !== '') {
  $sql .= " AND is_active = '$status_filter'";
}

if ($stock_filter === 'low') {
  $sql .= " AND stock <= 10";
}

$sql .= " ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<h3>Reward Catalog</h3>

<table class="eco-table">
<thead>
<tr>
  <th>ID</th>
  <th>Name</th>
  <th>Description</th>
  <th>Points</th>
  <th>Stock</th>
  <th>Status</th>
  <th>Image</th>
  <th>Created</th>
</tr>
</thead>
<tbody>
<?php if ($result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
<tr>
  <td><?= $row['reward_id'] ?></td>
  <td><?= htmlspecialchars($row['reward_name']) ?></td>
  <td><?= htmlspecialchars($row['description']) ?></td>
  <td><?= $row['points_required'] ?></td>
  <td><?= $row['stock'] ?></td>
  <td><?= $row['is_active'] ? 'Active' : 'Inactive' ?></td>
  <td><img src="<?= $row['image_path'] ?>" width="60"></td>
  <td><?= $row['created_at'] ?></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="8">No rewards found.</td></tr>
<?php endif; ?>
</tbody>
</table>

<?php endif; ?>

<!-- =====================
     REWARD REDEMPTIONS
===================== -->
<?php if ($table === 'redemptions'): ?>

<?php
$sql = "SELECT * FROM reward_redemptions WHERE 1";

if ($search !== '') {
  $sql .= " AND user_id LIKE '%$search_esc%'";
}

if ($date_filter === 'this_month') {
  $sql .= " AND MONTH(redemption_date_time)=MONTH(CURRENT_DATE())
            AND YEAR(redemption_date_time)=YEAR(CURRENT_DATE())";
} elseif ($date_filter === 'last_month') {
  $sql .= " AND MONTH(redemption_date_time)=MONTH(CURRENT_DATE()-INTERVAL 1 MONTH)
            AND YEAR(redemption_date_time)=YEAR(CURRENT_DATE()-INTERVAL 1 MONTH)";
} elseif ($date_filter === '7_days') {
  $sql .= " AND redemption_date_time >= NOW() - INTERVAL 7 DAY";
} elseif ($date_filter === '30_days') {
  $sql .= " AND redemption_date_time >= NOW() - INTERVAL 30 DAY";
}

$sql .= " ORDER BY redemption_date_time DESC";
$result = $conn->query($sql);
?>

<h3>Reward Redemptions</h3>

<table class="eco-table">
<thead>
<tr>
  <th>ID</th>
  <th>User ID</th>
  <th>Reward ID</th>
  <th>Date</th>
  <th>Status</th>
  <th>Points</th>
  <th>Notes</th>
</tr>
</thead>
<tbody>
<?php if ($result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
<tr>
  <td><?= $row['redemption_id'] ?></td>
  <td><?= $row['user_id'] ?></td>
  <td><?= $row['reward_id'] ?></td>
  <td><?= $row['redemption_date_time'] ?></td>
  <td><?= $row['status'] ?></td>
  <td><?= $row['points_spent'] ?></td>
  <td><?= $row['notes'] ?? '-' ?></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="7">No records found.</td></tr>
<?php endif; ?>
</tbody>
</table>

<?php endif; ?>

<?php include "admin_footer.php"; ?>

</div>
</main>
