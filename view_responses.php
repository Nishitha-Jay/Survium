<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require 'config.php';

if (!isset($_GET['survey_id'])) {
    header('Location: dashboard.php');
    exit();
}
$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];
$survey_id = $_GET['survey_id'];

$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $conn->prepare("SELECT * FROM SURVEY WHERE survey_id = :survey_id AND admin_id = :admin_id");
$stmt->execute(['survey_id' => $survey_id, 'admin_id' => $admin_id]);
$survey = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$survey) {
    header('Location: dashboard.php');
    exit();
}

// Fetch all questions to prepare for analytics
$stmt_q = $conn->prepare("SELECT * FROM QUESTION WHERE survey_id = :survey_id ORDER BY question_order ASC");
$stmt_q->execute(['survey_id' => $survey_id]);
$raw_questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC); // Fetch as associative array

$questions = [];
foreach ($raw_questions as $q) {
    $questions[$q['question_id']] = $q; // Manually re-index by question_id
}

// Fetch all answers
$stmt_a = $conn->prepare(
    "SELECT r.response_id, r.submitted_at, a.question_id, a.answer_value
     FROM ANSWER a
     JOIN RESPONSE r ON a.response_id = r.response_id
     WHERE r.survey_id = :survey_id
     ORDER BY r.submitted_at DESC, a.question_id ASC"
);
$stmt_a->execute(['survey_id' => $survey_id]);
$all_answers = $stmt_a->fetchAll(PDO::FETCH_ASSOC);

// Process responses for individual display
$responses = [];
foreach ($all_answers as $answer) {
    $responses[$answer['response_id']]['submitted_at'] = $answer['submitted_at'];
    $responses[$answer['response_id']]['answers'][$answer['question_id']] = $answer['answer_value'];
}
$total_responses = count($responses);

// Process analytics data for charts
$analytics_data = [];
foreach ($questions as $qid => $question) {
    $q_type = $question['question_type'];
    if (in_array($q_type, ['rating', 'multiple-choice', 'dropdown', 'checkboxes', 'linear-scale'])) {
        $analytics_data[$qid] = [
            'question' => $question['question_text'],
            'type' => $q_type,
            'counts' => []
        ];
    }
}

foreach ($all_answers as $answer) {
    $qid = $answer['question_id'];
    if (isset($analytics_data[$qid])) {
        $value = $answer['answer_value'];
        if ($questions[$qid]['question_type'] == 'checkboxes') {
            $decoded_values = json_decode($value, true);
            if (is_array($decoded_values)) {
                foreach ($decoded_values as $v) {
                    $analytics_data[$qid]['counts'][$v] = ($analytics_data[$qid]['counts'][$v] ?? 0) + 1;
                }
            }
        } else {
             $analytics_data[$qid]['counts'][$value] = ($analytics_data[$qid]['counts'][$value] ?? 0) + 1;
        }
    }
}

// ---- Overall rating summary (only for 'rating' questions) ----
$rating_qids = [];
foreach ($questions as $qid => $q) {
    if (($q['question_type'] ?? '') === 'rating') $rating_qids[] = (string)$qid;
}

$ratingCounts = [1=>0,2=>0,3=>0,4=>0,5=>0];
$totalRatingResponses = 0;
$ratingSum = 0;

if (!empty($rating_qids)) {
    foreach ($all_answers as $row) {
        $qid = (string)$row['question_id'];
        if (in_array($qid, $rating_qids, true)) {
            $v = (int)$row['answer_value'];
            if ($v >= 1 && $v <= 5) {
                $ratingCounts[$v]++;
                $ratingSum += $v;
                $totalRatingResponses++;
            }
        }
    }
}

