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

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ez2learn';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$lecturer_id = $_SESSION['user_id'] ?? 0;
$error = '';
$success = '';

// Get assigned courses
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
        // Verify lecturer is assigned to this course
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
                
                // Handle questions
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
                
                // Update total marks if needed
                if ($total_question_marks > 0 && $total_question_marks != $total_marks) {
                    $update_stmt = mysqli_prepare($conn, "UPDATE quizzes SET total_marks = ? WHERE quiz_id = ?");
                    mysqli_stmt_bind_param($update_stmt, "ii", $total_question_marks, $quiz_id);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                }
                
                $success = 'Quiz created successfully!';
                header('Location: index.php');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz - Lecturer - Ez2Learn</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .header {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            padding: 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-top {
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo-text {
            font-size: 24px;
            font-weight: bold;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .btn-back {
            background: #6b7280;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-back:hover {
            background: #4b5563;
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
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3198F8;
            box-shadow: 0 0 0 4px rgba(49, 152, 248, 0.1);
        }

        .questions-container {
            margin-top: 30px;
        }

        .question-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 2px solid #e5e7eb;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .question-number {
            font-weight: 600;
            color: #3198F8;
            font-size: 16px;
        }

        .btn-remove {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
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
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-top: 20px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(49, 152, 248, 0.4);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        @media (max-width: 768px) {
            .header-top {
                padding: 15px 20px;
            }

            .options-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div class="logo-text">Ez2Learn</div>
        </div>
    </div>

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <a href="index.php" class="btn-back">← Back</a>
                <h1>Create Quiz</h1>
            </div>

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

        // Add first question by default
        window.onload = function() {
            addQuestion();
        };
    </script>
</body>
</html>

