<?php
require 'config.php';
$token = $_GET['token'] ?? '';
if (!$token) { header('Location: login.php?error=' . urlencode('Invalid verification link.')); exit(); }

try {
	$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$stmt = $conn->prepare("SELECT admin_id FROM ADMINISTRATOR WHERE verification_token = :tkn");
	$stmt->execute(['tkn'=>$token]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$row) { header('Location: login.php?error=' . urlencode('Invalid or expired verification link.')); exit(); }

	$upd = $conn->prepare("UPDATE ADMINISTRATOR SET is_verified = 1, verification_token = NULL WHERE admin_id = :id");
	$upd->execute(['id'=>$row['admin_id']]);

	// CHANGE THIS LINE: Use 'success' parameter for good messages
	header('Location: login.php?success=' . urlencode('Email verified. You can now log in.'));
	exit();
} catch (Exception $e) {
	header('Location: login.php?error=' . urlencode('Verification failed: ' . $e->getMessage()));
	exit();
}