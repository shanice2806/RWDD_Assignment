<?php 
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $page_title ?? 'Admin Panel' ?></title>

  <link rel="stylesheet" href="../css/style.css?v=<?= time() // use latest version of css?>">
  <link rel="stylesheet" href="../css/admin.css?v=<?= time() ?>">
</head>

<body>

<header class="top-bar">
  <div class="left">
    <button class="menu-toggle" id="menuToggle">☰</button>
    <span><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?> </span>
  </div>

  <div class="icons">
    <a href="admin_dashboard.php" class="icon-btn">🏠</a>
    <a href="../login/logout.php"class="icon-btn" onclick="return confirm('Logout now?');">
      ⎋</a>
  </div>
</header>

<?php include "admin_sidebar.php"; ?>