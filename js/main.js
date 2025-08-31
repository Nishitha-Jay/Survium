document.addEventListener('DOMContentLoaded', function () {
    const addQuestionButtons = document.querySelectorAll('.add-question-buttons button');
    const questionsContainer = document.getElementById('questions-container');
    const surveyForm = document.getElementById('survey-form');
    const previewBtn = document.getElementById('preview-btn');
    let questionIndex = 0; // Global index for naming new questions

    // Function to generate a unique ID for question cards
    function generateUniqueId() {
        return `q-card-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
    }

    addQuestionButtons.forEach(button => {
        button.addEventListener('click', () => {
            const type = button.getAttribute('data-type');
            addQuestion(type);
        });
    });

    // Function to add a question, now accepts optional questionData for editing
    function addQuestion(type, questionData = null) {
        const qCardId = generateUniqueId(); // Unique ID for the question card
        const currentQuestionIndex = questionIndex++; // Use current index and then increment
        
        const questionCard = document.createElement('div');
        questionCard.className = 'question-card';
        questionCard.id = qCardId;

        let optionsHTML = '';
        let hiddenQuestionIdInput = '';
        let currentImagePreview = '';

        if (questionData && questionData.question_id) {
            hiddenQuestionIdInput = `<input type="hidden" name="questions[${currentQuestionIndex}][question_id]" value="${questionData.question_id}">`;
        }

        if (questionData && questionData.attached_image_path) {
            currentImagePreview = `
                <p>Current Image: <a href="${questionData.attached_image_path}" target="_blank">${questionData.attached_image_path.split('/').pop()}</a></p>
                <input type="hidden" name="questions[${currentQuestionIndex}][existing_image_path]" value="${questionData.attached_image_path}">
            `;
        }

        const qTypesWithOptions = ['multiple-choice', 'checkboxes', 'dropdown'];

        if (qTypesWithOptions.includes(type)) {
            let initialOptions = questionData && questionData.options ? questionData.options : [''];
            if (initialOptions.length === 0) initialOptions = ['']; // Ensure at least one option for new questions or empty existing
            
            let optionsInputs = initialOptions.map((option, optIndex) => `
                <div class="mc-option">
                    <input type="text" name="questions[${currentQuestionIndex}][options][]" class="form-control" placeholder="Option ${optIndex + 1}" value="${htmlspecialchars(option)}" required>
                    <button type="button" class="remove-option-btn">&times;</button>
                </div>
            `).join('');

            optionsHTML = `
                <div class="mc-options" id="mc-options-${qCardId}">
                    <label>Options</label>
                    ${optionsInputs}
                </div>
                <button type="button" class="btn btn-secondary btn-sm add-option-btn" data-qcard-id="${qCardId}">+ Add Option</button>
            `;
        } else if (type === 'linear-scale' && questionData && questionData.options) {
            const options = JSON.parse(questionData.options); // Options stored as JSON string
            optionsHTML = `
                <div class="linear-scale-options">
                    <div class="form-group">
                        <label>From (e.g., 1)</label>
                        <input type="number" name="questions[${currentQuestionIndex}][linear_min]" class="form-control" value="${htmlspecialchars(options.min)}" required>
                    </div>
                     <div class="form-group">
                        <label>To (e.g., 10)</label>
                        <input type="number" name="questions[${currentQuestionIndex}][linear_max]" class="form-control" value="${htmlspecialchars(options.max)}" required>
                    </div>
                     <div class="form-group">
                        <label>Min Label (e.g., "Not Likely")</label>
                        <input type="text" name="questions[${currentQuestionIndex}][linear_min_label]" class="form-control" placeholder="Optional" value="${htmlspecialchars(options.minLabel || '')}">
                    </div>
                     <div class="form-group">
                        <label>Max Label (e.g., "Very Likely")</label>
                        <input type="text" name="questions[${currentQuestionIndex}][linear_max_label]" class="form-control" placeholder="Optional" value="${htmlspecialchars(options.maxLabel || '')}">
                    </div>
                </div>
            `;
        } else if (type === 'linear-scale') { // For new linear scale questions
             optionsHTML = `
                <div class="linear-scale-options">
                    <div class="form-group">
                        <label>From (e.g., 1)</label>
                        <input type="number" name="questions[${currentQuestionIndex}][linear_min]" class="form-control" value="1" required>
                    </div>
                     <div class="form-group">
                        <label>To (e.g., 10)</label>
                        <input type="number" name="questions[${currentQuestionIndex}][linear_max]" class="form-control" value="10" required>
                    </div>
                     <div class="form-group">
                        <label>Min Label (e.g., "Not Likely")</label>
                        <input type="text" name="questions[${currentQuestionIndex}][linear_min_label]" class="form-control" placeholder="Optional">
                    </div>
                     <div class="form-group">
                        <label>Max Label (e.g., "Very Likely")</label>
                        <input type="text" name="questions[${currentQuestionIndex}][linear_max_label]" class="form-control" placeholder="Optional">
                    </div>
                </div>
            `;
        } else if (type === 'rating') { // For rating, options are min/max, not actual choices
            // Rating can be treated like a specific type of linear scale or a simple number input.
            // For simplicity, let's assume it's a fixed 1-5 rating or similar, or allow min/max here as well.
            // If it's a simple fixed rating (e.g., 1-5 stars), no extra options HTML is needed here.
            // If it can be configured (e.g. 1-10 stars), then we need min/max like linear scale.
            // For now, assuming it's a simple question type with no special options in the builder.
            // If it needs specific configuration, copy paste and adapt the linear-scale code.
        } else if (type === 'file-upload') {
             optionsHTML = `
                <div class="form-group">
                    <label for="q-image-${currentQuestionIndex}">Attach an image for context (optional)</label>
                    <input type="file" id="q-image-${currentQuestionIndex}" name="questions_${currentQuestionIndex}_image" class="form-control" accept="image/*">
                    ${currentImagePreview}
                </div>
             `;
        }

        const questionHTML = `
            <div class="question-card-header">
                <h4>${type.replace('-', ' ')} Question</h4>
                <button type="button" class="remove-question-btn" data-target="${qCardId}">&times;</button>
            </div>
            <div class="form-group">
                <label for="q-text-${currentQuestionIndex}">Question Text</label>
                <input type="text" id="q-text-${currentQuestionIndex}" name="questions[${currentQuestionIndex}][text]" placeholder="Enter your question" value="${htmlspecialchars(questionData ? questionData.question_text : '')}" required>
                <input type="hidden" name="questions[${currentQuestionIndex}][type]" value="${type}">
                ${hiddenQuestionIdInput}
            </div>
            ${optionsHTML}
            <div class="question-card-footer">
                <label class="switch-label">Required</label>
                <label class="switch">
                    <input type="checkbox" name="questions[${currentQuestionIndex}][required]" ${questionData && questionData.is_required ? 'checked' : ''}>
                    <span class="slider"></span>
                </label>
            </div>
        `;
        
        questionCard.innerHTML = questionHTML;
        questionsContainer.appendChild(questionCard);
    }

    questionsContainer.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-question-btn')) {
            document.getElementById(e.target.dataset.target)?.remove();
        }
        if (e.target.classList.contains('add-option-btn')) {
            const qCardId = e.target.dataset.qcardId;
            const optionsContainer = document.getElementById(`mc-options-${qCardId}`);
            // Find the index of the question card to correctly name the input fields
            const questionCard = document.getElementById(qCardId);
            const qIndexMatch = questionCard.querySelector('input[name^="questions["]').name.match(/questions\[(\d+)\]/);
            const qIndex = qIndexMatch ? qIndexMatch[1] : 0;

            const newOption = document.createElement('div');
            newOption.className = 'mc-option';
            newOption.innerHTML = `
                <input type="text" name="questions[${qIndex}][options][]" class="form-control" placeholder="Another option" required>
                <button type="button" class="remove-option-btn">&times;</button>
            `;
            optionsContainer.appendChild(newOption);
        }
        if (e.target.classList.contains('remove-option-btn')) {
            if (e.target.closest('.mc-options').querySelectorAll('.mc-option').length > 1) {
                e.target.parentElement.remove();
            } else {
                alert("A question must have at least one option.");
            }
        }
    });

    // Helper function for HTML escaping
    function htmlspecialchars(str) {
        if (typeof str !== 'string') {
            return str; // Return as is if not a string (e.g., number, boolean, null)
        }
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            '\'': '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Initialize questions if in edit mode
    if (typeof initialQuestionsData !== 'undefined' && initialQuestionsData.length > 0) {
        initialQuestionsData.forEach(q => {
            addQuestion(q.question_type, q);
        });
    }

    if (previewBtn) {
        previewBtn.addEventListener('click', function() {
            const originalAction = surveyForm.action;
            const originalTarget = surveyForm.target;
            
            // Temporarily change form attributes to open in a new tab
            surveyForm.action = 'survey_preview.php'; // A temporary file to render the preview
            surveyForm.target = '_blank';
            
            // We need a way to pass the data without submitting to the DB.
            // A simple way is to serialize the form and pass it via a hidden input.
            // For a more robust solution, localStorage or a temporary DB entry would be better.
            // Let's go with a simple approach for now.
            
            // Create a temporary file 'survey_preview.php' that mimics 'survey.php' but reads from POST.
            // For now, let's just make the 'Public Link' on the dashboard more prominent.
            // This button is complex to implement without a backend save.
            alert("Preview functionality is best used on a saved draft. Save this survey as a draft, then use the 'Public Link' on the dashboard to preview.");
            
            // Revert form attributes
            // surveyForm.action = originalAction;
            // surveyForm.target = originalTarget;
        });
    }
});