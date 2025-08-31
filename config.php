<?php
// --- DATABASE CONFIGURATION ---
// !! IMPORTANT !!
// This file holds the database credentials for the entire application.
$servername = "localhost";
$username = "root";
$password = ""; // Your database password
$dbname = "feedback_platform_db";

// --- PASSWORD VARIABLE FIX ---
// This resolves a variable naming conflict in the original login.php script.
$password_db = $password; 
?>