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
    if (!isset($_FILES['assignment_file']) || $_FILES['assignment_file']['error'] === UPLOAD_ERR_NO_FILE) {
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
                        } else {

                            $insert_stmt = mysqli_prepare($conn, "
                                INSERT INTO submissions (assignment_id, student_id, file_path)
                                VALUES (?, ?, ?)
                            ");
                            mysqli_stmt_bind_param($insert_stmt, "iis", $assignment_id, $student_id, $file_path);
                            mysqli_stmt_execute($insert_stmt);
                            mysqli_stmt_close($insert_stmt);
                        }
                        
                        $success = 'Assignment submitted successfully!';
                    } else {
                        $error = 'Failed to upload file. Please try again.';
                    }
                }
            }
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Assignment - Student - Ez2Learn</title>
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
            max-width: 800px;
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

        .assignment-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .info-row {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            display: inline-block;
            min-width: 120px;
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
                <a href="index.php" class="btn-back">← Back</a>
                <h1>Submit Assignment</h1>
            </div>

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

            <?php if ($existing_submission): ?>
                <div class="alert alert-info">
                    <strong>You have already submitted this assignment.</strong> Uploading a new file will replace your previous submission.
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Upload Assignment File *</label>
                    <input type="file" name="assignment_file" class="form-control" required accept=".pdf,.doc,.docx,.txt,.zip,.rar,.jpg,.jpeg,.png">
                    <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                        Allowed file types: PDF, DOC, DOCX, TXT, ZIP, RAR, JPG, PNG (Max 10MB)
                    </small>
                </div>

                <button type="submit" name="submit" class="btn-submit"><?php echo $existing_submission ? 'Resubmit Assignment' : 'Submit Assignment'; ?></button>
            </form>
        </div>
    </div>
</body>
</html>

