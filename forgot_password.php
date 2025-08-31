<?php
session_start();
if (isset($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit(); }
$err = $_GET['error'] ?? null;
$ok  = $_GET['ok'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Feedback Platform</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="signup-body">
    <div class="signup-container">
        <div class="signup-box">
            <div class="signup-header">
                <h1>Forgot Your Password?</h1>
                <p class="subtitle">Enter your email address below and we'll send you a link to reset your password.</p>
            </div>

            <?php if ($err): ?><p class="error-message"><?php echo htmlspecialchars($err); ?></p><?php endif; ?>
            <?php if ($ok):  ?><p class="success-message"><?php echo htmlspecialchars($ok); ?></p><?php endif; ?>

            <form action="api/request_password_reset.php" method="POST" novalidate>
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Your email" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Send Reset Link</button>
            </form>
            
            <div class="signup-footer">
                <p><a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
