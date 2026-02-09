<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// Check if user is an organizer
// ========================================
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

include '../connect.php';

/* Validate ID */
if (!isset($_GET['id'])) {
    die("User ID not provided.");
}

$user_id = $_GET['id'];

/* Fetch user details */
$stmt = $conn->prepare("
    SELECT user_id, name, email, role, eco_points, badges,
           created_at, account_status, last_login, profile_image
    FROM users
    WHERE user_id = ?
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();

/* Profile Image Logic */
$profileImage = !empty($user['profile_image']) ? $user['profile_image'] : 'default.jpeg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View User Profile</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <div class="main-content">
    <div class="back-btn-container">
      <a href="javascript:history.back()" class="action-btn">← Back</a>
    </div>

    <main class="dashboard">
      <!-- Page Title -->
      <div class="page-title-bar">
        <h2>User Profile: <?= htmlspecialchars($user['name']) ?></h2>
      </div>

      <!-- Profile Card -->
      <div class="section-preview" style="max-width:700px; margin:auto;">

        <!-- Profile Image -->
        <div style="text-align:center; margin-bottom:20px;">
          <img src="../images/profile/<?= htmlspecialchars($profileImage) ?>"
               alt="Profile Photo"
               style="
                  width:120px;
                  height:120px;
                  border-radius:50%;
                  object-fit:cover;
                  border: 3px solid #3ba99c;
               ">
        </div>

        <!-- User Details -->
        <div class="form-group">
          <label>Name</label>
          <input type="text" value="<?= htmlspecialchars($user['name']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>User ID</label>
          <input type="text" value="<?= htmlspecialchars($user['user_id']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="text" value="<?= htmlspecialchars($user['email']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Role</label>
          <input type="text" value="<?= htmlspecialchars($user['role']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Eco Points</label>
          <input type="text" value="<?= htmlspecialchars($user['eco_points']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Badges</label>
          <input type="text" value="<?= htmlspecialchars($user['badges'] ?? 'None') ?>" readonly>
        </div>

        <div class="form-group">
          <label>Last Login</label>
          <input type="text" value="<?= htmlspecialchars($user['last_login'] ?? 'Never') ?>" readonly>
        </div>

        <div class="form-group">
          <label>Account Created On</label>
          <input type="text" value="<?= htmlspecialchars($user['created_at']) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Account Status</label>
          <input type="text" value="<?= htmlspecialchars($user['account_status']) ?>" readonly>
        </div>

      </div>

    </main>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
