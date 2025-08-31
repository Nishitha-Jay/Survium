<?php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$survey_id = $_POST['survey_id'] ?? null;
$unique_url = $_POST['unique_url'] ?? null;
$answers = $_POST['answers'] ?? [];

if (!$survey_id) {
    http_response_code(400);
    exit('Bad Request: Missing survey ID.');
}

// Ensure uploads directories exist
if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);
if (!is_dir('../uploads/answers')) mkdir('../uploads/answers', 0777, true);

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->beginTransaction();

    $stmt = $conn->prepare("INSERT INTO RESPONSE (survey_id) VALUES (:survey_id)");
    $stmt->execute(['survey_id' => $survey_id]);
    $response_id = $conn->lastInsertId();

    $stmt_a = $conn->prepare(
        "INSERT INTO ANSWER (response_id, question_id, answer_value) VALUES (:response_id, :question_id, :answer_value)"
    );

    // Handle text-based answers
    foreach ($answers as $question_id => $answer_value) {
        $stmt_a->execute([
            'response_id' => $response_id,
            'question_id' => $question_id,
            'answer_value' => is_array($answer_value) ? json_encode($answer_value) : $answer_value
        ]);
    }

    // Handle file uploads
    if (!empty($_FILES['answers'])) {
        foreach ($_FILES['answers']['name'] as $question_id => $name) {
            if ($_FILES['answers']['error'][$question_id] == 0) {
                // File size validation (5MB)
                if ($_FILES['answers']['size'][$question_id] > 5 * 1024 * 1024) {
                    throw new Exception("File for question ID $question_id is too large.");
                }
                
                $target_dir = "../uploads/answers/";
                $file_extension = pathinfo($name, PATHINFO_EXTENSION);
                $new_filename = uniqid('ans_', true) . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES['answers']['tmp_name'][$question_id], $target_file)) {
                    $file_path = "uploads/answers/" . $new_filename;
                    $stmt_a->execute([
                        'response_id' => $response_id,
                        'question_id' => $question_id,
                        'answer_value' => $file_path
                    ]);
                }
            }
        }
    }

    $conn->commit();
    header('Location: ../thank_you.php?url=' . urlencode($unique_url));
    exit();

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    exit('An error occurred: ' . $e->getMessage());
}
?>