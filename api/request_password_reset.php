<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
require '../config.php';
require_once '../send_mail.php';

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../forgot_password.php?error=' . urlencode('Please enter a valid email address.'));
    exit();
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Find the user by email
    $stmt = $conn->prepare("SELECT admin_id, username, email FROM ADMINISTRATOR WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Always provide a generic message to prevent email enumeration
    if (!$user) {
        header('Location: ../forgot_password.php?ok=' . urlencode('If your email is in our system, a password reset link has been sent.'));
        exit();
    }

    // Generate a unique token and set an expiration time (e.g., 1 hour from now)
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store the token and expiry in the database
    $upd = $conn->prepare("UPDATE ADMINISTRATOR SET password_reset_token = :token, password_reset_expires_at = :expires WHERE admin_id = :id");
    $upd->execute(['token' => $token, 'expires' => $expires_at, 'id' => $user['admin_id']]);

    // Construct the reset link
    $resetLink = 'https://app.survium.com/reset_password.php?token=' . urlencode($token);

    // Send the password reset email
    $subject = 'Password Reset Request for your Feedback Platform account';
    $body = "Hello " . htmlspecialchars($user['username'] ?? $user['email']) . ",<br><br>We received a request to reset the password for your account. Please click the link below to reset your password:<br><a href='$resetLink'>$resetLink</a><br><br>This link will expire in 1 hour.<br><br>If you did not request a password reset, please ignore this email.<br><br>Thank you,<br>Feedback Platform Team";

    sendMail($user['email'], $subject, $body);

    header('Location: ../forgot_password.php?ok=' . urlencode('If your email is in our system, a password reset link has been sent.'));
    exit();

} catch (Exception $e) {
    // Log the error for debugging purposes (optional, but recommended for production)
    // error_log('Password reset request failed: ' . $e->getMessage()); 
    header('Location: ../forgot_password.php?error=' . urlencode('An error occurred during the password reset request. Please try again later.'));
    exit();
}
