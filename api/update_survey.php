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
$survey_id = $_POST['survey_id'] ?? null;
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$submitted_questions = $_POST['questions'] ?? [];
$status = isset($_POST['save_draft']) ? 'draft' : 'active';

if (empty($survey_id) || empty($title)) {
    header('Location: ../create_survey.php?error=' . urlencode('Survey ID and Title are required.'));
    exit();
}

// Ensure uploads directories exist
if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);
if (!is_dir('../uploads/questions')) mkdir('../uploads/questions', 0777, true);

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->beginTransaction();

    // 1. Update SURVEY table
    $stmt = $conn->prepare(
        "UPDATE SURVEY SET title = :title, description = :description, status = :status WHERE survey_id = :survey_id AND admin_id = :admin_id"
    );
    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'status' => $status,
        'survey_id' => $survey_id,
        'admin_id' => $admin_id
    ]);

    // Fetch existing questions for this survey
    $stmt_existing_q = $conn->prepare("SELECT question_id FROM QUESTION WHERE survey_id = :survey_id");
    $stmt_existing_q->execute(['survey_id' => $survey_id]);
    $existing_question_ids = $stmt_existing_q->fetchAll(PDO::FETCH_COLUMN);

    $submitted_question_ids = [];
    foreach ($submitted_questions as $question) {
        if (isset($question['question_id']) && !empty($question['question_id'])) {
            $submitted_question_ids[] = $question['question_id'];
        }
    }

    // 2. Delete removed questions
    $questions_to_delete = array_diff($existing_question_ids, $submitted_question_ids);
    if (!empty($questions_to_delete)) {
        // Delete options first due to foreign key constraint
        $placeholders = implode(',', array_fill(0, count($questions_to_delete), '?'));
        $stmt_del_opt = $conn->prepare("DELETE FROM QUESTION_OPTION WHERE question_id IN ($placeholders)");
        $stmt_del_opt->execute($questions_to_delete);

        $stmt_del_q = $conn->prepare("DELETE FROM QUESTION WHERE question_id IN ($placeholders)");
        $stmt_del_q->execute($questions_to_delete);
    }

    // Prepare statements for insert/update
    $stmt_insert_q = $conn->prepare(
        "INSERT INTO QUESTION (survey_id, question_text, question_type, question_order, options, is_required, attached_image_path)
         VALUES (:survey_id, :text, :type, :order, :options, :is_required, :image_path)"
    );

    $stmt_update_q = $conn->prepare(
        "UPDATE QUESTION SET question_text = :text, question_type = :type, question_order = :order, options = :options, is_required = :is_required, attached_image_path = :image_path
         WHERE question_id = :question_id AND survey_id = :survey_id"
    );

    $stmt_delete_options = $conn->prepare("DELETE FROM QUESTION_OPTION WHERE question_id = :question_id");
    $stmt_insert_options = $conn->prepare(
        "INSERT INTO QUESTION_OPTION (question_id, option_value, option_order) VALUES (:question_id, :value, :order)"
    );

    // 3. Insert or Update questions
    foreach ($submitted_questions as $index => $question) {
        $question_id = $question['question_id'] ?? null;
        $options_json = null;
        $is_required = isset($question['required']) ? 1 : 0;
        $image_path = null; // Will be set if a new file is uploaded or existing is retained

        // Handle question options (multiple-choice, dropdown, etc.)
        if (isset($question['options'])) {
            $options_json = json_encode($question['options']);
        } else if (isset($question['linear_min'])) {
            $options_json = json_encode([
                'min' => $question['linear_min'],
                'max' => $question['linear_max'],
                'minLabel' => $question['linear_min_label'],
                'maxLabel' => $question['linear_max_label']
            ]);
        }
        
        // Handle file uploads for question images
        $file_key = "questions_{$index}_image";
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
            $target_dir = "../uploads/questions/";
            $file_extension = pathinfo($_FILES[$file_key]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid('q_img_', true) . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            if (move_uploaded_file($_FILES[$file_key]["tmp_name"], $target_file)) {
                $image_path = "uploads/questions/" . $new_filename;
            }
        } elseif (isset($question['existing_image_path']) && !empty($question['existing_image_path'])) {
            $image_path = $question['existing_image_path']; // Retain existing image if no new one uploaded
        }

        $question_params = [
            'survey_id' => $survey_id,
            'text' => $question['text'],
            'type' => $question['type'],
            'order' => $index + 1,
            'options' => $options_json,
            'is_required' => $is_required,
            'image_path' => $image_path
        ];

        if ($question_id && in_array($question_id, $existing_question_ids)) {
            // Update existing question
            $question_params['question_id'] = $question_id;
            $stmt_update_q->execute($question_params);

            // Update options: delete old, insert new
            $stmt_delete_options->execute(['question_id' => $question_id]);
            if (isset($question['options']) && is_array($question['options'])) {
                foreach ($question['options'] as $option_order => $option_value) {
                    $stmt_insert_options->execute([
                        'question_id' => $question_id,
                        'value' => $option_value,
                        'order' => $option_order + 1
                    ]);
                }
            }
        } else {
            // Insert new question
            $stmt_insert_q->execute($question_params);
            $new_question_id = $conn->lastInsertId();
            
            // Insert options for new question
            if (isset($question['options']) && is_array($question['options'])) {
                foreach ($question['options'] as $option_order => $option_value) {
                    $stmt_insert_options->execute([
                        'question_id' => $new_question_id,
                        'value' => $option_value,
                        'order' => $option_order + 1
                    ]);
                }
            }
        }
    }

    $conn->commit();
    header('Location: ../dashboard.php');
    exit();

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log("Database error in api/update_survey.php: " . $e->getMessage());
    header('Location: ../create_survey.php?survey_id=' . $survey_id . '&error=' . urlencode('An error occurred: ' . $e->getMessage()));
    exit();
}
?>
