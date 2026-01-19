<aside class="user-sidebar collapsed" id="userSidebar">

  <div class="user-sidebar-profile">
    <img class="user-profile-avatar"
         src="<?php echo htmlspecialchars($profileImg); ?>"
         alt="Profile">

    <div class="user-profile-text">
      <strong><?php echo htmlspecialchars($user["name"]); ?></strong>
      <small><?php echo htmlspecialchars($user["role"]); ?></small>
      <a href="../profile/profile.php">View Profile</a>
    </div>
  </div>

  <div class="user-sidebar-menu">
    <a href="user_dashboard.php" class="user-menu-item active">🏠 Home</a>
    <a href="../user/user_event.php" class="user-menu-item">📅 Events</a>
    <a href="../user/rewards.php" class="user-menu-item">🏆 Rewards</a>
    <a href="../user/profile.php" class="user-menu-item">👤 Profile</a>
    <a href="../user/friends.php" class="user-menu-item">👥 Friends</a>

    <a href="../login/logout.php"
       class="user-menu-item logout"
       onclick="return confirm('Logout now?');">
      🚪 Logout
    </a>
</div>

</aside>
