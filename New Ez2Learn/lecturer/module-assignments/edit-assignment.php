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
$error = '';
$success = '';
$assignment_id = (int)($_GET['id'] ?? 0);

// Fetch assignment details
$stmt = mysqli_prepare($conn, "
    SELECT a.*, c.course_id, c.course_code, c.course_name
    FROM assignments a
    INNER JOIN courses c ON a.course_id = c.course_id
    WHERE a.assignment_id = ? AND a.lecturer_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $lecturer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assignment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$assignment) {
    header('Location: index.php');
    exit();
}

// Fetch courses for lecturer
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? null;
    $total_marks = (int)($_POST['total_marks'] ?? 100);
    
    if (empty($title)) {
        $error = 'Title is required.';
    } elseif ($course_id <= 0) {
        $error = 'Please select a course.';
    } else {
        // Verify lecturer has access to the course
        $verify_stmt = mysqli_prepare($conn, "
            SELECT course_id FROM course_lecturers 
            WHERE course_id = ? AND lecturer_id = ?
        ");
        mysqli_stmt_bind_param($verify_stmt, "ii", $course_id, $lecturer_id);
        mysqli_stmt_execute($verify_stmt);
        $verify_result = mysqli_stmt_get_result($verify_stmt);
        
        if (mysqli_num_rows($verify_result) > 0) {
            $instruction_file = $assignment['instruction_file'];
            
            // Handle file upload if new file is provided
            if (isset($_FILES['instruction_file']) && $_FILES['instruction_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/assignments/instructions/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['instruction_file']['name'], PATHINFO_EXTENSION);
                $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar'];
                
                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    $file_name = time() . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['instruction_file']['tmp_name'], $file_path)) {
                        // Delete old file if exists
                        if (!empty($instruction_file) && file_exists('../../' . $instruction_file)) {
                            unlink('../../' . $instruction_file);
                        }
                        $instruction_file = 'uploads/assignments/instructions/' . $file_name;
                    }
                } else {
                    $error = 'Invalid file type. Allowed: PDF, DOC, DOCX, TXT, ZIP, RAR';
                }
            }
            
            if (empty($error)) {
                $update_stmt = mysqli_prepare($conn, "
                    UPDATE assignments 
                    SET course_id = ?, title = ?, description = ?, due_date = ?, total_marks = ?, instruction_file = ?
                    WHERE assignment_id = ? AND lecturer_id = ?
                ");
                mysqli_stmt_bind_param($update_stmt, "isssisii", $course_id, $title, $description, $due_date, $total_marks, $instruction_file, $assignment_id, $lecturer_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $success = 'Assignment updated successfully!';
                    
                    // Refresh assignment data
                    $stmt = mysqli_prepare($conn, "
                        SELECT a.*, c.course_id, c.course_code, c.course_name
                        FROM assignments a
                        INNER JOIN courses c ON a.course_id = c.course_id
                        WHERE a.assignment_id = ? AND a.lecturer_id = ?
                    ");
                    mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $lecturer_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $assignment = mysqli_fetch_assoc($result);
                    mysqli_stmt_close($stmt);
                } else {
                    $error = 'Failed to update assignment.';
                }
                mysqli_stmt_close($update_stmt);
            }
        } else {
            $error = 'You are not assigned to this course.';
        }
        mysqli_stmt_close($verify_stmt);
    }
}

mysqli_close($conn);

$page_title = 'Edit Assignment';
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

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .form-control small {
            display: block;
            margin-top: 0.5rem;
            color: #64748b;
            font-size: 0.75rem;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
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

        .current-file {
            background: #f1f5f9;
            padding: 0.875rem 1rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            border: 1px solid #e2e8f0;
        }

        .current-file strong {
            color: #475569;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .current-file a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .current-file a:hover {
            color: #764ba2;
            text-decoration: underline;
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
                <h1 class="page-title">Edit Assignment</h1>
            </div>

            <div class="content">

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="editForm">
                <div class="form-group">
                    <label class="form-label">Course *</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>" <?php echo $assignment['course_id'] == $course['course_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($assignment['title']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"><?php echo htmlspecialchars($assignment['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Instruction File (Optional)</label>
                    <?php if (!empty($assignment['instruction_file'])): ?>
                        <div class="current-file">
                            <strong>Current file:</strong> 
                            <a href="../../<?php echo htmlspecialchars($assignment['instruction_file']); ?>" target="_blank">
                                <?php echo basename($assignment['instruction_file']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="instruction_file" class="form-control" accept=".pdf,.doc,.docx,.txt,.zip,.rar">
                    <small style="color: #64748b; font-size: 0.75rem; margin-top: 0.5rem; display: block;">Upload new file to replace the current one. Allowed formats: PDF, DOC, DOCX, TXT, ZIP, RAR (Max 10MB)</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="<?php echo htmlspecialchars($assignment['due_date'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Total Marks</label>
                    <input type="number" name="total_marks" class="form-control" min="1" value="<?php echo htmlspecialchars($assignment['total_marks']); ?>">
                </div>

                <button type="submit" name="update" class="btn-submit">Update Assignment</button>
            </form>
            </div>
        </div>
    </div>
</body>
</html>
