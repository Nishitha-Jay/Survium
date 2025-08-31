<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require 'config.php';
$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];

$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $conn->prepare(
    "SELECT s.*, (SELECT COUNT(*) FROM RESPONSE WHERE survey_id = s.survey_id) as response_count
     FROM SURVEY s
     WHERE s.admin_id = :admin_id
     ORDER BY s.created_at DESC"
);
$stmt->execute(['admin_id' => $admin_id]);
$surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard</title>
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-body">
    <header class="app-header">
        <div class="logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16zM2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12z"
                    fill="#fff" />
                <path
                    d="M12 6a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 12 6zm0 12a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 1 0v2a.5.5 0 0 1-.5.5zM7.5 11a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2zm8 0a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1h-2z"
                    fill="#007bff" />
            </svg>
            <span>Feedback Central</span>
        </div>
        <nav>
            <a href="dashboard.php" class="active">Home</a>
            <a href="#">Reports <br>(Coming Soon)</a>
            <a href="#">Integrations <br>(Coming Soon)</a>
        </nav>
        <div class="user-profile">
            <svg class="icon">
                <use href="assets/icons.svg#notification"></use>
            </svg>
            <div class="avatar" title="<?php echo htmlspecialchars($admin_email); ?>">
                <?php echo strtoupper(substr($admin_email, 0, 1)); ?>
            </div>
            <a href="api/logout.php" class="logout-link" title="Logout">
                <svg class="icon">
                    <use href="assets/icons.svg#logout"></use>
                </svg>
            </a>
        </div>
    </header>

    <main class="main-content">
        <div class="dashboard-header">
            <h1>Administrator Dashboard</h1>
            <a href="create_survey.php" class="btn btn-primary">
                <svg class="icon">
                    <use href="assets/icons.svg#plus"></use>
                </svg>
                Create New Survey
            </a>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Active Surveys</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>TITLE</th>
                                <th>STATUS</th>
                                <th>RESPONSES</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($surveys)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 2rem;">You haven't created any
                                        surveys yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($surveys as $survey): ?>
                                    <tr id="survey-row-<?php echo $survey['survey_id']; ?>">
                                        <td><?php echo htmlspecialchars($survey['title']); ?></td>
                                        <td>
                                            <span
                                                class="status-badge status-<?php echo htmlspecialchars($survey['status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($survey['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($survey['response_count']); ?></td>
                                        <td class="actions-cell">
                                            <?php if ($survey['status'] === 'active'): ?>
                                                <a href="view_responses.php?survey_id=<?php echo $survey['survey_id']; ?>"
                                                    class="action-link">View Responses</a>
                                                <button type="button" class="btn btn-secondary btn-share"
                                                    data-public-url="survey.php?url=<?php echo $survey['unique_url']; ?>">
                                                    <svg class="icon">
                                                        <use href="assets/icons.svg#share-3dots"></use>
                                                    </svg>
                                                    Share
                                                </button>
                                            <?php endif; ?>
                                            <a href="survey.php?url=<?php echo $survey['unique_url']; ?>" class="action-link open-public">Public Link</a>
                                            <a href="create_survey.php?survey_id=<?php echo $survey['survey_id']; ?>"
                                                class="action-link">Edit Survey</a>
                                            <?php if ($survey['status'] === 'draft'): ?>
                                                <form class="publish-form" action="api/publish_survey.php" method="POST"
                                                    style="display:inline-block;">
                                                    <input type="hidden" name="survey_id"
                                                        value="<?php echo $survey['survey_id']; ?>">
                                                    <button type="submit" class="action-link"
                                                        title="Publish Survey">Publish</button>
                                                </form>
                                            <?php endif; ?>
                                            <form class="delete-form" action="api/delete_survey.php" method="POST">
                                                <input type="hidden" name="survey_id"
                                                    value="<?php echo $survey['survey_id']; ?>">
                                                <button type="submit" class="btn-delete" title="Delete Survey">
                                                    <svg class="icon">
                                                        <use href="assets/icons.svg#delete"></use>
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <div id="clipboard-snackbar" class="snackbar">URL copied to clipboard!</div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteForms = document.querySelectorAll('.delete-form');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to delete this survey? This action cannot be undone.')) {
                        const formData = new FormData(this);
                        fetch(this.action, {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    const surveyId = formData.get('survey_id');
                                    document.getElementById(`survey-row-${surveyId}`).remove();
                                } else {
                                    alert('Error: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while deleting the survey.');
                            });
                    }
                });
            });

            const publishForms = document.querySelectorAll('.publish-form');
            publishForms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to publish this survey? It will become publicly accessible.')) {
                        const formData = new FormData(this);
                        fetch(this.action, {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    window.location.reload(); // Reload to reflect status change
                                } else {
                                    alert('Error: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while publishing the survey.');
                            });
                    }
                });
            });

            const shareButtons = document.querySelectorAll('.btn-share');
            const snackbar = document.getElementById('clipboard-snackbar');

            shareButtons.forEach(button => {
                button.addEventListener('click', async function () {
                    const rel = this.dataset.publicUrl; // "survey.php?url=..."
                    const base = window.location.origin + window.location.pathname.replace(/[^/]*$/, '/');
                    const fullUrl = base + rel;

                    try {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(fullUrl);
                        } else {
                            const ta = document.createElement('textarea');
                            ta.value = fullUrl;
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            ta.remove();
                        }
                        snackbar.textContent = 'URL copied to clipboard!';
                        snackbar.classList.add('show');
                        setTimeout(() => snackbar.classList.remove('show'), 3000);
                    } catch (e) {
                        alert('Copy failed. URL:\n' + fullUrl);
                    }
                });
            });

            document.querySelectorAll('.open-public').forEach(a => {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    window.open(this.href, 'surveyWindow', 'noopener'); // makes thank_you close() work
                });
            });

            document.getElementById('closeBtn').addEventListener('click', function () {
                // Try to close if this tab was script-opened
                if (window.opener && !window.opener.closed) {
                    window.close();
                    return;
                }
                // Safari/Chrome workaround
                window.open('', '_self');
                window.close();

                // Fallback: if the browser still blocks closing, navigate away
                setTimeout(function () {
                    window.location.href = 'about:blank';
                }, 150);
            });
        });
    </script>
</body>

</html>