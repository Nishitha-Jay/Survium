<?php
session_start();
if (isset($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit(); }

require '../config.php';
require_once '../send_mail.php';

// Rename this variable to avoid conflict with database username
$user_chosen_username = trim($_POST['username'] ?? '');
$email                = trim($_POST['email'] ?? '');
$pass                 = $_POST['password'] ?? '';
$pass2                = $_POST['password2'] ?? '';

if (empty($user_chosen_username) || strlen($user_chosen_username) < 3) {
	header('Location: ../signup.php?error=' . urlencode('Username must be at least 3 characters.'));
	exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	header('Location: ../signup.php?error=' . urlencode('Enter a valid email.'));
	exit();
}
if (strlen($pass) < 8) {
	header('Location: ../signup.php?error=' . urlencode('Password must be at least 8 characters.'));
	exit();
}
if ($pass !== $pass2) {
	header('Location: ../signup.php?error=' . urlencode('Passwords do not match.'));
	exit();
}

try {
	// Use the correct database credentials from config.php
	$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password_db); 
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// Check if user_chosen_username already exists
	$stmt = $conn->prepare("SELECT admin_id FROM ADMINISTRATOR WHERE username = :user_chosen_username");
	$stmt->execute(['user_chosen_username' => $user_chosen_username]);
	if ($stmt->fetch()) {
		header('Location: ../signup.php?error=' . urlencode('Username is already taken.'));
		exit();
	}

	// Check if email already exists
	$stmt = $conn->prepare("SELECT admin_id FROM ADMINISTRATOR WHERE email = :email");
	$stmt->execute(['email' => $email]);
	if ($stmt->fetch()) {
		header('Location: ../signup.php?error=' . urlencode('Email is already in use.'));
		exit();
	}

	$hash  = password_hash($pass, PASSWORD_DEFAULT);
	$token = bin2hex(random_bytes(32));
	$now   = date('Y-m-d H:i:s');

	// Insert the user_chosen_username into the database
	$ins = $conn->prepare("INSERT INTO ADMINISTRATOR (username, email, password_hash, is_verified, verification_token, verification_sent_at) VALUES (:user_chosen_username, :email, :ph, 0, :token, :sent)");
	$ins->execute(['user_chosen_username'=>$user_chosen_username,'email'=>$email,'ph'=>$hash,'token'=>$token,'sent'=>$now]);

	// Send verification email
	$verifyLink = 'https://app.survium.com/verify.php?token=' . urlencode($token);

	$subject = 'Verify your Feedback Platform account';
	$body = "Hello $user_chosen_username,<br><br>Please verify your account by clicking the link below:<br><a href='$verifyLink'>$verifyLink</a><br><br>If you did not request this, ignore this email.";

	sendMail($email, $subject, $body);

	header('Location: ../signup.php?ok=1');
	exit();
} catch (Exception $e) {
	header('Location: ../signup.php?error=' . urlencode('Registration failed: ' . $e->getMessage()));
	exit();
}
?>