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

$quiz_query = "
    SELECT q.*, c.course_code, c.course_name
    FROM quizzes q
    LEFT JOIN courses c ON q.course_id = c.course_id
    WHERE q.quiz_id = ?
";
$stmt = mysqli_prepare($conn, $quiz_query);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$quiz = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$quiz) {
    header('Location: index.php');
    exit();
}

$attempt_query = "SELECT * FROM quiz_attempts WHERE quiz_id = ? AND student_id = ? ORDER BY attempted_at DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $attempt_query);
mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attempt = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$attempt) {
    header('Location: attempt-quiz.php?quiz_id=' . $quiz_id);
    exit();
}

$questions_query = "
    SELECT question_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer, marks
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

$student_answers = json_decode($attempt['answers'] ?? '{}', true);

mysqli_close($conn);

$page_title = 'Quiz Result';
require_once '../../includes/header-student.php';
?>
    <style>
        .page-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .content {
            padding: 2rem;
        }

        .result-header {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
        }

        .result-header h1 {
            font-size: 1.75rem;
            margin-bottom: 0.75rem;
            font-weight: 700;
        }

        .score-display {
            font-size: 3rem;
            font-weight: 700;
            margin: 1.25rem 0;
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

        .question-number {
            font-weight: 600;
            color: #667eea;
            font-size: 1rem;
            margin-bottom: 0.75rem;
        }

        .question-text {
            font-size: 0.9375rem;
            color: #1e293b;
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .answer-section {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 8px;
        }

        .answer-correct {
            background: #ecfdf5;
            border: 2px solid #10b981;
        }

        .answer-incorrect {
            background: #fef2f2;
            border: 2px solid #ef4444;
        }

        .answer-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            margin-top: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .content {
                padding: 1.5rem;
            }

            .score-display {
                font-size: 2rem;
            }
        }
    </style>

    <div class="container">
        <div class="page-container">
            <div class="content">
                <div class="result-header">
                <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
                <div class="score-display">
                    <?php echo $attempt['score']; ?> / <?php echo $quiz['total_marks']; ?>
                </div>
                <p>Your Score</p>
                    <p style="margin-top: 0.75rem; opacity: 0.9; font-size: 0.875rem;">Submitted: <?php echo date('M d, Y H:i', strtotime($attempt['attempted_at'])); ?></p>
                </div>

                <h2 style="margin-bottom: 1.5rem; color: #1e293b; font-size: 1.25rem; font-weight: 700;">Review Your Answers</h2>

            <?php foreach ($questions as $index => $question): ?>
                <?php
                $student_answer = $student_answers[$question['question_id']] ?? '';
                $correct_answer = $question['correct_answer'];
                $is_correct = (strtolower(trim($student_answer)) === strtolower(trim($correct_answer)));
                ?>
                <div class="question-item">
                    <div class="question-number">Question <?php echo $index + 1; ?> (<?php echo $question['marks']; ?> marks)</div>
                    <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>
                    
                    <div class="answer-section <?php echo $is_correct ? 'answer-correct' : 'answer-incorrect'; ?>">
                        <div class="answer-label">Your Answer:</div>
                        <div><?php echo htmlspecialchars($student_answer ?: 'Not answered'); ?></div>
                        <div class="answer-label" style="margin-top: 10px;">Correct Answer:</div>
                        <div><?php echo htmlspecialchars($correct_answer); ?></div>
                        <?php if ($is_correct): ?>
                            <div style="color: #065f46; font-weight: 600; margin-top: 10px;">✓ Correct</div>
                        <?php else: ?>
                            <div style="color: #991b1b; font-weight: 600; margin-top: 10px;">✗ Incorrect</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <div style="text-align: center;">
                    <a href="../module-managelearning/index.php" class="btn-back">← Back to Courses</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

