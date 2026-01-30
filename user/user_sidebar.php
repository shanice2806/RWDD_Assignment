
<aside class="user-sidebar collapsed" id="userSidebar">

  <div class="user-sidebar-profile">
    <img class="user-profile-avatar"
         src="<?php echo ($profileImg); ?>"
         alt="Profile">

    <div class="user-profile-text">
      <strong><?php echo ($user["name"]); ?></strong>

<?php $role = strtolower(trim($_SESSION["role"] ?? "")); ?>

<?php if ($role === "event organizer"): ?>

<a href="/RWDD_Assignment/event organizer/switch_to_organizer.php"
   class="switch-role-btn">
  🔄 Switch to Event Organizer
</a>

<?php endif; ?>

      <a href="../user/user_profile.php">View Profile</a>
    </div>
  </div>

  <div class="user-sidebar-menu">
    <a href="user_dashboard.php" class="user-menu-item">🏠 Home</a>
    <a href="../user/user_profile.php" class="user-menu-item">👤 Profile</a>
    <a href="user_reward.php" class="user-menu-item">🏆 Rewards</a>
    <a href="../user/friends.php" class="user-menu-item">👥 Friends</a>
    <a href="../user/user_event.php" class="user-menu-item">📅 Events</a>
    <a href="../user/user_recycle.php" class="user-menu-item">♻️ Recycle</a>
    <a href="../user/user_community.php" class="user-menu-item">🏛️ Community</a>
    <a href="../user/tutorial_view.php" class="user-menu-item">⚒️ Tutorial</a>
    


    <a href="../login/logout.php"
       class="user-menu-item logout"
       onclick="return confirm('Logout now?');">
      🚪 Logout
    </a>
  </div>

</aside>
