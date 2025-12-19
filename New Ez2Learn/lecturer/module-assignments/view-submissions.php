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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions - Lecturer - Ez2Learn</title>
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
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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

        .submissions-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .submission-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
            display: inline-block;
            padding: 8px 16px;
            background: #3198F8;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 10px;
        }

        .file-link:hover {
            background: #1e6bb8;
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
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
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
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-grade:hover {
            background: #059669;
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

        .marks-display {
            font-size: 24px;
            font-weight: bold;
            color: #3198F8;
        }

        @media (max-width: 768px) {
            .header-top {
                padding: 15px 20px;
            }

            .form-row {
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
        <div class="page-header">
            <h1><?php echo htmlspecialchars($assignment['title']); ?></h1>
            <p style="color: #666; margin-top: 5px;"><?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?></p>
            <a href="index.php" class="btn-back">← Back to Assignments</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (empty($submissions)): ?>
            <div style="background: white; border-radius: 15px; padding: 40px; text-align: center; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
                <p style="color: #6b7280;">No submissions yet for this assignment.</p>
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
</body>
</html>

