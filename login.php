<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Feedback Platform</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Font Awesome CDN for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Added styles for icon positioning and new visual enhancements -->
    <style>
        /* --- Icon Positioning --- */
        .input-group {
            position: relative;
        }

        .input-group .icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }

        .input-group input {
            padding-left: 45px; /* Make space for the icon */
        }
        
        .btn-google .icon {
            margin-right: 10px; /* Add space between Google icon and text */
        }

        /* --- Modern "OR" Divider --- */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #aaa;
            margin: 25px 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #444;
        }

        .divider:not(:empty)::before {
            margin-right: 1em;
        }

        .divider:not(:empty)::after {
            margin-left: 1em;
        }

        /* --- Animated Background --- */
        .signup-body {
            overflow: hidden; /* Hide scrollbars caused by animations */
        }

        @keyframes drift {
            from {
                transform: translateY(0) rotate(0deg);
            }
            to {
                transform: translateY(-120vh) rotate(360deg); /* Move from bottom to well above the top */
            }
        }

        .background-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0; /* Behind the login form but above the body background */
        }

        .background-shapes div {
            position: absolute;
            display: block;
            background: rgba(255, 255, 255, 0.05); /* Very subtle circles */
            animation: drift 25s linear infinite;
            bottom: -200px; /* Start below the screen */
            border-radius: 50%;
        }

        /* Define various sizes, positions, and animation delays for a random effect */
        .background-shapes div:nth-child(1) { left: 25%; width: 80px; height: 80px; animation-delay: 0s; }
        .background-shapes div:nth-child(2) { left: 10%; width: 20px; height: 20px; animation-delay: 2s; animation-duration: 12s; }
        .background-shapes div:nth-child(3) { left: 70%; width: 20px; height: 20px; animation-delay: 4s; }
        .background-shapes div:nth-child(4) { left: 40%; width: 60px; height: 60px; animation-delay: 0s; animation-duration: 18s; }
        .background-shapes div:nth-child(5) { left: 65%; width: 20px; height: 20px; animation-delay: 0s; animation-duration: 22s; }
        .background-shapes div:nth-child(6) { left: 75%; width: 110px; height: 110px; animation-delay: 3s; }
        .background-shapes div:nth-child(7) { left: 35%; width: 150px; height: 150px; animation-delay: 7s; }
        .background-shapes div:nth-child(8) { left: 50%; width: 25px; height: 25px; animation-delay: 15s; animation-duration: 45s; }
        .background-shapes div:nth-child(9) { left: 20%; width: 15px; height: 15px; animation-delay: 2s; animation-duration: 35s; }
        .background-shapes div:nth-child(10) { left: 85%; width: 150px; height: 150px; animation-delay: 0s; animation-duration: 11s; }

        /* Ensure login container is on top of the animation */
        .signup-container {
            position: relative;
            z-index: 1;
        }

    </style>
</head>

<body class="signup-body">

    <!-- New Animated Background -->
    <div class="background-shapes">
        <div></div><div></div><div></div><div></div><div></div>
        <div></div><div></div><div></div><div></div><div></div>
    </div>

    <div class="signup-container">
        <div class="signup-box">
            <div class="signup-header">
                <h1>Welcome Back</h1>
                <p class="subtitle">Don't have an account yet? <a href="signup.php">Sign up</a></p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <p class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></p>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <p class="success-message"><?php echo htmlspecialchars($_GET['success']); ?></p>
            <?php endif; ?>

            <form action="api/login.php" method="POST">
                <div class="input-group">
                    <i class="fas fa-envelope icon"></i>
                    <input type="email" id="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock icon"></i>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Login</button>
            </form>

            <p class="subtitle" style="margin-top: 15px;"><a href="forgot_password.php">Forgot Password?</a></p>

            <!-- Modernized Divider -->
            <div class="divider">OR</div>

            <div class="social-login-google">
                <button type="button" class="btn btn-secondary btn-full btn-google">
                    <i class="fab fa-google icon"></i>
                    Sign in with Google
                </button>
            </div>
        </div>
    </div>

    <!-- The old "tech-lines" div has been removed -->

</body>

</html>