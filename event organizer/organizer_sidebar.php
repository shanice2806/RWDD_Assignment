<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$organizerName = htmlspecialchars($_SESSION['name'] ?? 'Organizer');
$organizerRole = htmlspecialchars($_SESSION['role'] ?? 'Organizer');
$profileImg    = '../images/profile.png';
?>

<aside class="organizer-sidebar" id="sidebar">
  <div class="organizer-sidebar-profile">
    <img class="organizer-profile-avatar" src="<?= $profileImg ?>" alt="Profile">
    <div class="organizer-profile-text">
      <strong><?= $organizerName ?></strong>
      <small class="organizer-role"><?= $organizerRole ?></small>
      <a href="/RWDD_Assignment/user/switch_to_user.php" class="switch-role-btn">🔄 Switch to User</a>
      <a href="../profile/profile.php">View Profile</a>
    </div>
  </div>

  <div class="organizer-sidebar-menu">
    <a href="organizer_event_management.php" class="organizer-menu-item">📅 Event Management</a>
    <a href="organizer_registration_attendance_hub.php" class="organizer-menu-item">📝 Registration, Attendance & Volunteers</a>
    <a href="organizer_sustainability_hub.php" class="organizer-menu-item">🌱 Sustainability Tracking</a>
    <a href="organizer_collaboration_feedback_hub.php" class="organizer-menu-item">💬 Collaboration & Feedback</a>
    <a href="organizer_archive_showcase_hub.php" class="organizer-menu-item">📁 Archive & Showcase</a>
    <a href="organizer_profile_view.php" class="organizer-menu-item">👤 Profile</a>
    
    <a href="../login/logout.php" class="organizer-menu-item logout" onclick="return confirm('Logout now?');">🚪 Log Out</a>
  </div>
</aside>
