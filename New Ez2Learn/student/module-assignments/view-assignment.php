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

$page_title = 'View Assignment';
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

        .assignment-details {
            margin-bottom: 30px;
        }

        .detail-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-section:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            color: #1e293b;
            font-size: 0.9375rem;
            line-height: 1.6;
        }

        .submission-section {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1.5rem;
            border: 2px solid #e5e7eb;
        }

        .marks-display {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            margin: 1rem 0;
        }

        .btn-action {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(16, 185, 129, 0.4);
        }

        .btn-download {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .content {
                padding: 1.5rem;
            }

            .page-header {
                padding: 1.25rem 1.5rem;
            }
        }
    </style>

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <a href="index.php" class="btn-back">← Back to Assignments</a>
                <h1 class="page-title"><?php echo htmlspecialchars($assignment['title']); ?></h1>
            </div>

            <div class="content">
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

                <?php if (!empty($assignment['instruction_file'])): ?>
                    <div class="detail-section">
                        <div class="detail-label">Instruction File</div>
                        <div class="detail-value">
                            <a href="../../<?php echo htmlspecialchars($assignment['instruction_file']); ?>" target="_blank" class="btn-action btn-download" style="margin-top: 10px;">📎 Download Instructions</a>
                        </div>
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
    </div>
</body>
</html>

