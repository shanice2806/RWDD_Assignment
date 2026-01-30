
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
    <h2>Menu</h2>
    <a href="user_reward.php" class="user-menu-item">🎁 Rewards</a>
    <a href="../user/add_friends.php" class="user-menu-item">👥 Chats</a>
    <a href="../user/user_event.php" class="user-menu-item">📅 Events</a>
    <a href="../user/user_recycle.php" class="user-menu-item">♻️ Recycle</a>
    <a href="../user/user_community.php" class="user-menu-item">🏛️ Community</a>
    <a href="../user/tutorial_view.php" class="user-menu-item">⚒️ Tutorial</a>
    <a href="../user/point_history.php" class="user-menu-item">💰Points History</a>
    <h2>Dashboard</h2>
    <a href="../user/user_ranking.php" class="user-menu-item">🏆Ranking</a>
    <h2>Account</h2>
    <a href="../user/user_profile.php" class="user-menu-item">👤 Profile</a>

    <a href="../login/logout.php"
       class="user-menu-item logout" style="margin-top: 60px;"
       onclick="return confirm('Logout now?');">
      🚪 Logout
    </a>
  </div>

</aside>
