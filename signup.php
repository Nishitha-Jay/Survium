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
	<title>Create Your Account - Feedback Platform</title>
	<link rel="stylesheet" href="css/style.css">
	<!-- Link to Font Awesome for icons -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

	<!-- Added styles for the new divider and icon -->
	<style>
		/* This is a new, alternative style for the 'Or sign up with' divider */
		.divider {
			position: relative; /* Establishes a positioning context for child elements */
			text-align: center; /* Centers the text span within the div */
			margin: 25px 0; /* Provides vertical spacing */
		}

		/* This pseudo-element creates the horizontal line */
		.divider::before {
			content: '';
			position: absolute;
			top: 50%; /* Aligns the line to the vertical middle of the container */
			left: 0;
			right: 0;
			height: 1px;
			background-color: #4a5568; /* A subtle line color that fits the theme */
			z-index: 1; /* Ensures the line is rendered behind the text */
		}

		/* This is the text part of the divider */
		.divider span {
			position: relative; /* Allows z-index to take effect */
			z-index: 2; /* Places the text in front of the line */
			padding: 0 1rem; /* Adds horizontal space around the text */
			color: #a0aec0; /* A light grey color for better visibility */

			/* This background color creates the "break" in the line.
			   It MUST match the background color of your .signup-box container.
			   I have used #2d3748 based on the screenshot. */
			background-color: #2d3748;
		}
        
        /* Small adjustment to align the Font Awesome icon nicely with the text */
        .btn-google i {
            margin-right: 8px; /* Adds space between the icon and the word "Google" */
        }
	</style>
</head>
<body class="signup-body">
	<div class="signup-container">
		<div class="signup-box">
			<div class="signup-header">
				<h1>Create Your Account</h1>
				<p class="subtitle">Join us today and start exploring!</p>
			</div>

			<?php if ($err): ?><p class="error-message"><?php echo htmlspecialchars($err); ?></p><?php endif; ?>
			<?php if ($ok):  ?><p class="success-message">Check your email to verify the account.</p><?php endif; ?>

			<form action="api/register.php" method="POST" novalidate>
				<div class="input-group">
					<label for="username">Username</label>
					<input type="text" id="username" name="username" placeholder="Choose a username" required>
				</div>
				<div class="input-group">
					<label for="email">Email Address</label>
					<input type="email" id="email" name="email" placeholder="Your email" required>
				</div>
				<div class="input-group">
					<label for="password">New Password</label>
					<input type="password" id="password" name="password" placeholder="Create a password" required>
				</div>
				<div class="input-group">
					<label for="password2">Confirm Password</label>
					<input type="password" id="password2" name="password2" placeholder="Confirm your password" required>
				</div>
				
				<button type="submit" class="btn btn-primary btn-full">Sign Up</button>
				
				<!-- This is the improved divider -->
				<div class="divider">
					<span>Or sign up with</span>
				</div>
				
				<!-- This is the button with the Font Awesome icon -->
				<button type="button" class="btn btn-google btn-full">
					<i class="fab fa-google"></i>
					Google
				</button>
			</form>
			
			<div class="signup-footer">
				<p>Already have an account? <a href="login.php">Log in</a></p>
				<p><a href="#" class="terms-link">Terms & Privacy</a></p>
			</div>
		</div>
	</div>
</body>
</html>