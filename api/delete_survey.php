<?php
session_start();
require '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

$admin_id = $_SESSION['admin_id'];
$survey_id = $_POST['survey_id'] ?? null;

if (!$survey_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Survey ID is required.']);
    exit();
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the survey belongs to the logged-in admin before deleting
    $stmt = $conn->prepare("SELECT admin_id FROM SURVEY WHERE survey_id = :survey_id");
    $stmt->execute(['survey_id' => $survey_id]);
    $survey = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$survey || $survey['admin_id'] != $admin_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this survey.']);
        exit();
    }

    // Deletion will cascade to QUESTION, RESPONSE, and ANSWER tables due to FOREIGN KEY constraints
    $stmt = $conn->prepare("DELETE FROM SURVEY WHERE survey_id = :survey_id AND admin_id = :admin_id");
    $stmt->execute(['survey_id' => $survey_id, 'admin_id' => $admin_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Survey deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Survey could not be found or already deleted.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>