<?php
session_start();

// If the user is logged in (i.e., an admin session exists),
// redirect them to the dashboard.
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
} else {
    // Otherwise, redirect them to the login page.
    header('Location: login.php');
    exit();
}
?>