$averageRating = $totalRatingResponses > 0 ? $ratingSum / $totalRatingResponses : 0;
$ratingPercents = [];
if ($totalRatingResponses > 0) {
    for ($i=1; $i<=5; $i++) {
        $ratingPercents[$i] = round($ratingCounts[$i] * 100 / $totalRatingResponses);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Responses - <?php echo htmlspecialchars($survey['title']); ?></title>
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="app-body">
    <header class="app-header"><!-- ... header code ... --></header>

    <main class="main-content">
        <div style="margin-bottom:1rem;">
          <a href="dashboard.php" class="btn btn-secondary">
            <svg class="icon"><use href="assets/icons.svg#arrow-left"></use></svg>
            Back to dashboard
          </a>
        </div>
        <div class="page-title">
            <h1><?php echo htmlspecialchars($survey['title']); ?></h1>
            <p>Collected <?php echo $total_responses; ?> responses</p>
        </div>

        <div class="responses-layout">
            <div class="summary-panel">
                <div class="card">
                    <div class="card-body">
                        <h2>Summary & Analytics</h2>
                        <?php if ($totalRatingResponses > 0): ?>
                          <div class="rating-summary-block">
                            <div class="average-rating">
                              <div class="score"><?php echo number_format($averageRating, 1); ?></div>
                              <div class="stars" style="--rating: <?php echo round($averageRating,2); ?>"></div>
                            </div>
                            <div class="based-on">Based on <?php echo $totalRatingResponses; ?> reviews</div>
                            <div class="rating-bars">
                              <?php for ($i=5; $i>=1; $i--): ?>
                                <div class="bar-row">
                                  <div class="bar-label"><?php echo $i; ?></div>
                                  <div class="bar-container"><div class="bar" style="width: <?php echo $ratingPercents[$i]; ?>%;"></div></div>
                                  <div class="bar-percentage"><?php echo $ratingPercents[$i]; ?>%</div>
                                </div>
                              <?php endfor; ?>
                            </div>
                          </div>
                        <?php endif; ?>
                        <?php if (empty($analytics_data)): ?>
                             <p>No questions in this survey are eligible for analytics.</p>
                        <?php else: ?>
                            <?php foreach($analytics_data as $qid => $data): ?>
                                <div class="analytics-block">
                                    <h4><?php echo htmlspecialchars($data['question']); ?></h4>
                                    <div class="chart-container">
                                        <canvas id="chart-<?php echo $qid; ?>"></canvas>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="individual-responses-panel">
                <h2>Individual Responses</h2>
                <?php if (empty($responses)): ?>
                    <div class="card"><div class="card-body">No responses have been submitted yet.</div></div>
                <?php else: ?>
                    <?php $response_num = 0; foreach ($responses as $response_id => $response_data): $response_num++; ?>
                        <details class="accordion-item card">
                            <summary class="accordion-header">
                                Response #<?php echo $response_num; ?>
                                <svg class="icon accordion-icon"><use href="assets/icons.svg#chevron-down"></use></svg>
                            </summary>
                            <div class="accordion-body">
                                <p class="response-timestamp">Submitted on: <?php echo date('F j, Y, g:i a', strtotime($response_data['submitted_at'])); ?></p>
                                <?php foreach ($response_data['answers'] as $qid => $answer_value): ?>
                                    <div class="answer-block">
                                        <strong><?php echo htmlspecialchars($questions[$qid]['question_text']); ?></strong>
                                        <?php if ($questions[$qid]['question_type'] == 'file-upload' && $answer_value): ?>
                                            <p><a href="<?php echo htmlspecialchars($answer_value); ?>" target="_blank">View Uploaded File</a></p>
                                        <?php else: ?>
                                            <p><?php echo nl2br(htmlspecialchars($answer_value)); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const analyticsData = <?php echo json_encode(array_values($analytics_data)); ?>;
        const questionMap = <?php echo json_encode($questions); ?>;
        
        analyticsData.forEach((data, index) => {
            const qid = Object.keys(questionMap).find(key => questionMap[key].question_text === data.question);
            const ctx = document.getElementById(`chart-${qid}`).getContext('2d');
            const labels = Object.keys(data.counts);
            const values = Object.values(data.counts);
            
            let chartType = 'bar';
            if (data.type === 'rating' || data.type === 'multiple-choice' || data.type === 'dropdown') {
                chartType = 'doughnut';
            }

            new Chart(ctx, {
                type: chartType,
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Responses',
                        data: values,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)', 'rgba(153, 102, 255, 0.6)',
                            'rgba(255, 159, 64, 0.6)', 'rgba(255, 99, 132, 0.6)',
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)', 'rgba(255, 99, 132, 1)',
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: chartType === 'doughnut' ? 'top' : 'none',
                            labels: { color: '#e0e0e0' }
                        }
                    },
                    scales: chartType === 'bar' ? {
                        y: { beginAtZero: true, ticks: { color: '#a0a0a0' }, grid: { color: '#444' } },
                        x: { ticks: { color: '#a0a0a0' }, grid: { color: '#444' } }
                    } : {}
                }
            });
        });
    });
    </script>
</body>
</html>