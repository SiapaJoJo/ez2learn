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

require_once '../../includes/db-config.php';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$student_id = $_SESSION['user_id'] ?? 0;
$assignment_id = (int)($_GET['assignment_id'] ?? 0);

$assignment_query = "
    SELECT a.*, c.course_code, c.course_name
    FROM assignments a
    INNER JOIN enrollments e ON a.course_id = e.course_id
    LEFT JOIN courses c ON a.course_id = c.course_id
    WHERE a.assignment_id = ? AND e.student_id = ?
";
$stmt = mysqli_prepare($conn, $assignment_query);
mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assignment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$assignment) {
    header('Location: index.php');
    exit();
}

$check_submission = mysqli_prepare($conn, "SELECT submission_id, file_path FROM submissions WHERE assignment_id = ? AND student_id = ?");
mysqli_stmt_bind_param($check_submission, "ii", $assignment_id, $student_id);
mysqli_stmt_execute($check_submission);
$submission_result = mysqli_stmt_get_result($check_submission);
$existing_submission = mysqli_fetch_assoc($submission_result);
mysqli_stmt_close($check_submission);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Check if due date has passed
    $is_overdue = false;
    if (!empty($assignment['due_date'])) {
        $due_datetime = strtotime($assignment['due_date'] . ' 23:59:59');
        $is_overdue = time() > $due_datetime;
    }
    
    if (isset($assignment['status']) && $assignment['status'] === 'closed') {
        $error = 'This assignment is closed. Submissions are no longer accepted.';
    } elseif ($is_overdue) {
        $error = 'The submission deadline has passed. You can no longer submit or resubmit this assignment.';
    } elseif (!isset($_FILES['assignment_file']) || $_FILES['assignment_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please select a file to upload.';
    } else {
        $file = $_FILES['assignment_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload error. Please try again.';
        } else {
            $max_size = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $max_size) {
                $error = 'File size exceeds 10MB limit.';
            } else {
                $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    $error = 'Invalid file type. Allowed: PDF, DOC, DOCX, TXT, ZIP, RAR, JPG, PNG.';
                } else {
                    $upload_dir = '../../uploads/assignments/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $unique_filename = 'assignment_' . $assignment_id . '_student_' . $student_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $unique_filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $file_path = 'uploads/assignments/' . $unique_filename;
                        
                        if ($existing_submission) {

                            if ($existing_submission['file_path'] && file_exists('../../' . $existing_submission['file_path'])) {
                                unlink('../../' . $existing_submission['file_path']);
                            }

                            $update_stmt = mysqli_prepare($conn, "
                                UPDATE submissions 
                                SET file_path = ?, submitted_at = NOW()
                                WHERE assignment_id = ? AND student_id = ?
                            ");
                            mysqli_stmt_bind_param($update_stmt, "sii", $file_path, $assignment_id, $student_id);
                            mysqli_stmt_execute($update_stmt);
                            mysqli_stmt_close($update_stmt);
                            $success = 'Assignment resubmitted successfully!';
                        } else {

                            $insert_stmt = mysqli_prepare($conn, "
                                INSERT INTO submissions (assignment_id, student_id, file_path)
                                VALUES (?, ?, ?)
                            ");
                            mysqli_stmt_bind_param($insert_stmt, "iis", $assignment_id, $student_id, $file_path);
                            mysqli_stmt_execute($insert_stmt);
                            mysqli_stmt_close($insert_stmt);
                            $success = 'Assignment submitted successfully!';
                        }
                    } else {
                        $error = 'Failed to upload file. Please try again.';
                    }
                }
            }
        }
    }
}

mysqli_close($conn);

$page_title = 'Submit Assignment';
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

        .assignment-info {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 2px solid #e5e7eb;
        }

        .info-row {
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
            display: inline-block;
            min-width: 120px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: #374151;
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

        .form-control small {
            display: block;
            margin-top: 0.5rem;
            color: #64748b;
            font-size: 0.75rem;
        }

        .btn-submit {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(16, 185, 129, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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

        .alert-info {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #1e40af;
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
                <a href="index.php" class="btn-back">← Back</a>
                <h1 class="page-title">Submit Assignment</h1>
            </div>

            <div class="content">

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="assignment-info">
                <div class="info-row">
                    <span class="info-label">Assignment:</span>
                    <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                </div>
                <div class="info-row">
                    <span class="info-label">Course:</span>
                    <?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?>
                </div>
                <?php if ($assignment['due_date']): ?>
                    <div class="info-row">
                        <span class="info-label">Due Date:</span>
                        <?php 
                        $due_date = strtotime($assignment['due_date']);
                        $now = time();
                        if ($due_date < $now) {
                            echo '<span style="color: #ef4444;">' . date('M d, Y H:i', $due_date) . ' (Overdue)</span>';
                        } else {
                            echo date('M d, Y H:i', $due_date);
                        }
                        ?>
                    </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Total Marks:</span>
                    <?php echo $assignment['total_marks'] ?? 'N/A'; ?>
                </div>
                <?php if ($assignment['description']): ?>
                    <div class="info-row" style="margin-top: 15px;">
                        <span class="info-label">Description:</span>
                        <div style="margin-top: 5px; color: #333;"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <?php 
            // Check if due date has passed
            $is_overdue = false;
            if (!empty($assignment['due_date'])) {
                $due_datetime = strtotime($assignment['due_date'] . ' 23:59:59');
                $is_overdue = time() > $due_datetime;
            }
            ?>
            <?php if (isset($assignment['status']) && $assignment['status'] === 'closed'): ?>
                <div class="alert alert-danger">
                    <strong>⚠️ This assignment is closed.</strong> Submissions are no longer accepted.
                </div>
            <?php elseif ($is_overdue): ?>
                <div class="alert alert-danger">
                    <strong>⚠️ Submission deadline has passed.</strong> You can no longer submit or resubmit this assignment.
                </div>
            <?php elseif ($existing_submission): ?>
                <div class="alert alert-info">
                    <strong>You have already submitted this assignment.</strong> You can still resubmit before the deadline. Uploading a new file will replace your previous submission.
                </div>
            <?php endif; ?>

            <?php
            $form_disabled = (isset($assignment['status']) && $assignment['status'] === 'closed') || $is_overdue;
            ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Upload Assignment File *</label>
                    <input type="file" name="assignment_file" class="form-control" required accept=".pdf,.doc,.docx,.txt,.zip,.rar,.jpg,.jpeg,.png" <?php echo $form_disabled ? 'disabled' : ''; ?>>
                    <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                        Allowed file types: PDF, DOC, DOCX, TXT, ZIP, RAR, JPG, PNG (Max 10MB)
                    </small>
                </div>

                <button type="submit" name="submit" class="btn-submit" <?php echo $form_disabled ? 'disabled' : ''; ?>><?php echo $existing_submission ? 'Resubmit Assignment' : 'Submit Assignment'; ?></button>
            </form>
            </div>
        </div>
    </div>
</body>
</html>

