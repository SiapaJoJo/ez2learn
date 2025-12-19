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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result - Student - Ez2Learn</title>
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

        .result-header {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .result-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .score-display {
            font-size: 48px;
            font-weight: bold;
            margin: 20px 0;
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

        .answer-section {
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
        }

        .answer-correct {
            background: #d1fae5;
            border: 2px solid #10b981;
        }

        .answer-incorrect {
            background: #fee2e2;
            border: 2px solid #ef4444;
        }

        .answer-label {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .btn-back {
            background: #3198F8;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }

        .btn-back:hover {
            background: #1e6bb8;
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
            <div class="result-header">
                <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
                <div class="score-display">
                    <?php echo $attempt['score']; ?> / <?php echo $quiz['total_marks']; ?>
                </div>
                <p>Your Score</p>
                <p style="margin-top: 10px; opacity: 0.9;">Submitted: <?php echo date('M d, Y H:i', strtotime($attempt['attempted_at'])); ?></p>
            </div>

            <h2 style="margin-bottom: 20px; color: #333;">Review Your Answers</h2>

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
                <a href="../module-managelearning/index.php" class="btn-back">Back to Courses</a>
            </div>
        </div>
    </div>
</body>
</html>

