<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require 'config.php'; // Include config.php for database connection

$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];

$survey_id = $_GET['survey_id'] ?? null;
$survey = null;
$questions = [];
$is_editing = false;
$form_action = 'api/create_survey.php'; // Default action for creating

if ($survey_id) {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch survey details
        $stmt = $conn->prepare("SELECT * FROM SURVEY WHERE survey_id = :survey_id AND admin_id = :admin_id");
        $stmt->execute(['survey_id' => $survey_id, 'admin_id' => $admin_id]);
        $survey = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($survey) {
            $is_editing = true;
            $form_action = 'api/update_survey.php'; // Change action for updating

            // Fetch questions for the survey
            $stmt_q = $conn->prepare("SELECT * FROM QUESTION WHERE survey_id = :survey_id ORDER BY question_order ASC");
            $stmt_q->execute(['survey_id' => $survey_id]);
            $questions_data = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

            foreach ($questions_data as $q) {
                $question_id = $q['question_id'];
                $q_type = $q['question_type'];
                $options = [];

                // Fetch options for multiple choice, checkboxes, dropdown
                if (in_array($q_type, ['multiple-choice', 'checkboxes', 'dropdown', 'linear-scale', 'rating'])) {
                    $stmt_opt = $conn->prepare("SELECT option_value FROM QUESTION_OPTION WHERE question_id = :question_id ORDER BY option_order ASC");
                    $stmt_opt->execute(['question_id' => $question_id]);
                    $options = $stmt_opt->fetchAll(PDO::FETCH_COLUMN);
                }
                
                $questions[] = [
                    'question_id' => $question_id, // Include question_id for updates
                    'question_text' => $q['question_text'],
                    'question_type' => $q_type,
                    'is_required' => (bool)$q['is_required'],
                    'options' => $options
                ];
            }
        } else {
            // Survey not found or not owned by admin, redirect to dashboard
            header('Location: dashboard.php');
            exit();
        }
    } catch (PDOException $e) {
        // Log error and redirect
        error_log("Database error in create_survey.php: " . $e->getMessage());
        header('Location: dashboard.php?error=db_error'); // Generic error for user
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_editing ? 'Edit Survey' : 'Create New Survey'; ?> - Feedback Platform</title>
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="app-body">
    <header class="app-header">
        <div class="logo">
             <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16zM2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12z" fill="#fff"/><path d="M12 6a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 12 6zm0 12a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 1 0v2a.5.5 0 0 1-.5.5zM7.5 11a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2zm8 0a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2z" fill="#007bff"/></svg>
            <span>Feedback Platform</span>
        </div>
        <nav>
            <a href="dashboard.php">Home</a>
            <a href="dashboard.php" class="active">Surveys</a>
            <a href="#">Reports</a>
            <a href="#">Integrations</a>
        </nav>
        <div class="user-profile">
            <svg class="icon"><use href="assets/icons.svg#notification"></use></svg>
            <div class="avatar" title="<?php echo htmlspecialchars($admin_email); ?>">
                <?php echo strtoupper(substr($admin_email, 0, 1)); ?>
            </div>
             <a href="api/logout.php" class="logout-link" title="Logout">
                <svg class="icon"><use href="assets/icons.svg#logout"></use></svg>
            </a>
        </div>
    </header>

    <main class="main-content survey-builder-page">
        <div class="page-title">
            <h1><?php echo $is_editing ? 'Edit Survey: ' . htmlspecialchars($survey['title']) : 'Create New Survey'; ?></h1>
            <p>Design your survey by adding questions and customizing the layout.</p>
        </div>

        <form action="<?php echo $form_action; ?>" method="POST" id="survey-form" enctype="multipart/form-data">
            <?php if ($is_editing): ?>
                <input type="hidden" name="survey_id" value="<?php echo htmlspecialchars($survey['survey_id']); ?>">
            <?php endif; ?>
            <div class="builder-layout">
                <div class="builder-main">
                    <div class="card">
                        <div class="card-body">
                            <h2>Survey Details</h2>
                            <div class="form-group">
                                <label for="survey-title">Survey Title</label>
                                <input type="text" id="survey-title" name="title" placeholder="e.g., Customer Satisfaction Survey" value="<?php echo $is_editing ? htmlspecialchars($survey['title']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="survey-description">Survey Description</label>
                                <textarea id="survey-description" name="description" rows="4" placeholder="A short description of your survey"><?php echo $is_editing ? htmlspecialchars($survey['description']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h2>Add Questions</h2>
                            <div class="add-question-buttons">
                                <button type="button" class="btn btn-secondary" data-type="multiple-choice"><svg class="icon"><use href="assets/icons.svg#list"></use></svg> Multiple Choice</button>
                                <button type="button" class="btn btn-secondary" data-type="checkboxes"><svg class="icon"><use href="assets/icons.svg#checkbox"></use></svg> Checkboxes</button>
                                <button type="button" class="btn btn-secondary" data-type="dropdown"><svg class="icon"><use href="assets/icons.svg#dropdown"></use></svg> Dropdown</button>
                                <button type="button" class="btn btn-secondary" data-type="open-question"><svg class="icon"><use href="assets/icons.svg#text"></use></svg> Open Question</button>
                                <button type="button" class="btn btn-secondary" data-type="rating"><svg class="icon"><use href="assets/icons.svg#star"></use></svg> Rating</button>
                                <button type="button" class="btn btn-secondary" data-type="linear-scale"><svg class="icon"><use href="assets/icons.svg#scale"></use></svg> Linear Scale</button>
                                <button type="button" class="btn btn-secondary" data-type="date"><svg class="icon"><use href="assets/icons.svg#date"></use></svg> Date</button>
                                <button type="button" class="btn btn-secondary" data-type="time"><svg class="icon"><use href="assets/icons.svg#time"></use></svg> Time</button>
                                <button type="button" class="btn btn-secondary" data-type="file-upload"><svg class="icon"><use href="assets/icons.svg#upload"></use></svg> File Upload</button>
                            </div>
                            <div id="questions-container">
                                <!-- Questions will be added here dynamically by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="builder-sidebar">
                    <div class="card survey-preview">
                        <div class="card-body">
                            <h2>Survey Preview</h2>
                            <div id="preview-container" class="preview-content">
                                <div class="empty-preview" style="<?php echo $is_editing && !empty($questions) ? 'display: none;' : ''; ?>">
                                    <svg class="icon"><use href="assets/icons.svg#survey-icon"></use></svg>
                                    <p><strong>No questions added yet</strong></p>
                                    <p>Add questions to see how your survey will look.</p>
                                </div>
                            </div>
                            <div class="preview-actions">
                                <button type="button" id="preview-btn" class="btn btn-secondary">Preview Survey</button>
                                <button type="submit" name="save_draft" class="btn btn-secondary">Save as Draft</button>
                                <button type="submit" name="publish" class="btn btn-primary">Save & Publish</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <script>
        const initialQuestionsData = <?php echo json_encode($questions); ?>;
    </script>
    <script src="js/main.js"></script>
</body>
</html>