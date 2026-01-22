<?php
// Start session to access user data
session_start();

// Connect to database
require_once "../connect.php";

// ========================================
// STEP 1: Check if user is logged in
// ========================================
if (!isset($_SESSION["user_id"])) {
  // Not logged in? Send to login page
  header("Location: ../login/login.php");
  exit();
}

// Get the logged in user's ID
$user_id = $_SESSION["user_id"];

// ========================================
// STEP 2: Get user's role from database
// ========================================
// Prepare SQL query to get role
$sql = "SELECT role FROM users WHERE user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);

// Fill in the ? with user_id
mysqli_stmt_bind_param($stmt, "s", $user_id);

// Execute query
mysqli_stmt_execute($stmt);

// Get result
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

// Close statement
mysqli_stmt_close($stmt);

// Get role and convert to lowercase
$role = strtolower(trim($row["role"] ?? ""));

// ========================================
// STEP 3: Check if user is organizer
// ========================================
if ($role !== "organizer" && $role !== "event organizer") {
  // Not an organizer? Send back to user dashboard
  header("Location: ../user/user_dashboard.php?err=not_organizer");
  exit();
}

// ========================================
// STEP 4: User is organizer - Go to dashboard
// ========================================
header("Location: organizer_dashboard.php");
exit();
