<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
require '../config.php';

$token = $_POST['token'] ?? '';
$pass  = $_POST['password'] ?? '';
$pass2 = $_POST['password2'] ?? '';

if (empty($token)) {
    header('Location: ../login.php?error=' . urlencode('Invalid password reset request.'));
    exit();
}

if (strlen($pass) < 8) {
    header('Location: ../reset_password.php?token=' . urlencode($token) . '&error=' . urlencode('Password must be at least 8 characters.'));
    exit();
}
if ($pass !== $pass2) {
    header('Location: ../reset_password.php?token=' . urlencode($token) . '&error=' . urlencode('Passwords do not match.'));
    exit();
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Validate the token and ensure it hasn't expired
    $stmt = $conn->prepare("SELECT admin_id FROM ADMINISTRATOR WHERE password_reset_token = :token AND password_reset_expires_at > NOW()");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: ../login.php?error=' . urlencode('Invalid or expired password reset link. Please request a new one.'));
        exit();
    }

    // Hash the new password
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    // Update the password and clear the reset token/expiry
    $upd = $conn->prepare("UPDATE ADMINISTRATOR SET password_hash = :password_hash, password_reset_token = NULL, password_reset_expires_at = NULL WHERE admin_id = :id");
    $upd->execute(['password_hash' => $hashed_password, 'id' => $user['admin_id']]);

    header('Location: ../login.php?success=' . urlencode('Your password has been reset successfully. You can now log in.'));
    exit();

} catch (Exception $e) {
    // Log the error for debugging purposes
    // error_log('Password reset processing failed: ' . $e->getMessage());
    header('Location: ../reset_password.php?token=' . urlencode($token) . '&error=' . urlencode('An error occurred during password reset. Please try again.'));
    exit();
}
