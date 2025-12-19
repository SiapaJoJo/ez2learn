<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'student') {
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

$student_id = $_SESSION['user_id'] ?? 0;
$quiz_id = (int)($_GET['quiz_id'] ?? 0);

// Get quiz details and verify enrollment
$quiz_query = "
    SELECT q.*, c.course_code, c.course_name
    FROM quizzes q
    INNER JOIN enrollments e ON q.course_id = e.course_id
    LEFT JOIN courses c ON q.course_id = c.course_id
    WHERE q.quiz_id = ? AND e.student_id = ?
";
$stmt = mysqli_prepare($conn, $quiz_query);
mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$quiz = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$quiz) {
    header('Location: ../module-managelearning/index.php');
    exit();
}

// Check if already attempted
$check_attempt = mysqli_prepare($conn, "SELECT attempt_id, score FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?");
mysqli_stmt_bind_param($check_attempt, "ii", $quiz_id, $student_id);
mysqli_stmt_execute($check_attempt);
$attempt_result = mysqli_stmt_get_result($check_attempt);
$existing_attempt = mysqli_fetch_assoc($attempt_result);
mysqli_stmt_close($check_attempt);

// Get questions
$questions_query = "
    SELECT question_id, question_text, question_type, option_a, option_b, option_c, option_d, marks
    FROM quiz_questions
    WHERE quiz_id = ?
    ORDER BY question_order ASC
";
$stmt = mysqli_prepare($conn, $questions_query);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$questions = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$error = '';
$success = '';

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $answers = $_POST['answers'] ?? [];
    $total_score = 0;
    $total_marks = 0;
    
    foreach ($questions as $question) {
        $question_id = $question['question_id'];
        $total_marks += $question['marks'];
        $student_answer = trim($answers[$question_id] ?? '');
        $correct_answer = '';
        
        // Get correct answer
        $correct_query = mysqli_prepare($conn, "SELECT correct_answer FROM quiz_questions WHERE question_id = ?");
        mysqli_stmt_bind_param($correct_query, "i", $question_id);
        mysqli_stmt_execute($correct_query);
        $correct_result = mysqli_stmt_get_result($correct_query);
        $correct_row = mysqli_fetch_assoc($correct_result);
        $correct_answer = trim($correct_row['correct_answer']);
        mysqli_stmt_close($correct_query);
        
        // Check if answer is correct (case-insensitive for text answers)
        if (strtolower($student_answer) === strtolower($correct_answer)) {
            $total_score += $question['marks'];
        }
    }
    
    // Save attempt
    $answers_json = json_encode($answers);
    
    if ($existing_attempt) {
        // Update existing attempt
        $update_stmt = mysqli_prepare($conn, "
            UPDATE quiz_attempts 
            SET score = ?, answers = ?, attempted_at = NOW()
            WHERE attempt_id = ?
        ");
        mysqli_stmt_bind_param($update_stmt, "isi", $total_score, $answers_json, $existing_attempt['attempt_id']);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    } else {
        // Insert new attempt
        $insert_stmt = mysqli_prepare($conn, "
            INSERT INTO quiz_attempts (quiz_id, student_id, score, answers)
            VALUES (?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($insert_stmt, "iiis", $quiz_id, $student_id, $total_score, $answers_json);
        mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);
    }
    
    $success = "Quiz submitted! Your score: $total_score / $total_marks";
    header('Location: view-quiz-result.php?quiz_id=' . $quiz_id);
    exit();
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attempt Quiz - Student - Ez2Learn</title>
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
            max-width: 900px;
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
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .page-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .quiz-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .info-row {
            margin-bottom: 8px;
            font-size: 14px;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            display: inline-block;
            min-width: 120px;
        }

        .question-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 2px solid #e5e7eb;
        }

        .question-number {
            font-weight: 600;
            color: #3198F8;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .question-text {
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .options-list {
            margin-top: 15px;
        }

        .option-item {
            margin-bottom: 10px;
        }

        .option-label {
            display: flex;
            align-items: center;
            padding: 10px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .option-label:hover {
            border-color: #3198F8;
            background: #f0f7ff;
        }

        .option-label input[type="radio"] {
            margin-right: 10px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 10px;
        }

        .form-control:focus {
            outline: none;
            border-color: #3198F8;
            box-shadow: 0 0 0 4px rgba(49, 152, 248, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        @media (max-width: 768px) {
            .header-top {
                padding: 15px 20px;
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
                <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
                <div class="quiz-info">
                    <div class="info-row">
                        <span class="info-label">Course:</span>
                        <?php echo htmlspecialchars($quiz['course_code'] . ' - ' . $quiz['course_name']); ?>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Marks:</span>
                        <?php echo $quiz['total_marks'] ?? 'N/A'; ?>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Questions:</span>
                        <?php echo count($questions); ?>
                    </div>
                    <?php if ($existing_attempt): ?>
                        <div class="info-row" style="color: #10b981; font-weight: 600;">
                            You have already attempted this quiz. Score: <?php echo $existing_attempt['score']; ?> / <?php echo $quiz['total_marks']; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($questions)): ?>
                <div style="text-align: center; padding: 40px; color: #6b7280;">
                    <p>No questions available for this quiz.</p>
                </div>
            <?php else: ?>
                <?php if ($existing_attempt): ?>
                    <div class="alert alert-info">
                        You can retake this quiz. Your previous answers will be replaced.
                    </div>
                <?php endif; ?>

                <form method="POST" id="quizForm">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-item">
                            <div class="question-number">Question <?php echo $index + 1; ?> (<?php echo $question['marks']; ?> marks)</div>
                            <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>
                            
                            <?php if ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'true_false'): ?>
                                <div class="options-list">
                                    <?php if ($question['option_a']): ?>
                                        <div class="option-item">
                                            <label class="option-label">
                                                <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="A" required>
                                                <span>A. <?php echo htmlspecialchars($question['option_a']); ?></span>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($question['option_b']): ?>
                                        <div class="option-item">
                                            <label class="option-label">
                                                <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="B" required>
                                                <span>B. <?php echo htmlspecialchars($question['option_b']); ?></span>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($question['option_c']): ?>
                                        <div class="option-item">
                                            <label class="option-label">
                                                <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="C" required>
                                                <span>C. <?php echo htmlspecialchars($question['option_c']); ?></span>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($question['option_d']): ?>
                                        <div class="option-item">
                                            <label class="option-label">
                                                <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="D" required>
                                                <span>D. <?php echo htmlspecialchars($question['option_d']); ?></span>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($question['question_type'] === 'true_false'): ?>
                                        <div class="option-item">
                                            <label class="option-label">
                                                <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="True" required>
                                                <span>True</span>
                                            </label>
                                        </div>
                                        <div class="option-item">
                                            <label class="option-label">
                                                <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="False" required>
                                                <span>False</span>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <textarea name="answers[<?php echo $question['question_id']; ?>]" class="form-control" rows="4" required placeholder="Enter your answer here..."></textarea>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" name="submit_quiz" class="btn-submit">Submit Quiz</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

