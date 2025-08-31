<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - Feedback Platform</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body">
    <div class="feedback-modal">
        <div class="modal-icon-container">
            <svg class="icon success-icon"><use href="assets/icons.svg#check-circle"></use></svg>
        </div>
        <h1>Thank you for your feedback!</h1>
        <p>Your input is valuable to us and helps us improve our services. We appreciate you taking the time to share your thoughts.</p>
        <button class="btn btn-primary" id="closeBtn">Close</button>
    </div>

    <script>
(function () {
  document.getElementById('closeBtn').addEventListener('click', function () {
    // Works if this tab was opened by script (e.g., admin preview)
    if (window.opener && !window.opener.closed) {
      window.close();
      return;
    }
    // Try common workaround
    try { window.open('', '_self'); window.close(); } catch (e) {}
    // Fallback: replace with a blank page (no back button to return)
    location.replace('about:blank');
  });
})();
</script>
</body>
</html>