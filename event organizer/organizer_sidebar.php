<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user information from session
$organizerName = htmlspecialchars($_SESSION['name'] ?? 'Organizer');
$organizerRole = htmlspecialchars($_SESSION['role'] ?? 'Organizer');
$userId = $_SESSION['user_id'] ?? '';

// Fetch user profile image from database
$profileImg = '../images/profile.png'; // Default image
if (!empty($userId) && isset($conn)) {
    $profileStmt = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ?");
    $profileStmt->bind_param("s", $userId);
    $profileStmt->execute();
    $profileResult = $profileStmt->get_result();
    if ($profileResult->num_rows > 0) {
        $profileData = $profileResult->fetch_assoc();
        if (!empty($profileData['profile_image'])) {
            $profileImg = '../images/profile/' . htmlspecialchars($profileData['profile_image']);
        }
    }
    $profileStmt->close();
}
?>

<aside class="organizer-sidebar" id="sidebar">
  <!-- ============================== -->
  <!-- Profile Section -->
  <!-- ============================== -->
  <div class="organizer-sidebar-profile">
    <img class="organizer-profile-avatar" src="<?= $profileImg ?>" alt="Profile">
    <div class="organizer-profile-text">
      <strong><?= $organizerName ?></strong>
      <small class="organizer-role"><?= $organizerRole ?></small>
      <!-- Button to switch back to user mode -->
      <a href="/RWDD_Assignment/user/switch_to_user.php" class="switch-role-btn">🔄 Switch to User</a>
      <a href="organizer_view_profile.php">View Profile</a>
    </div>
  </div>

  <!-- ============================== -->
  <!-- Menu Items -->
  <!-- ============================== -->
  <div class="organizer-sidebar-menu">
    <a href="organizer_event_management.php" class="organizer-menu-item">📅 Event Management</a>
    <a href="organizer_registration_attendance_hub.php" class="organizer-menu-item">📝 Registration, Attendance & Volunteers</a>
    <a href="organizer_sustainability_hub.php" class="organizer-menu-item">🌱 Sustainability Tracking</a>
    <a href="organizer_collaboration_feedback_hub.php" class="organizer-menu-item">💬 Collaboration & Feedback</a>
    <a href="organizer_archive_showcase_hub.php" class="organizer-menu-item">📁 Archive & Showcase</a>
    <a href="organizer_view_profile.php" class="organizer-menu-item">👤 Profile</a>
    
    <!-- Logout button -->
    <a href="../login/logout.php" class="organizer-menu-item logout">🚪 Log Out</a>
  </div>
</aside>
