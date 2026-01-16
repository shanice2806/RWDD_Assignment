<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "RWDD_Assignment";

$conn = new mysqli($host, $user, $pass, $db);
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
}
?>
