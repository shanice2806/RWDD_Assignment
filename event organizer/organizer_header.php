<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Get user's name from session (or set default)
$_SESSION['name'] = $_SESSION['name'] ?? 'User (Test)';
?>
<header class="top-bar">
  <!-- Left side: hamburger menu + user name -->
  <div class="top-left">
    <button id="sidebar-toggle" class="icon-btn">☰</button>
    <span class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></span>
  </div>

  <!-- Center: search bar -->
  <div class="search-container">
    <input type="text" class="search-bar" placeholder="Search events...">
    <button class="search-btn">🔍</button>
  </div>

  <!-- Right side: home and exit icons -->
  <div class="icons">
    <a href="organizer_dashboard.php" class="icon-btn">🏠</a>
    <a href="../guest.html" class="icon-btn">⎋</a>
  </div>
</header>

<?php include "organizer_sidebar.php"; ?>
<script src="/RWDD_Assignment/event%20organizer/organizer.js"></script>
