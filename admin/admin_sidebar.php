<?php include "../connect.php"; ?>

<aside class="admin-sidebar" id="adminSidebar">

  <div class="sidebar-profile">
    <div class="profile-avatar">

  <?php if (!empty($_SESSION['profile_image'])): ?>
  <img 
    src="../images/profile/<?= htmlspecialchars($_SESSION['profile_image']) ?>" 
    alt="Profile"
  >
<?php else: ?>
  <img 
    src="../images/profile/default.png" 
    alt="Profile"
  >
<?php endif; ?>

</div>



    <div class="profile-text">
      <strong><?= htmlspecialchars($_SESSION['name'] ?? 'User'); ?></strong>
      <a href="view_profile.php">View Profile</a>
    </div>
  </div>

  <nav class="sidebar-menu">
    <a href="admin_dashboard.php" class="menu-item menu-first">📊 Dashboard</a>
    <a href="admin_manage_user.php" class="menu-item">👤 Manage User</a>
    <a href="admin_recycling_log.php" class="menu-item">♻️ Recycling Log</a>
    <a href="admin_manage_post.php" class="menu-item">📝 Manage Post</a>
    <a href="admin_event.php" class="menu-item">📅 Recycling Event</a>
    <a href="admin_rewards.php" class="menu-item">🎁 Rewards</a>
    <a href="admin_announcement.php" class="menu-item">📢 Announcement</a>
    <a href="admin_system_settings.php" class="menu-item">⚙️ System Settings</a>
  </nav>

</aside>