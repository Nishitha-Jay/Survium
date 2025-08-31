<?php
session_start();
require '../config.php'; // CORRECTED: Use config file, not setup

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: ../login.php?error=Email and password are required.');
    exit();
}

try {
    // Use $password_db from config.php for the connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM ADMINISTRATOR WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password_hash'])) {
        // Password is correct, start the session
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_email'] = $admin['email'];
        header('Location: ../dashboard.php');
        exit();
    } else {
        // Invalid credentials
        header('Location: ../login.php?error=Invalid email or password.');
        exit();
    }
    // after verifying password matches
    if ((int) $admin['is_verified'] !== 1) {
        header('Location: ../login.php?error=' . urlencode('Please verify your email first.'));
        exit();
    }
} catch (PDOException $e) {
    header('Location: ../login.php?error=Database error. Please try again later.');
    exit();
}
?>