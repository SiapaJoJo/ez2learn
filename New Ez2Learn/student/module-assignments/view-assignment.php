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
$assignment_id = (int)($_GET['assignment_id'] ?? 0);

$assignment_query = "
    SELECT 
        a.*, 
        c.course_code, c.course_name,
        s.submission_id, s.file_path as submission_file, s.marks, s.feedback, s.submitted_at
    FROM assignments a
    INNER JOIN enrollments e ON a.course_id = e.course_id
    LEFT JOIN courses c ON a.course_id = c.course_id
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = ?
    WHERE a.assignment_id = ? AND e.student_id = ?
";
$stmt = mysqli_prepare($conn, $assignment_query);
mysqli_stmt_bind_param($stmt, "iii", $student_id, $assignment_id, $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assignment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$assignment) {
    header('Location: index.php');
    exit();
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Assignment - Student - Ez2Learn</title>
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
            margin-top: 10px;
        }

        .btn-back:hover {
            background: #4b5563;
        }

        .assignment-details {
            margin-bottom: 30px;
        }

        .detail-section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-section:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .detail-value {
            color: #333;
            font-size: 16px;
            line-height: 1.6;
        }

        .submission-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .marks-display {
            font-size: 24px;
            font-weight: bold;
            color: #3198F8;
            margin: 15px 0;
        }

        .btn-action {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }

        .btn-submit {
            background: #10b981;
            color: white;
        }

        .btn-submit:hover {
            background: #059669;
        }

        .btn-download {
            background: #3b82f6;
            color: white;
        }

        .btn-download:hover {
            background: #2563eb;
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
                <a href="index.php" class="btn-back">← Back to Assignments</a>
                <h1><?php echo htmlspecialchars($assignment['title']); ?></h1>
            </div>

            <div class="assignment-details">
                <div class="detail-section">
                    <div class="detail-label">Course</div>
                    <div class="detail-value"><?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?></div>
                </div>

                <?php if ($assignment['description']): ?>
                    <div class="detail-section">
                        <div class="detail-label">Description</div>
                        <div class="detail-value" style="white-space: pre-wrap;"><?php echo htmlspecialchars($assignment['description']); ?></div>
                    </div>
                <?php endif; ?>

                <div class="detail-section">
                    <div class="detail-label">Due Date</div>
                    <div class="detail-value">
                        <?php 
                        if ($assignment['due_date']) {
                            $due_date = strtotime($assignment['due_date']);
                            $now = time();
                            if ($due_date < $now) {
                                echo '<span style="color: #ef4444;">' . date('F d, Y H:i', $due_date) . ' (Overdue)</span>';
                            } else {
                                echo date('F d, Y H:i', $due_date);
                            }
                        } else {
                            echo 'No due date';
                        }
                        ?>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="detail-label">Total Marks</div>
                    <div class="detail-value"><?php echo $assignment['total_marks'] ?? 'N/A'; ?></div>
                </div>

                <?php if ($assignment['submission_id']): ?>
                    <div class="submission-section">
                        <div class="detail-label">Your Submission</div>
                        <div class="detail-value" style="margin-top: 10px;">
                            <strong>Submitted:</strong> <?php echo date('M d, Y H:i', strtotime($assignment['submitted_at'])); ?>
                        </div>
                        <?php if ($assignment['submission_file']): ?>
                            <a href="../../<?php echo htmlspecialchars($assignment['submission_file']); ?>" target="_blank" class="btn-action btn-download">Download Your Submission</a>
                        <?php endif; ?>
                        
                        <?php if ($assignment['marks'] !== null): ?>
                            <div class="marks-display">
                                Marks: <?php echo $assignment['marks']; ?> / <?php echo $assignment['total_marks']; ?>
                                <?php 
                                $percentage = ($assignment['marks'] / $assignment['total_marks']) * 100;
                                echo '(' . round($percentage, 1) . '%)';
                                ?>
                            </div>
                            <?php if ($assignment['feedback']): ?>
                                <div class="detail-section" style="border: none; padding: 0; margin-top: 15px;">
                                    <div class="detail-label">Feedback</div>
                                    <div class="detail-value" style="white-space: pre-wrap;"><?php echo htmlspecialchars($assignment['feedback']); ?></div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p style="color: #666; margin-top: 10px;">Your submission is pending grading.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="margin-top: 20px;">
                        <a href="submit-assignment.php?assignment_id=<?php echo $assignment['assignment_id']; ?>" class="btn-action btn-submit">Submit Assignment</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

