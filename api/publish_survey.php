<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

$admin_id = $_SESSION['admin_id'];
$survey_id = $_POST['survey_id'] ?? null;

if (!$survey_id) {
    echo json_encode(['success' => false, 'message' => 'Survey ID is required.']);
    exit();
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("UPDATE SURVEY SET status = 'active' WHERE survey_id = :survey_id AND admin_id = :admin_id AND status = 'draft'");
    $stmt->execute(['survey_id' => $survey_id, 'admin_id' => $admin_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Survey published successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Survey not found, not owned by admin, or already published.']);
    }

} catch (PDOException $e) {
    error_log("Database error in api/publish_survey.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
