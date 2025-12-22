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
$error = '';
$success = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
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

        $verify_stmt = mysqli_prepare($conn, "
            SELECT course_id FROM course_lecturers 
            WHERE course_id = ? AND lecturer_id = ?
        ");
        mysqli_stmt_bind_param($verify_stmt, "ii", $course_id, $lecturer_id);
        mysqli_stmt_execute($verify_stmt);
        $verify_result = mysqli_stmt_get_result($verify_stmt);
        
        if (mysqli_num_rows($verify_result) > 0) {
            $instruction_file = null;
            
            // Handle file upload
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
                        $instruction_file = 'uploads/assignments/instructions/' . $file_name;
                    }
                } else {
                    $error = 'Invalid file type. Allowed: PDF, DOC, DOCX, TXT, ZIP, RAR';
                }
            }
            
            if (empty($error)) {
                $insert_stmt = mysqli_prepare($conn, "
                    INSERT INTO assignments (course_id, lecturer_id, title, description, due_date, total_marks, instruction_file)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($insert_stmt, "iisssss", $course_id, $lecturer_id, $title, $description, $due_date, $total_marks, $instruction_file);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $success = 'Assignment created successfully!';
                    $_POST = array(); // Clear form
                } else {
                    $error = 'Failed to create assignment.';
                }
                mysqli_stmt_close($insert_stmt);
            }
        } else {
            $error = 'You are not assigned to this course.';
        }
        mysqli_stmt_close($verify_stmt);
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment - Lecturer - Ez2Learn</title>
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
        }

        .btn-back:hover {
            background: #4b5563;
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

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn-submit {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(49, 152, 248, 0.4);
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
                <h1>Create Assignment</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Course *</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>" <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $course['course_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Instruction File (Optional)</label>
                    <input type="file" name="instruction_file" class="form-control" accept=".pdf,.doc,.docx,.txt,.zip,.rar">
                    <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">Allowed formats: PDF, DOC, DOCX, TXT, ZIP, RAR (Max 10MB)</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Total Marks</label>
                    <input type="number" name="total_marks" class="form-control" min="1" value="<?php echo isset($_POST['total_marks']) ? htmlspecialchars($_POST['total_marks']) : '100'; ?>">
                </div>

                <button type="submit" name="create" class="btn-submit">Create Assignment</button>
            </form>
        </div>
    </div>
</body>
</html>

