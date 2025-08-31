<?php
session_start();
require 'config.php';

$token = $_GET['token'] ?? '';
$err   = $_GET['error'] ?? null;
$ok    = $_GET['ok'] ?? null;

if (empty($token)) {
    header('Location: login.php?error=' . urlencode('Password reset link is missing.'));
    exit();
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the token is valid and not expired
    $stmt = $conn->prepare("SELECT admin_id FROM ADMINISTRATOR WHERE password_reset_token = :token AND password_reset_expires_at > NOW()");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: login.php?error=' . urlencode('Invalid or expired password reset link.'));
        exit();
    }

} catch (Exception $e) {
    header('Location: login.php?error=' . urlencode('An error occurred during token validation. Please try again.'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Feedback Platform</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="signup-body">
    <div class="signup-container">
        <div class="signup-box">
            <div class="signup-header">
                <h1>Reset Your Password</h1>
                <p class="subtitle">Enter your new password below.</p>
            </div>

            <?php if ($err): ?><p class="error-message"><?php echo htmlspecialchars($err); ?></p><?php endif; ?>
            <?php if ($ok):  ?><p class="success-message"><?php echo htmlspecialchars($ok); ?></p><?php endif; ?>

            <form action="api/process_password_reset.php" method="POST" novalidate>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="input-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter new password (min 8 chars)" required>
                </div>
                <div class="input-group">
                    <label for="password2">Confirm New Password</label>
                    <input type="password" id="password2" name="password2" placeholder="Confirm new password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Reset Password</button>
            </form>
            
            <div class="signup-footer">
                <p><a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </div>
    <div class="tech-lines">
        <div class="line-widget" style="top: 20px; left: 20px;"></div>
        <div class="line-widget" style="top: 20px; right: 20px;"></div>
        <div class="line-widget" style="bottom: 20px; left: 20px;"></div>
        <div class="line-widget" style="bottom: 20px; right: 20px;"></div>
    </div>
</body>
</html>
