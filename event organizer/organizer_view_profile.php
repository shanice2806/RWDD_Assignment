<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protect access
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

include '../connect.php';

$user_id = $_SESSION["user_id"];

// Fetch user profile
$userStmt = $conn->prepare("
    SELECT user_id, name, email, role,
           created_at, account_status, last_login, profile_image
    FROM users
    WHERE user_id = ?
");
$userStmt->bind_param("s", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
  <style>
    .profile-container {
      max-width: 600px;
      margin: 0 auto;
    }
    
    .profile-avatar-large {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      background: #e9ecef;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 80px;
      margin: 0 auto 30px;
      overflow: hidden;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .profile-avatar-large img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .profile-form {
      background: white;
      border-radius: 8px;
      padding: 30px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: #333;
      font-size: 14px;
    }
    
    .form-group input {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
      background: #f8f9fa;
      color: #666;
    }
    
    .form-group input[readonly] {
      cursor: not-allowed;
    }
  </style>
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <div class="main-content">
    <div class="event-management-container">
      <div class="page-header">
        <h1 class="page-title">My Profile</h1>
        <a href="organizer_dashboard.php" class="action-btn">Back to Dashboard</a>
      </div>

      <div class="profile-container">
        <!-- AVATAR -->
        <div class="profile-avatar-large">
          <?php if (!empty($user['profile_image'])): ?>
            <img src="../images/profile/<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile">
          <?php else: ?>
            👤
          <?php endif; ?>
        </div>

        <!-- ACTION -->
        <div style="text-align: center; margin-bottom: 30px;">
          <a href="edit_profile.php" class="action-btn" style="padding: 12px 32px;">
            Edit Profile
          </a>
        </div>

        <!-- PROFILE FORM -->
        <form class="profile-form">

          <div class="form-group">
            <label>User ID:</label>
            <input type="text" value="<?= htmlspecialchars($user['user_id']) ?>" readonly>
          </div>

          <div class="form-group">
            <label>Name:</label>
            <input type="text" value="<?= htmlspecialchars($user['name']) ?>" readonly>
          </div>

          <div class="form-group">
            <label>Email:</label>
            <input type="text" value="<?= htmlspecialchars($user['email']) ?>" readonly>
          </div>

          <div class="form-group">
            <label>Role:</label>
            <input type="text" value="<?= htmlspecialchars($user['role']) ?>" readonly>
          </div>
          
          <div class="form-group">
            <label>Last Login:</label>
            <input type="text" value="<?= htmlspecialchars($user['last_login'] ?? 'N/A') ?>" readonly>
          </div>

          <div class="form-group">
            <label>Account Created On:</label>
            <input type="text" value="<?= htmlspecialchars($user['created_at']) ?>" readonly>
          </div>

          <div class="form-group">
            <label>Account Status:</label>
            <input type="text" value="<?= htmlspecialchars($user['account_status']) ?>" readonly>
          </div>

        </form>

      </div>
    </div>
  </div>

  <?php include 'organizer_footer.php'; ?>
</body>
</html>
