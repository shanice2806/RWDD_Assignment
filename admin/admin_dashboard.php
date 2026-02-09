<?php
include '../connect.php';
$page_title = "Admin Dashboard";
include 'admin_header.php'; 


function getCount($conn, $query, $label) {
    $result = $conn->query($query);
    if (!$result) {
        die("Query failed for $label: " . $conn->error);
    }
    return $result->fetch_assoc()[$label];
}

$total_users = getCount($conn, "SELECT COUNT(*) as total_users FROM users", 'total_users');
$total_recycling_log = getCount($conn, "SELECT COUNT(*) as total_recycling_log FROM recycling_log", 'total_recycling_log');
$total_post = getCount($conn, "SELECT COUNT(*) as total_post FROM posts", 'total_post');
$total_events = getCount($conn, "SELECT COUNT(*) as total_events FROM events", 'total_events');
$total_flagged_log = getCount($conn, "SELECT COUNT(*) as total_flagged_log FROM recycling_log WHERE is_flagged = \"1\"", 'total_flagged_log');
$total_reported_content = getCount($conn, "SELECT COUNT(*) as total_reported_content FROM post_reports", 'total_reported_content');
?>


  <div class="main-content">
    <main class="dashboard">

        <h2>Quick View</h2> 

        <div class="card-grid">

    <div class="card">
        <div class="icon">👥</div>
        <p>Total User</p>
        <h3><?= $total_users ?></h3>
        <a href="admin_manage_user.php" class="action-btn">View All</a>
    </div>

    <div class="card">
        <div class="icon">♻️</div>
        <p>Total Recycling Log</p>
        <h3><?= $total_recycling_log ?></h3>
        <a href="admin_recycling_log.php" class="action-btn">View All</a>
    </div>

    <div class="card">
        <div class="icon">📝</div>
        <p>Total Post</p>
        <h3><?= $total_post ?></h3>
        <a href="admin_manage_post.php" class="action-btn">View All</a>
    </div>

    <div class="card">
        <div class="icon">📅</div>
        <p>Total Events</p>
        <h3><?= $total_events ?></h3>
        <a href="admin_event.php" class="action-btn">View All</a>
    </div>

    <div class="card">
        <div class="icon">🚩</div>
        <p>Total Flagged Log</p>
        <h3><?= $total_flagged_log ?></h3>
        <a href="admin_recycling_log.php?status=Invalid" class="action-btn">View All</a>
    </div>

    <div class="card">
        <div class="icon">⚠️</div>
        <p>Total Reported Content</p>
        <h3><?= $total_reported_content ?></h3>
        <a href="admin_manage_post.php?status=Reported" class="action-btn">View All</a>
    </div>

</div>


        <h2>Top 5 Users by Eco-Points</h2>
<table class="eco-table">
    <thead>
        <tr>
            <th>Rank</th>
            <th>Name</th>
            <th>User ID</th>
            <th>Total Points</th>
            <th>Badges</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        
   <?php
$top_users_query = "
        SELECT user_id, name, eco_points, badges
        FROM users
        ORDER BY eco_points DESC
        LIMIT 5
    ";
    $top_users_result = $conn->query($top_users_query);

    if ($top_users_result && $top_users_result->num_rows > 0) {
        $rank = 1;
        while ($user = $top_users_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $rank++ . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['eco_points']) . "</td>";
            echo "<td>" . htmlspecialchars($user['badges']) . "</td>";
            echo "<td>
                    <a href='admin_points_transaction.php?id=" . urlencode($user['user_id']) . "' 
                       class='action-btn'>
                       View
                    </a>
                  </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6'>No top users found.</td></tr>";}
?>


    </tbody>
</table>


     <div class="chart-section">
    <div class="chart-box">
        <h4>Monthly Recycling Log Bar Chart</h4>
        <canvas id="recyclingChart" width="500" height="300"></canvas>
    </div>
    
    <div class="chart-box">
        <h4>Monthly Event Attendance Bar Chart</h4>
        <canvas id="eventAttendanceChart" width="500" height="300"></canvas>
    </div>
</div>

</main>

<script src="chart.js"></script>


<?php include 'admin_footer.php'; ?>

