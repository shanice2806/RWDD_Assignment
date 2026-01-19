<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['name'] = $_SESSION['name'] ?? 'User (Test)';
?>
<header class="top-bar">
  <!-- Left side: sidebar toggle + user name -->
  <div class="top-left">
    <button id="sidebar-toggle" class="icon-btn">☰</button>
    <span class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></span>
  </div>

  <!-- Center: search bar -->
  <div class="search-container">
    <input type="text" class="search-bar" placeholder="Search events...">
  </div>

  <!-- Right side: icons -->
  <div class="icons">
    <button class="icon-btn">👤</button>
    <a href="../login/logout.php" class="icon-btn">⎋</a>
  </div>
</header>
