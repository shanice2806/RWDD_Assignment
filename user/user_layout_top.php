<?php
$userName = $user["name"] ?? "User";
$userRole = $user["role"] ?? "User";
$profileImg = $profileImg ?? "../images/apu.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo ($pageTitle ?? "ReLife Hub"); ?></title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">
</head>
<body class="sidebar-collapsed">

<button class="sidebar-toggle" id="userSidebarToggle" type="button">☰</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="user-layout">
  <?php include __DIR__ . "/user_sidebar.php"; ?>
  <div class="user-content">
