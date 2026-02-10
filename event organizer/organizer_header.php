<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}


$_SESSION['name'] = $_SESSION['name'] ?? 'User (Test)';
?>
<header class="top-bar">
 
  <div class="top-left">
    <button id="sidebar-toggle" class="icon-btn">☰</button>
    <span class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></span>
  </div>


  <div class="icons">
    <a href="organizer_dashboard.php" class="icon-btn">🏠</a>
    <a href="../guest.html" class="icon-btn">⎋</a>
  </div>
</header>

<?php include "organizer_sidebar.php"; ?>
<script src="/RWDD_Assignment/event%20organizer/organizer.js"></script>
