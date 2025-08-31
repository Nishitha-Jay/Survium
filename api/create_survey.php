<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo "Forbidden";
    exit();
}

require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit();
}

$admin_id = $_SESSION['admin_id'];
$title = $_POST['title'] ?? 'Untitled Survey';
$description = $_POST['description'] ?? '';
$questions = $_POST['questions'] ?? [];
$status = isset($_POST['save_draft']) ? 'draft' : 'active';

if (empty($title)) {
    header('Location: ../create_survey.php?error=Title is required.');
    exit();
}

// Ensure uploads directories exist
if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);
if (!is_dir('../uploads/questions')) mkdir('../uploads/questions', 0777, true);

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->beginTransaction();

    $unique_url = bin2hex(random_bytes(8));
    $stmt = $conn->prepare(
        "INSERT INTO SURVEY (admin_id, title, description, unique_url, status) VALUES (:admin_id, :title, :description, :unique_url, :status)"
    );
    $stmt->execute([
        'admin_id' => $admin_id,
        'title' => $title,
        'description' => $description,
        'unique_url' => $unique_url,
        'status' => $status
    ]);
    $survey_id = $conn->lastInsertId();

    $stmt_q = $conn->prepare(
        "INSERT INTO QUESTION (survey_id, question_text, question_type, question_order, options, is_required, attached_image_path) 
         VALUES (:survey_id, :text, :type, :order, :options, :is_required, :image_path)"
    );

    foreach ($questions as $index => $question) {
        $options = null;
        if (isset($question['options'])) {
            $options = json_encode($question['options']);
        } else if (isset($question['linear_min'])) {
            $options = json_encode([
                'min' => $question['linear_min'],
                'max' => $question['linear_max'],
                'minLabel' => $question['linear_min_label'],
                'maxLabel' => $question['linear_max_label']
            ]);
        }

        $image_path = null;
        $file_key = "questions_{$index}_image";
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
            $target_dir = "../uploads/questions/";
            $file_extension = pathinfo($_FILES[$file_key]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid('q_img_', true) . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            if (move_uploaded_file($_FILES[$file_key]["tmp_name"], $target_file)) {
                $image_path = "uploads/questions/" . $new_filename;
            }
        }
        
        $stmt_q->execute([
            'survey_id' => $survey_id,
            'text' => $question['text'],
            'type' => $question['type'],
            'order' => $index + 1,
            'options' => $options,
            'is_required' => isset($question['required']) ? 1 : 0,
            'image_path' => $image_path
        ]);
    }

    $conn->commit();
    header('Location: ../dashboard.php');
    exit();

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    header('Location: ../create_survey.php?error=' . urlencode('An error occurred: ' . $e->getMessage()));
    exit();
}
?>