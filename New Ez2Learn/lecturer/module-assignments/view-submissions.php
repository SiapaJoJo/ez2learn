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
$assignment_id = (int)($_GET['assignment_id'] ?? 0);

$assignment_query = "
    SELECT a.*, c.course_code, c.course_name
    FROM assignments a
    LEFT JOIN courses c ON a.course_id = c.course_id
    WHERE a.assignment_id = ? AND a.lecturer_id = ?
";
$stmt = mysqli_prepare($conn, $assignment_query);
mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $lecturer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assignment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$assignment) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade'])) {
    $submission_id = (int)($_POST['submission_id'] ?? 0);
    $marks = isset($_POST['marks']) ? (int)$_POST['marks'] : null;
    $feedback = trim($_POST['feedback'] ?? '');
    
    if ($marks === null || $marks < 0) {
        $error = 'Please enter valid marks (0 or greater).';
    } elseif ($marks > $assignment['total_marks']) {
        $error = 'Marks cannot exceed total marks (' . $assignment['total_marks'] . ').';
    } else {
        $update_stmt = mysqli_prepare($conn, "
            UPDATE submissions 
            SET marks = ?, feedback = ?
            WHERE submission_id = ? AND assignment_id = ?
        ");
        mysqli_stmt_bind_param($update_stmt, "isii", $marks, $feedback, $submission_id, $assignment_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success = 'Submission graded successfully!';
        } else {
            $error = 'Failed to grade submission.';
        }
        mysqli_stmt_close($update_stmt);
    }
}

$submissions_query = "
    SELECT 
        s.*,
        u.name as student_name, u.email as student_email
    FROM submissions s
    JOIN users u ON s.student_id = u.user_id
    WHERE s.assignment_id = ?
    ORDER BY s.submitted_at DESC
";
$stmt = mysqli_prepare($conn, $submissions_query);
mysqli_stmt_bind_param($stmt, "i", $assignment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$submissions = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

mysqli_close($conn);

$page_title = 'View Submissions';
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
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 0.875rem;
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
        }

        .btn-back:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        .content {
            padding: 2rem;
        }

        .submissions-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .submission-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .submission-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }

        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .student-info h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .student-info p {
            color: #666;
            font-size: 14px;
        }

        .submission-meta {
            text-align: right;
        }

        .submission-meta p {
            color: #666;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-graded {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .submission-content {
            margin-bottom: 20px;
        }

        .file-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .file-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
        }

        .grading-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 14px;
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

        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 15px;
        }

        .btn-grade {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-grade:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
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

        .marks-display {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
            background: white;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }

        @media (max-width: 768px) {
            .content {
                padding: 1.5rem;
            }

            .page-header {
                padding: 1.25rem 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <a href="index.php" class="btn-back">← Back to Assignments</a>
                <h1 class="page-title"><?php echo htmlspecialchars($assignment['title']); ?></h1>
                <p class="page-subtitle"><?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?></p>
            </div>

            <div class="content">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (empty($submissions)): ?>
                    <div class="empty-state">
                        <p>No submissions yet for this assignment.</p>
                    </div>
                <?php else: ?>
                    <div class="submissions-list">
                <?php foreach ($submissions as $submission): ?>
                    <div class="submission-card">
                        <div class="submission-header">
                            <div class="student-info">
                                <h3><?php echo htmlspecialchars($submission['student_name']); ?></h3>
                                <p><?php echo htmlspecialchars($submission['student_email']); ?></p>
                            </div>
                            <div class="submission-meta">
                                <p>Submitted: <?php echo date('M d, Y H:i', strtotime($submission['submitted_at'])); ?></p>
                                <?php if ($submission['marks'] !== null): ?>
                                    <span class="badge badge-graded">Graded</span>
                                    <div class="marks-display"><?php echo $submission['marks']; ?> / <?php echo $assignment['total_marks']; ?></div>
                                <?php else: ?>
                                    <span class="badge badge-pending">Pending</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="submission-content">
                            <?php if ($submission['file_path']): ?>
                                <a href="../../<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank" class="file-link">📄 Download Submission</a>
                            <?php else: ?>
                                <p style="color: #999;">No file uploaded</p>
                            <?php endif; ?>
                        </div>

                        <div class="grading-form">
                            <form method="POST">
                                <input type="hidden" name="submission_id" value="<?php echo $submission['submission_id']; ?>">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Marks (out of <?php echo $assignment['total_marks']; ?>)</label>
                                        <input type="number" name="marks" class="form-control" min="0" max="<?php echo $assignment['total_marks']; ?>" value="<?php echo $submission['marks'] ?? ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Feedback</label>
                                        <textarea name="feedback" class="form-control" placeholder="Enter feedback for the student..."><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <button type="submit" name="grade" class="btn-grade"><?php echo $submission['marks'] !== null ? 'Update Grade' : 'Grade Submission'; ?></button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

