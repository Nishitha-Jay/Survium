<?php
require 'config.php';

if (!isset($_GET['url'])) {
    die("Survey not found. A URL is required.");
}
$unique_url = $_GET['url'];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM SURVEY WHERE unique_url = :url AND status = 'active'");
    $stmt->execute(['url' => $unique_url]);
    $survey = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$survey) {
        die("This survey is not available. It may be a draft or has been disabled.");
    }

    $stmt = $conn->prepare("SELECT * FROM QUESTION WHERE survey_id = :survey_id ORDER BY question_order ASC");
    $stmt->execute(['survey_id' => $survey['survey_id']]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: Could not connect to the database.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($survey['title']); ?></title>
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="public-survey-body">
    <div class="survey-container">
        <div class="survey-header">
            <h1><?php echo htmlspecialchars($survey['title']); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($survey['description'])); ?></p>
        </div>

        <form action="api/submit_response.php" method="POST" class="survey-form" enctype="multipart/form-data">
            <input type="hidden" name="survey_id" value="<?php echo $survey['survey_id']; ?>">
            <input type="hidden" name="unique_url" value="<?php echo $survey['unique_url']; ?>">

            <?php foreach ($questions as $index => $q): ?>
                <div class="survey-question-card">
                    <label class="question-label">
                        <?php echo ($index + 1) . ". " . htmlspecialchars($q['question_text']); ?>
                        <?php if ($q['is_required']): ?><span class="required-asterisk">*</span><?php endif; ?>
                    </label>

                    <?php if ($q['attached_image_path']): ?>
                        <div class="question-image-container">
                            <img src="<?php echo htmlspecialchars($q['attached_image_path']); ?>" alt="Question Image">
                        </div>
                    <?php endif; ?>

                    <?php $required = $q['is_required'] ? 'required' : ''; ?>
                    
                    <?php if ($q['question_type'] === 'open-question'): ?>
                        <textarea name="answers[<?php echo $q['question_id']; ?>]" class="form-control" rows="5" <?php echo $required; ?>></textarea>
                    
                    <?php elseif ($q['question_type'] === 'rating'): ?>
                        <div class="choice-group">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="radio-label"><input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="<?php echo $i; ?>" <?php echo $required; ?>> <span><?php echo $i; ?> ★</span></label>
                            <?php endfor; ?>
                        </div>

                    <?php elseif (in_array($q['question_type'], ['multiple-choice', 'dropdown'])): ?>
                        <?php $options = json_decode($q['options'], true); ?>
                        <?php if ($q['question_type'] === 'dropdown'): ?>
                            <select name="answers[<?php echo $q['question_id']; ?>]" class="form-control" <?php echo $required; ?>>
                                <option value="" disabled selected>Select an option</option>
                                <?php foreach ($options as $opt): ?>
                                    <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: // multiple-choice ?>
                             <div class="choice-group">
                                <?php foreach ($options as $opt): ?>
                                    <label class="radio-label"><input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="<?php echo htmlspecialchars($opt); ?>" <?php echo $required; ?>> <span><?php echo htmlspecialchars($opt); ?></span></label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($q['question_type'] === 'checkboxes'): ?>
                        <div class="choice-group">
                            <?php $options = json_decode($q['options'], true); ?>
                            <?php foreach ($options as $opt): ?>
                                <label class="checkbox-label"><input type="checkbox" name="answers[<?php echo $q['question_id']; ?>][]" value="<?php echo htmlspecialchars($opt); ?>"> <span><?php echo htmlspecialchars($opt); ?></span></label>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($q['question_type'] === 'linear-scale'): ?>
                        <?php $scale_opts = json_decode($q['options'], true); ?>
                        <div class="linear-scale-group">
                            <span><?php echo htmlspecialchars($scale_opts['minLabel']); ?></span>
                            <?php for ($i = $scale_opts['min']; $i <= $scale_opts['max']; $i++): ?>
                                <label class="radio-label-inline"><input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="<?php echo $i; ?>" <?php echo $required; ?>> <span><?php echo $i; ?></span></label>
                            <?php endfor; ?>
                            <span><?php echo htmlspecialchars($scale_opts['maxLabel']); ?></span>
                        </div>

                    <?php elseif ($q['question_type'] === 'date'): ?>
                        <input type="date" name="answers[<?php echo $q['question_id']; ?>]" class="form-control" <?php echo $required; ?>>

                    <?php elseif ($q['question_type'] === 'time'): ?>
                        <input type="time" name="answers[<?php echo $q['question_id']; ?>]" class="form-control" <?php echo $required; ?>>

                    <?php elseif ($q['question_type'] === 'file-upload'): ?>
                        <input type="file" name="answers[<?php echo $q['question_id']; ?>]" class="form-control" accept="image/*,application/pdf" <?php echo $required; ?>>
                        <small class="form-text">Max file size: 5MB.</small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="survey-footer">
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </div>
        </form>
    </div>
</body>
</html>