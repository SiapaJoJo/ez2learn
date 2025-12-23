<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'lecturer') {
    header('Location: ../../login.php');
    exit();
}

require_once '../../includes/db-config.php';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$lecturer_id = $_SESSION['user_id'] ?? 0;
$error = '';
$success = '';

$courses_query = "
    SELECT c.course_id, c.course_code, c.course_name
    FROM courses c
    INNER JOIN course_lecturers cl ON c.course_id = cl.course_id
    WHERE cl.lecturer_id = ?
    ORDER BY c.course_code ASC
";
$stmt = mysqli_prepare($conn, $courses_query);
mysqli_stmt_bind_param($stmt, "i", $lecturer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$courses = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quiz'])) {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $total_marks = (int)($_POST['total_marks'] ?? 100);
    
    if (empty($title)) {
        $error = 'Title is required.';
    } elseif ($course_id <= 0) {
        $error = 'Please select a course.';
    } else {

        $verify_stmt = mysqli_prepare($conn, "
            SELECT course_id FROM course_lecturers 
            WHERE course_id = ? AND lecturer_id = ?
        ");
        mysqli_stmt_bind_param($verify_stmt, "ii", $course_id, $lecturer_id);
        mysqli_stmt_execute($verify_stmt);
        $verify_result = mysqli_stmt_get_result($verify_stmt);
        
        if (mysqli_num_rows($verify_result) > 0) {
            $insert_stmt = mysqli_prepare($conn, "
                INSERT INTO quizzes (course_id, lecturer_id, title, total_marks)
                VALUES (?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param($insert_stmt, "iisi", $course_id, $lecturer_id, $title, $total_marks);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $quiz_id = mysqli_insert_id($conn);
                mysqli_stmt_close($insert_stmt);

                $questions = $_POST['questions'] ?? [];
                $total_question_marks = 0;
                
                foreach ($questions as $index => $question) {
                    if (!empty($question['text']) && !empty($question['correct_answer'])) {
                        $question_text = trim($question['text']);
                        $question_type = $question['type'] ?? 'multiple_choice';
                        $marks = (int)($question['marks'] ?? 1);
                        $correct_answer = trim($question['correct_answer']);
                        
                        $option_a = trim($question['option_a'] ?? '');
                        $option_b = trim($question['option_b'] ?? '');
                        $option_c = trim($question['option_c'] ?? '');
                        $option_d = trim($question['option_d'] ?? '');
                        
                        $insert_question = mysqli_prepare($conn, "
                            INSERT INTO quiz_questions 
                            (quiz_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer, marks, question_order)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        mysqli_stmt_bind_param($insert_question, "isssssssii", 
                            $quiz_id, $question_text, $question_type, 
                            $option_a, $option_b, $option_c, $option_d, 
                            $correct_answer, $marks, $index
                        );
                        mysqli_stmt_execute($insert_question);
                        mysqli_stmt_close($insert_question);
                        
                        $total_question_marks += $marks;
                    }
                }

                if ($total_question_marks > 0 && $total_question_marks != $total_marks) {
                    $update_stmt = mysqli_prepare($conn, "UPDATE quizzes SET total_marks = ? WHERE quiz_id = ?");
                    mysqli_stmt_bind_param($update_stmt, "ii", $total_question_marks, $quiz_id);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                }
                
                $success = 'Quiz created successfully!';
                header('Location: edit-quiz.php?quiz_id=' . $quiz_id);
                exit();
            } else {
                $error = 'Failed to create quiz.';
            }
        } else {
            $error = 'You are not assigned to this course.';
        }
        mysqli_stmt_close($verify_stmt);
    }
}

mysqli_close($conn);

$page_title = 'Create Quiz';
require_once '../../includes/header-lecturer.php';
?>
    <style>
        .page-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .page-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        .btn-back {
            background: #6b7280;
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 1rem;
        }

        .btn-back:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        .content {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f8fafc;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .questions-container {
            margin-top: 30px;
        }

        .question-item {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 2px solid #e5e7eb;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .question-item:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .question-number {
            font-weight: 600;
            color: #667eea;
            font-size: 1rem;
        }

        .btn-remove {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-remove:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }

        .btn-add-question {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-add-question:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            margin-top: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            animation: slideUp 0.3s;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #ecfdf5;
            border-color: #10b981;
            color: #065f46;
        }

        .alert-danger {
            background: #fef2f2;
            border-color: #ef4444;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .content {
                padding: 1.5rem;
            }

            .page-header {
                padding: 1.25rem 1.5rem;
            }

            .options-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <a href="index.php" class="btn-back">← Back</a>
                <h1 class="page-title">Create Quiz</h1>
            </div>

            <div class="content">

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" id="quizForm">
                <div class="form-group">
                    <label class="form-label">Course *</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>" <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $course['course_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Quiz Title *</label>
                    <input type="text" name="title" class="form-control" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Total Marks</label>
                    <input type="number" name="total_marks" class="form-control" min="1" value="<?php echo isset($_POST['total_marks']) ? htmlspecialchars($_POST['total_marks']) : '100'; ?>">
                    <small style="color: #666; font-size: 12px;">Will be auto-calculated based on question marks if not specified</small>
                </div>

                <div class="questions-container">
                    <h3 style="margin-bottom: 20px; color: #333;">Questions</h3>
                    <div id="questionsList"></div>
                    <button type="button" class="btn-add-question" onclick="addQuestion()">+ Add Question</button>
                </div>

                <button type="submit" name="create_quiz" class="btn-submit">Create Quiz</button>
            </form>
            </div>
        </div>
    </div>

    <script>
        let questionCount = 0;

        function addQuestion() {
            questionCount++;
            const questionsList = document.getElementById('questionsList');
            const questionDiv = document.createElement('div');
            questionDiv.className = 'question-item';
            questionDiv.id = 'question-' + questionCount;
            
            questionDiv.innerHTML = `
                <div class="question-header">
                    <span class="question-number">Question ${questionCount}</span>
                    <button type="button" class="btn-remove" onclick="removeQuestion(${questionCount})">Remove</button>
                </div>
                <div class="form-group">
                    <label class="form-label">Question Text *</label>
                    <textarea name="questions[${questionCount}][text]" class="form-control" required rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Question Type *</label>
                    <select name="questions[${questionCount}][type]" class="form-control" onchange="toggleOptions(${questionCount}, this.value)" required>
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="true_false">True/False</option>
                        <option value="short_answer">Short Answer</option>
                    </select>
                </div>
                <div class="options-grid" id="options-${questionCount}">
                    <div class="form-group">
                        <label class="form-label">Option A</label>
                        <input type="text" name="questions[${questionCount}][option_a]" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Option B</label>
                        <input type="text" name="questions[${questionCount}][option_b]" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Option C</label>
                        <input type="text" name="questions[${questionCount}][option_c]" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Option D</label>
                        <input type="text" name="questions[${questionCount}][option_d]" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Correct Answer *</label>
                    <input type="text" name="questions[${questionCount}][correct_answer]" class="form-control" required placeholder="Enter correct answer (e.g., A, B, True, or text answer)">
                </div>
                <div class="form-group">
                    <label class="form-label">Marks</label>
                    <input type="number" name="questions[${questionCount}][marks]" class="form-control" min="1" value="1">
                </div>
            `;
            
            questionsList.appendChild(questionDiv);
        }

        function removeQuestion(id) {
            const questionDiv = document.getElementById('question-' + id);
            if (questionDiv) {
                questionDiv.remove();
            }
        }

        function toggleOptions(questionId, type) {
            const optionsDiv = document.getElementById('options-' + questionId);
            if (type === 'short_answer') {
                optionsDiv.style.display = 'none';
            } else {
                optionsDiv.style.display = 'grid';
            }
        }

        window.onload = function() {
            addQuestion();
        };
    </script>
</body>
</html>

