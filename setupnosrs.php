<?php
require 'config.php';

// --- DEFAULT ADMINISTRATOR ---
$admin_email = "admin@example.com";
$admin_password = "password123";

header('Content-Type: text/plain');
echo "--- Database Setup and Update Script ---\n\n";

try {
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "SUCCESS: Connected to MySQL server.\n";

    $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "SUCCESS: Database '$dbname' is ready.\n";
    $conn->exec("USE `$dbname`");
    echo "SUCCESS: Switched to database '$dbname'.\n";

    // --- Table Creation (for new installs) ---
    echo "\n--- Ensuring Tables Exist ---\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS ADMINISTRATOR (admin_id INT PRIMARY KEY AUTO_INCREMENT, email VARCHAR(255) UNIQUE NOT NULL, password_hash VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP);");
    $conn->exec("CREATE TABLE IF NOT EXISTS SURVEY (survey_id INT PRIMARY KEY AUTO_INCREMENT, admin_id INT, title VARCHAR(255) NOT NULL, description TEXT, unique_url VARCHAR(255) UNIQUE NOT NULL, status VARCHAR(50) NOT NULL DEFAULT 'active', created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (admin_id) REFERENCES ADMINISTRATOR(admin_id) ON DELETE CASCADE);");
    $conn->exec("CREATE TABLE IF NOT EXISTS QUESTION (question_id INT PRIMARY KEY AUTO_INCREMENT, survey_id INT, question_text TEXT NOT NULL, question_type VARCHAR(50) NOT NULL, question_order INT NOT NULL, options TEXT, FOREIGN KEY (survey_id) REFERENCES SURVEY(survey_id) ON DELETE CASCADE);");
    $conn->exec("CREATE TABLE IF NOT EXISTS QUESTION_OPTION (option_id INT PRIMARY KEY AUTO_INCREMENT, question_id INT, option_value VARCHAR(255) NOT NULL, option_order INT NOT NULL, FOREIGN KEY (question_id) REFERENCES QUESTION(question_id) ON DELETE CASCADE);");
    $conn->exec("CREATE TABLE IF NOT EXISTS RESPONSE (response_id INT PRIMARY KEY AUTO_INCREMENT, survey_id INT, submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (survey_id) REFERENCES SURVEY(survey_id) ON DELETE CASCADE);");
    $conn->exec("CREATE TABLE IF NOT EXISTS ANSWER (answer_id INT PRIMARY KEY AUTO_INCREMENT, response_id INT, question_id INT, answer_value TEXT, FOREIGN KEY (response_id) REFERENCES RESPONSE(response_id) ON DELETE CASCADE, FOREIGN KEY (question_id) REFERENCES QUESTION(question_id) ON DELETE CASCADE);");
    echo "SUCCESS: All base tables are present.\n";

    // --- Schema Updates (for existing installs) ---
    echo "\n--- Applying Schema Updates ---\n";

    // Add 'status' to SURVEY table if not exists
    try {
        $conn->exec("ALTER TABLE SURVEY ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'draft' AFTER unique_url;");
        echo "SUCCESS: Added 'status' column to SURVEY table.\n";
    } catch (PDOException $e) {
        echo "INFO: 'status' column in SURVEY likely already exists.\n";
    }

    // Add 'is_required' to QUESTION table if not exists
    try {
        $conn->exec("ALTER TABLE QUESTION ADD COLUMN is_required TINYINT(1) NOT NULL DEFAULT 0 AFTER options;");
        echo "SUCCESS: Added 'is_required' column to QUESTION table.\n";
    } catch (PDOException $e) {
        echo "INFO: 'is_required' column in QUESTION likely already exists.\n";
    }
    
    // Add 'attached_image_path' to QUESTION table if not exists
    try {
        $conn->exec("ALTER TABLE QUESTION ADD COLUMN attached_image_path VARCHAR(255) DEFAULT NULL AFTER is_required;");
        echo "SUCCESS: Added 'attached_image_path' column to QUESTION table.\n";
    } catch (PDOException $e) {
        echo "INFO: 'attached_image_path' column in QUESTION likely already exists.\n";
    }

    // Add 'is_verified' to ADMINISTRATOR table if not exists
    try {
        $conn->exec("ALTER TABLE ADMINISTRATOR ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0;");
        echo "SUCCESS: Added 'is_verified' column to ADMINISTRATOR table.\n";
    } catch (PDOException $e) {
        echo "INFO: 'is_verified' column in ADMINISTRATOR likely already exists.\n";
    }

    // Add 'verification_token' to ADMINISTRATOR table if not exists
    try {
        $conn->exec("ALTER TABLE ADMINISTRATOR ADD COLUMN verification_token VARCHAR(255) DEFAULT NULL;");
        echo "SUCCESS: Added 'verification_token' column to ADMINISTRATOR table.\n";
    } catch (PDOException $e) {
        echo "INFO: 'verification_token' column in ADMINISTRATOR likely already exists.\n";
    }

    // Add 'verification_sent_at' to ADMINISTRATOR table if not exists
    try {
        $conn->exec("ALTER TABLE ADMINISTRATOR ADD COLUMN verification_sent_at DATETIME DEFAULT NULL;");
        echo "SUCCESS: Added 'verification_sent_at' column to ADMINISTRATOR table.\n";
    } catch (PDOException $e) {
        echo "INFO: 'verification_sent_at' column in ADMINISTRATOR likely already exists.\n";
    }

    // Add index for verification_token if not exists
    try {
        $conn->exec("CREATE INDEX idx_admin_verif_token ON ADMINISTRATOR(verification_token);");
        echo "SUCCESS: Added index for verification_token to ADMINISTRATOR table.\n";
    } catch (PDOException $e) {
        echo "INFO: Index for verification_token in ADMINISTRATOR likely already exists.\n";
    }

    // Add 'password_reset_token' to ADMINISTRATOR table if not exists
    try {
        $conn->exec("ALTER TABLE ADMINISTRATOR ADD COLUMN password_reset_token VARCHAR(255) DEFAULT NULL;");
        echo "SUCCESS: Added 'password_reset_token' column to ADMINISTRATOR table.\n";
    } catch (PDOException $e) {
        echo "INFO: 'password_reset_token' column in ADMINISTRATOR likely already exists.\n";
    }

    // Add 'password_reset_expires_at' to ADMINISTRATOR table if not exists
    try {
        $conn->exec("ALTER TABLE ADMINISTRATOR ADD COLUMN password_reset_expires_at DATETIME DEFAULT NULL;");
        echo "SUCCESS: Added 'password_reset_expires_at' column to ADMINISTRATOR table.\n";
    } catch (PDOException $e) {
        echo "INFO: 'password_reset_expires_at' column in ADMINISTRATOR likely already exists.\n";
    }

    // Add 'username' to ADMINISTRATOR table if not exists
    try {
        $conn->exec("ALTER TABLE ADMINISTRATOR ADD COLUMN username VARCHAR(50) UNIQUE NOT NULL AFTER admin_id;");
        echo "SUCCESS: Added 'username' column to ADMINISTRATOR table.\n";
    } catch (PDOException $e) {
        echo "INFO: 'username' column in ADMINISTRATOR likely already exists.\n";
    }

    // --- Default Admin Setup ---
    echo "\n--- Setting up Default Administrator ---\n";
    $stmt = $conn->prepare("SELECT admin_id FROM ADMINISTRATOR WHERE email = :email");
    $stmt->execute(['email' => $admin_email]);

    if ($stmt->rowCount() == 0) {
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        $insert_stmt = $conn->prepare("INSERT INTO ADMINISTRATOR (email, password_hash) VALUES (:email, :password_hash)");
        $insert_stmt->execute(['email' => $admin_email, 'password_hash' => $hashed_password]);
        echo "SUCCESS: Default administrator created.\n";
        echo "Email: " . $admin_email . "\n";
        echo "Password: " . $admin_password . "\n";
    } else {
        echo "INFO: Default administrator already exists.\n";
    }

    echo "\n\nSETUP AND UPDATE COMPLETE! You can now delete this file.";

} catch(PDOException $e) {
    echo "\n--- ERROR ---\n";
    echo "An error occurred: " . $e->getMessage();
}

$conn = null;
?>