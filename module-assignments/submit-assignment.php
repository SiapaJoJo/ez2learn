<?php
session_start();

// Check authentication and role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../module-usermanagement/login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../module-usermanagement/login.php');
    exit();
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ez2learn';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$student_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get assignment ID
if (!isset($_GET['id'])) {
    header('Location: student-assignments.php');
    exit();
}

$assignment_id = (int)$_GET['id'];

// Fetch assignment details
$stmt = mysqli_prepare($conn, "
    SELECT a.*, u.username as staff_username, u.first_name as staff_first_name, u.last_name as staff_last_name
    FROM assignments a
    LEFT JOIN users u ON a.created_by = u.id
    WHERE a.id = ? AND a.status = 'published'
");
mysqli_stmt_bind_param($stmt, "i", $assignment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assignment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$assignment) {
    $_SESSION['error'] = 'Assignment not found or not available.';
    header('Location: student-assignments.php');
    exit();
}

// Check if due date has passed
$is_past_due = strtotime($assignment['due_date']) < time();

// Check if student has already submitted
$check_stmt = mysqli_prepare($conn, "SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
mysqli_stmt_bind_param($check_stmt, "ii", $assignment_id, $student_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$already_submitted = mysqli_num_rows($check_result) > 0;
mysqli_stmt_close($check_stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_past_due && !$already_submitted) {
    
    if (!isset($_FILES['assignment_file']) || $_FILES['assignment_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please select a file to upload.';
    } else {
        $file = $_FILES['assignment_file'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload error. Please try again.';
        } else {
            // Validate file size (max 10MB)
            $max_size = 10 * 1024 * 1024; // 10MB in bytes
            if ($file['size'] > $max_size) {
                $error = 'File size exceeds 10MB limit.';
            } else {
                // Validate file type (common document types)
                $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    $error = 'Invalid file type. Allowed: PDF, DOC, DOCX, TXT, ZIP, RAR, JPG, PNG.';
                } else {
                    // Create uploads directory if it doesn't exist
                    $upload_dir = 'uploads/assignments/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $unique_filename = 'assignment_' . $assignment_id . '_student_' . $student_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $unique_filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        // Insert submission record
                        $insert_stmt = mysqli_prepare($conn, "
                            INSERT INTO assignment_submissions (assignment_id, student_id, file_path, submission_status)
                            VALUES (?, ?, ?, 'submitted')
                        ");
                        mysqli_stmt_bind_param($insert_stmt, "iis", $assignment_id, $student_id, $upload_path);
                        
                        if (mysqli_stmt_execute($insert_stmt)) {
                            $_SESSION['success'] = 'Assignment submitted successfully!';
                            header('Location: student-assignments.php');
                            exit();
                        } else {
                            // Remove uploaded file if database insert fails
                            unlink($upload_path);
                            $error = 'Failed to submit assignment. Please try again.';
                        }
                        mysqli_stmt_close($insert_stmt);
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
    <title>Submit Assignment - Ez2Learn</title>
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

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-menu {
            display: flex;
            gap: 10px;
            list-style: none;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .profile-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .profile-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .profile-dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a {
            display: block;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-menu a:first-child {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        .dropdown-menu a:last-child {
            border-bottom: none;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .dropdown-menu a:hover {
            background: #f8f9fa;
            color: #3198F8;
        }

        .dropdown-menu a.logout {
            color: #c33;
        }

        .dropdown-menu a.logout:hover {
            background: #fee;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .assignment-details {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .assignment-details h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .assignment-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .meta-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }

        .meta-value {
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }

        .due-date-warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .due-date-warning.urgent {
            background: #ffebee;
            border-color: #f44336;
        }

        .assignment-description {
            color: #666;
            line-height: 1.8;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }

        .submission-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .submission-card h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 25px;
        }

        .file-upload-area {
            border: 3px dashed #3198F8;
            border-radius: 15px;
            padding: 60px 40px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .file-upload-area:hover {
            background: #e8f4fd;
            border-color: #1e6bb8;
        }

        .file-upload-area.dragover {
            background: #e3f2fd;
            border-color: #1976D2;
        }

        .upload-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .file-upload-area h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .file-upload-area p {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .file-input {
            display: none;
        }

        .btn-choose {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(49, 152, 248, 0.4);
        }

        .btn-choose:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(49, 152, 248, 0.5);
        }

        .file-info {
            display: none;
            margin-top: 25px;
            padding: 20px;
            background: #e8f5e9;
            border-radius: 10px;
            border: 2px solid #4CAF50;
        }

        .file-info.show {
            display: block;
        }

        .file-name {
            font-size: 16px;
            font-weight: 600;
            color: #2e7d32;
            margin-bottom: 8px;
            word-break: break-all;
        }

        .file-size {
            font-size: 14px;
            color: #666;
        }

        .file-requirements {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 25px;
            border-left: 4px solid #3198F8;
        }

        .file-requirements h4 {
            color: #333;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .file-requirements ul {
            list-style: none;
            padding: 0;
        }

        .file-requirements li {
            padding: 8px 0;
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .file-requirements li::before {
            content: '✓';
            color: #4CAF50;
            font-weight: bold;
            font-size: 16px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: flex-end;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.5);
        }

        .btn-primary:disabled {
            background: #e0e0e0;
            color: #999;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .closed-message {
            background: #ffebee;
            border: 2px solid #f44336;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
        }

        .closed-message h2 {
            color: #c62828;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .closed-message p {
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .header-top {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }

            .submission-card {
                padding: 25px 20px;
            }

            .file-upload-area {
                padding: 40px 20px;
            }

            .assignment-meta {
                flex-direction: column;
                gap: 15px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div class="logo-text">Ez2Learn</div>
            <div class="header-right">
                <ul class="nav-menu">
                    <li><a href="../module-usermanagement/dashboard-student.php">Dashboard</a></li>
                    <li><a href="../module-managelearning/main.php">My Courses</a></li>
                    <li><a href="student-assignments.php">Assignments</a></li>
                    <li><a href="student-grades.php">Grades</a></li>
                </ul>
                <div class="profile-dropdown" id="profileDropdown">
                    <button class="profile-btn" onclick="toggleDropdown()">
                        <div class="profile-icon"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <span>▼</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="../module-usermanagement/edit-profile.php">Edit Profile</a>
                        <a href="../module-usermanagement/logout.php" class="logout">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="assignment-details">
            <h1><?php echo htmlspecialchars($assignment['title']); ?></h1>
            
            <div class="assignment-meta">
                <div class="meta-item">
                    <span class="meta-label">Due Date</span>
                    <span class="meta-value"><?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Due Time</span>
                    <span class="meta-value"><?php echo date('H:i', strtotime($assignment['due_date'])); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Total Marks</span>
                    <span class="meta-value"><?php echo htmlspecialchars($assignment['total_marks']); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Instructor</span>
                    <span class="meta-value">
                        <?php 
                        $staff_name = trim(($assignment['staff_first_name'] ?? '') . ' ' . ($assignment['staff_last_name'] ?? ''));
                        echo htmlspecialchars($staff_name ?: $assignment['staff_username']);
                        ?>
                    </span>
                </div>
            </div>

            <?php
            $time_diff = strtotime($assignment['due_date']) - time();
            $hours_remaining = floor($time_diff / 3600);
            $days_remaining = floor($time_diff / 86400);
            $minutes_remaining = floor(($time_diff % 3600) / 60);
            
            if (!$is_past_due):
                if ($days_remaining < 1): 
            ?>
                <div class="due-date-warning urgent">
                    <span style="font-size: 24px;">⚠️</span>
                    <div>
                        <strong>URGENT:</strong> 
                        <?php 
                        if ($hours_remaining > 0) {
                            echo $hours_remaining . ' hour(s) ' . $minutes_remaining . ' minute(s)';
                        } else {
                            echo $minutes_remaining . ' minute(s)';
                        }
                        ?> remaining! 
                        Submit your assignment as soon as possible.
                    </div>
                </div>
            <?php elseif ($days_remaining <= 2): ?>
                <div class="due-date-warning">
                    <span style="font-size: 24px;">⏰</span>
                    <div>
                        <strong>Reminder:</strong> <?php echo $days_remaining; ?> day(s) remaining until the deadline.
                    </div>
                </div>
            <?php endif; endif; ?>

            <div class="assignment-description">
                <h3 style="color: #333; margin-bottom: 10px;">Assignment Instructions:</h3>
                <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
            </div>

            <?php if (!empty($assignment['assignment_file'])): ?>
                <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 10px; border: 2px solid #2196F3;">
                    <h4 style="color: #1565c0; margin-bottom: 10px;">📎 Assignment Materials</h4>
                    <p style="color: #666; margin-bottom: 10px;">The instructor has provided additional materials for this assignment:</p>
                    <a href="download-file.php?type=assignment&id=<?php echo $assignment['id']; ?>" 
                       class="btn btn-primary" 
                       style="display: inline-block;">
                        📥 Download Assignment File
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($is_past_due): ?>
            <div class="closed-message">
                <h2>⏰ Submission Deadline Passed</h2>
                <p>The deadline for this assignment was <?php echo date('M d, Y H:i', strtotime($assignment['due_date'])); ?>.</p>
                <p>Submissions are no longer accepted.</p>
                <a href="student-assignments.php" class="btn btn-secondary">Back to Assignments</a>
            </div>
        <?php elseif ($already_submitted): ?>
            <div class="closed-message" style="background: #e8f5e9; border-color: #4CAF50;">
                <h2 style="color: #2e7d32;">✅ Assignment Already Submitted</h2>
                <p>You have already submitted this assignment.</p>
                <p>Check the assignments page to view your submission status and grades.</p>
                <a href="student-assignments.php" class="btn btn-secondary">Back to Assignments</a>
            </div>
        <?php else: ?>
            <div class="submission-card">
                <h2>Submit Your Assignment</h2>

                <form method="POST" action="" enctype="multipart/form-data" id="submissionForm">
                    <div class="file-upload-area" id="uploadArea">
                        <div class="upload-icon">📤</div>
                        <h3>Drag & Drop Your File Here</h3>
                        <p>or</p>
                        <button type="button" class="btn-choose" onclick="document.getElementById('fileInput').click();">
                            Choose File
                        </button>
                        <input 
                            type="file" 
                            id="fileInput" 
                            name="assignment_file" 
                            class="file-input"
                            accept=".pdf,.doc,.docx,.txt,.zip,.rar,.jpg,.jpeg,.png"
                            required
                        >
                    </div>

                    <div class="file-info" id="fileInfo">
                        <div class="file-name" id="fileName"></div>
                        <div class="file-size" id="fileSize"></div>
                    </div>

                    <div class="file-requirements">
                        <h4>📋 File Requirements</h4>
                        <ul>
                            <li>Maximum file size: 10 MB</li>
                            <li>Allowed formats: PDF, DOC, DOCX, TXT, ZIP, RAR, JPG, PNG</li>
                            <li>Ensure your file is properly named and readable</li>
                            <li>Include your name or student ID in the filename</li>
                        </ul>
                    </div>

                    <div class="form-actions">
                        <a href="student-assignments.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            Submit Assignment
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const uploadArea = document.getElementById('uploadArea');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const submitBtn = document.getElementById('submitBtn');

        // File selection handling
        fileInput.addEventListener('change', function() {
            handleFile(this.files[0]);
        });

        // Drag and drop handling
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFile(files[0]);
            }
        });

        function handleFile(file) {
            if (!file) return;

            // Display file info
            fileName.textContent = '📎 ' + file.name;
            fileSize.textContent = 'Size: ' + formatFileSize(file.size);
            fileInfo.classList.add('show');
            submitBtn.disabled = false;

            // Validate file size
            const maxSize = 10 * 1024 * 1024; // 10MB
            if (file.size > maxSize) {
                alert('File size exceeds 10MB limit. Please choose a smaller file.');
                resetFileInput();
                return;
            }

            // Validate file type
            const allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (!allowedExtensions.includes(fileExtension)) {
                alert('Invalid file type. Allowed: PDF, DOC, DOCX, TXT, ZIP, RAR, JPG, PNG');
                resetFileInput();
                return;
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        function resetFileInput() {
            fileInput.value = '';
            fileInfo.classList.remove('show');
            submitBtn.disabled = true;
        }

        // Form submission confirmation
        document.getElementById('submissionForm').addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to submit this assignment? You cannot resubmit once submitted.')) {
                e.preventDefault();
                return false;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
        });

        function toggleDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html>
