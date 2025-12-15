<?php
session_start();

// Check authentication and role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../module-usermanagement/login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'staff') {
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

$staff_id = $_SESSION['user_id'];
$error = '';
$success = '';
$is_edit = false;
$assignment = null;

// Check if editing
if (isset($_GET['id'])) {
    $is_edit = true;
    $assignment_id = (int)$_GET['id'];
    
    // Fetch assignment - verify it belongs to this staff
    $stmt = mysqli_prepare($conn, "SELECT * FROM assignments WHERE id = ? AND created_by = ?");
    mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $staff_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $assignment = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$assignment) {
        $_SESSION['error'] = 'Assignment not found or unauthorized access.';
        header('Location: staff-assignments.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date_raw = trim($_POST['due_date'] ?? '');
    $total_marks = (int)($_POST['total_marks'] ?? 0);
    $status = $_POST['status'] ?? 'draft';
    
    // Format due_date to MySQL datetime format (YYYY-MM-DD HH:MM:SS)
    $due_date = '';
    if (!empty($due_date_raw)) {
        $timestamp = strtotime($due_date_raw);
        if ($timestamp !== false) {
            $due_date = date('Y-m-d H:i:s', $timestamp);
        }
    }
    
    // Validation
    if (empty($title)) {
        $error = 'Assignment title is required.';
    } elseif (strlen($title) < 5) {
        $error = 'Assignment title must be at least 5 characters.';
    } elseif (empty($description)) {
        $error = 'Assignment description is required.';
    } elseif (empty($due_date)) {
        $error = 'Due date and time are required.';
    } elseif (strtotime($due_date) < time()) {
        $error = 'Due date must be in the future.';
    } elseif ($total_marks <= 0) {
        $error = 'Total marks must be greater than 0.';
    } elseif ($total_marks > 1000) {
        $error = 'Total marks cannot exceed 1000.';
    } elseif (!in_array($status, ['draft', 'published', 'closed'])) {
        $error = 'Invalid status.';
    }
    
    // Handle file upload
    $file_path = null;
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['assignment_file'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $max_size = 20 * 1024 * 1024; // 20MB
            if ($file['size'] > $max_size) {
                $error = 'File size exceeds 20MB limit.';
            } else {
                $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'pptx', 'xlsx'];
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    $error = 'Invalid file type. Allowed: PDF, DOC, DOCX, TXT, ZIP, RAR, PPTX, XLSX.';
                } else {
                    $upload_dir = 'uploads/assignments/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $unique_filename = 'assignment_file_' . time() . '_' . $staff_id . '.' . $file_extension;
                    $file_path = $upload_dir . $unique_filename;
                    
                    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                        $error = 'Failed to upload file. Please try again.';
                        $file_path = null;
                    }
                }
            }
        }
    }
    
    if (empty($error)) {
        if ($is_edit) {
            // Update existing assignment
            if ($file_path) {
                // Delete old file if exists
                if (!empty($assignment['assignment_file']) && file_exists($assignment['assignment_file'])) {
                    unlink($assignment['assignment_file']);
                }
                $stmt = mysqli_prepare($conn, "
                    UPDATE assignments 
                    SET title = ?, description = ?, assignment_file = ?, due_date = ?, total_marks = ?, status = ?
                    WHERE id = ? AND created_by = ?
                ");
                mysqli_stmt_bind_param($stmt, "ssssisii", $title, $description, $file_path, $due_date, $total_marks, $status, $assignment_id, $staff_id);
            } else {
                $stmt = mysqli_prepare($conn, "
                    UPDATE assignments 
                    SET title = ?, description = ?, due_date = ?, total_marks = ?, status = ?
                    WHERE id = ? AND created_by = ?
                ");
                mysqli_stmt_bind_param($stmt, "sssisii", $title, $description, $due_date, $total_marks, $status, $assignment_id, $staff_id);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = 'Assignment updated successfully!';
                header('Location: staff-assignments.php');
                exit();
            } else {
                $error = 'Failed to update assignment. Please try again.';
            }
            mysqli_stmt_close($stmt);
        } else {
            // Create new assignment
            if ($file_path) {
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO assignments (title, description, assignment_file, due_date, total_marks, created_by, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($stmt, "ssssiss", $title, $description, $file_path, $due_date, $total_marks, $staff_id, $status);
            } else {
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO assignments (title, description, due_date, total_marks, created_by, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($stmt, "sssiss", $title, $description, $due_date, $total_marks, $staff_id, $status);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = 'Assignment created successfully!';
                header('Location: staff-assignments.php');
                exit();
            } else {
                $error = 'Failed to create assignment. Please try again.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

mysqli_close($conn);

// Set default due date (1 week from now)
$default_due_date = date('Y-m-d\TH:i', strtotime('+1 week'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit' : 'Create'; ?> Assignment - Ez2Learn</title>
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

        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group label .required {
            color: #f44336;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3198F8;
            background: white;
            box-shadow: 0 0 0 4px rgba(49, 152, 248, 0.1);
        }

        .form-group small {
            display: block;
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 35px;
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
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(49, 152, 248, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(49, 152, 248, 0.5);
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .char-counter {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
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

            .form-card {
                padding: 30px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
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
                    <li><a href="../module-usermanagement/dashboard-staff.php">Dashboard</a></li>
                    <li><a href="../module-managelearning/main.php">My Courses</a></li>
                    <li><a href="#">Students</a></li>
                    <li><a href="staff-assignments.php">Assignments</a></li>
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
        <div class="form-card">
            <div class="form-header">
                <h1><?php echo $is_edit ? 'Edit' : 'Create New'; ?> Assignment</h1>
                <p><?php echo $is_edit ? 'Update assignment details below' : 'Fill in the details to create a new assignment'; ?></p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="assignmentForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Assignment Title <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        placeholder="Enter assignment title"
                        required
                        maxlength="150"
                        value="<?php echo htmlspecialchars($assignment['title'] ?? ''); ?>"
                    >
                    <small>Give your assignment a clear, descriptive title (5-150 characters)</small>
                </div>

                <div class="form-group">
                    <label for="description">Assignment Description <span class="required">*</span></label>
                    <textarea 
                        id="description" 
                        name="description" 
                        placeholder="Provide detailed instructions for students..."
                        required
                    ><?php echo htmlspecialchars($assignment['description'] ?? ''); ?></textarea>
                    <div class="char-counter">
                        <span id="charCount">0</span> characters
                    </div>
                    <small>Provide clear instructions, requirements, and grading criteria</small>
                </div>

                <div class="form-group">
                    <label for="assignment_file">Assignment File (Optional)</label>
                    <input 
                        type="file" 
                        id="assignment_file" 
                        name="assignment_file"
                        accept=".pdf,.doc,.docx,.txt,.zip,.rar,.pptx,.xlsx"
                    >
                    <small>Upload reference materials, guidelines, or resources (Max 20MB. Formats: PDF, DOC, DOCX, TXT, ZIP, RAR, PPTX, XLSX)</small>
                    <?php if ($is_edit && !empty($assignment['assignment_file'])): ?>
                        <div style="margin-top: 10px; padding: 10px; background: #e3f2fd; border-radius: 6px; font-size: 14px;">
                            📎 Current file: <strong><?php echo basename($assignment['assignment_file']); ?></strong>
                            <small style="display: block; margin-top: 5px; color: #666;">Upload a new file to replace it</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="due_date_picker">Due Date <span class="required">*</span></label>
                        <input 
                            type="date" 
                            id="due_date_picker" 
                            required
                            min="<?php echo date('Y-m-d'); ?>"
                            value="<?php echo $is_edit ? date('Y-m-d', strtotime($assignment['due_date'])) : date('Y-m-d', strtotime('+1 week')); ?>"
                        >
                        <small>Select the due date</small>
                    </div>

                    <div class="form-group">
                        <label for="due_time">Due Time <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="due_time" 
                            required
                            pattern="([01][0-9]|2[0-3]):[0-5][0-9]"
                            placeholder="22:48"
                            maxlength="5"
                            value="<?php echo $is_edit ? date('H:i', strtotime($assignment['due_date'])) : '23:59'; ?>"
                        >
                        <small>Use 24-hour format: HH:MM (e.g., 22:48 for 10:48 PM, 00:00 to 23:59)</small>
                    </div>
                </div>

                <input type="hidden" id="due_date" name="due_date" value="">

                    <div class="form-group">
                        <label for="total_marks">Total Marks <span class="required">*</span></label>
                        <input 
                            type="number" 
                            id="total_marks" 
                            name="total_marks" 
                            placeholder="100"
                            required
                            min="1"
                            max="1000"
                            value="<?php echo htmlspecialchars($assignment['total_marks'] ?? ''); ?>"
                        >
                        <small>Maximum marks for this assignment (1-1000)</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Assignment Status <span class="required">*</span></label>
                    <select id="status" name="status" required>
                        <option value="draft" <?php echo (!$is_edit || $assignment['status'] === 'draft') ? 'selected' : ''; ?>>
                            Draft (Not visible to students)
                        </option>
                        <option value="published" <?php echo ($is_edit && $assignment['status'] === 'published') ? 'selected' : ''; ?>>
                            Published (Visible to students)
                        </option>
                        <option value="closed" <?php echo ($is_edit && $assignment['status'] === 'closed') ? 'selected' : ''; ?>>
                            Closed (No more submissions)
                        </option>
                    </select>
                    <small>
                        <strong>Draft:</strong> Only you can see it. 
                        <strong>Published:</strong> Students can view and submit. 
                        <strong>Closed:</strong> No more submissions accepted.
                    </small>
                </div>

                <div class="form-actions">
                    <a href="staff-assignments.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $is_edit ? 'Update Assignment' : 'Create Assignment'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Character counter
        const description = document.getElementById('description');
        const charCount = document.getElementById('charCount');
        
        function updateCharCount() {
            charCount.textContent = description.value.length;
        }
        
        description.addEventListener('input', updateCharCount);
        updateCharCount();

        // Form validation and date/time combination
        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            // Combine date and time into datetime format
            const dateVal = document.getElementById('due_date_picker').value;
            const timeVal = document.getElementById('due_time').value;
            
            if (dateVal && timeVal) {
                document.getElementById('due_date').value = dateVal + ' ' + timeVal + ':00';
            }
            
            const title = document.getElementById('title').value.trim();
            const desc = document.getElementById('description').value.trim();
            const dueDate = document.getElementById('due_date').value;
            const totalMarks = parseInt(document.getElementById('total_marks').value);
            
            if (title.length < 5) {
                e.preventDefault();
                alert('Assignment title must be at least 5 characters long.');
                return false;
            }
            
            if (desc.length < 1) {
                e.preventDefault();
                alert('Assignment description is required.');
                return false;
            }
            
            if (!dueDate) {
                e.preventDefault();
                alert('Please select a due date and time.');
                return false;
            }
            
            const dueDateObj = new Date(dueDate);
            const now = new Date();
            
            if (dueDateObj <= now) {
                e.preventDefault();
                alert('Due date must be in the future.');
                return false;
            }
            
            if (totalMarks < 1 || totalMarks > 1000) {
                e.preventDefault();
                alert('Total marks must be between 1 and 1000.');
                return false;
            }
        });

        // Time input validation and formatting
        const timeInput = document.getElementById('due_time');
        
        timeInput.addEventListener('blur', function() {
            let value = this.value.replace(/[^0-9:]/g, '');
            
            // Auto-format if user enters without colon
            if (value.length === 4 && !value.includes(':')) {
                value = value.substring(0, 2) + ':' + value.substring(2);
            }
            
            // Validate format
            const timeRegex = /^([01][0-9]|2[0-3]):([0-5][0-9])$/;
            if (value && !timeRegex.test(value)) {
                alert('Invalid time format. Please use 24-hour format HH:MM (e.g., 22:48)\n\nValid range: 00:00 to 23:59');
                this.focus();
                return;
            }
            
            this.value = value;
        });
        
        // Auto-insert colon while typing
        timeInput.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            
            if (value.length >= 2) {
                value = value.substring(0, 2) + ':' + value.substring(2, 4);
            }
            
            this.value = value.substring(0, 5);
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
