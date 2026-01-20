<?php 
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $page_title ?? 'Admin Panel' ?></title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>

<body>

<header class="top-bar">
  <div class="left">
    <button class="menu-toggle" id="menuToggle">☰</button>
    <span>Welcome!  <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?> </span>
  </div>

  <div class="search-container">
    <input type="text" class="search-bar" placeholder="Search...">
    <button class="search-btn">🔍</button>
  </div>

  <div class="icons">
    <a href="admin_dashboard.php" class="icon-btn">🏠</a>
    <a href="logout.php" class="icon-btn">⎋</a>
  </div>
</header>

<?php include "admin_sidebar.php"; ?>
