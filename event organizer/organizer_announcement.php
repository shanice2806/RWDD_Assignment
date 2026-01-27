<?php
// ========================================
// STEP 1: Start session and check login
// ========================================
session_start();

// Only event organizers can access this page
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

// Connect to database
include '../connect.php';

// Get current user's ID
$user_id = $_SESSION['user_id'];

// ========================================
// STEP 2: Handle search
// ========================================
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// ========================================
// STEP 3: Get announcements created by this organizer
// ========================================
$query = "SELECT * FROM announcements WHERE created_by = '$user_id'";

if (!empty($search)) {
    $query .= " AND (title LIKE '%$search%' OR message LIKE '%$search%')";
}

$query .= " ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
    <?php include 'organizer_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'organizer_sidebar.php'; ?>
        
          <div class="main-content">
    <div class="back-btn-container">
      <button onclick="history.back()" class="action-btn"> Back</button>
    </div>            <!-- Page title -->
            <div class="page-header">
                <h1>Announcements</h1>
            </div>
            
            <!-- Search bar -->
            <div class="search-section">
                <form method="GET" class="announcement-search">
                    <input type="text" name="search" placeholder="Search announcements..." value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                    <button type="submit" class="search-btn">🔍</button>
                </form>
            </div>
            
            <!-- Announcements list -->
            <div class="announcements-list">
                <?php
                if ($result->num_rows > 0) {
                    while ($announcement = $result->fetch_assoc()) {
                        // Determine status label
                        $status_label = 'Draft';
                        $status_class = 'badge-info';
                        
                        if ($announcement['is_active'] == 1) {
                            $status_label = 'Published';
                            $status_class = 'badge-success';
                        } else if (strtotime($announcement['end_date']) < time()) {
                            $status_label = 'Expired';
                            $status_class = 'badge-danger';
                        }
                        ?>
                        <div class="announcement-card">
                            <div class="announcement-info">
                                <p><strong>Title:</strong> <?php echo htmlspecialchars($announcement['title']); ?></p>
                                <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($announcement['start_date'])); ?></p>
                                <p><strong>Status:</strong> <span class="badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span></p>
                            </div>
                            <div class="announcement-actions flex gap-10">
                                <a href="organizer_announcement_view.php?id=<?php echo $announcement['announcement_id']; ?>" class="action-btn">View</a>
                                <a href="organizer_announcement_edit.php?id=<?php echo $announcement['announcement_id']; ?>" class="action-btn">Edit</a>
                                <a href="organizer_announcement_delete.php?id=<?php echo $announcement['announcement_id']; ?>" class="action-btn">Delete</a>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="no-events">
                        <p>No announcements found.</p>
                        <?php if (empty($search)): ?>
                            <p>Create your first announcement to communicate with participants!</p>
                        <?php endif; ?>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <!-- Post new announcement button -->
            <div class="text-center mt-20">
                <a href="organizer_announcement_create.php" class="action-btn">Post New Announcement</a>
            </div>
        </div>
    </div>
</body>
</html>
