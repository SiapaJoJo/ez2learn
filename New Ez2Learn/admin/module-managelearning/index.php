<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'admin') {
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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $course_code = trim($_POST['course_code'] ?? '');
        $course_name = trim($_POST['course_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'open';
        
        if (empty($course_code) || empty($course_name)) {
            $error = 'Course code and name are required.';
        } else {
            $check_stmt = mysqli_prepare($conn, "SELECT course_id FROM courses WHERE course_code = ?");
            mysqli_stmt_bind_param($check_stmt, "s", $course_code);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error = 'Course code already exists.';
            } else {
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO courses (course_code, course_name, description, status)
                    VALUES (?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($stmt, "ssss", $course_code, $course_name, $description, $status);
                
                if (mysqli_stmt_execute($stmt)) {
                    $course_id = mysqli_insert_id($conn);
                    // Assign lecturer if provided
                    if (!empty($_POST['lecturer_id'])) {
                        $lecturer_id = (int)$_POST['lecturer_id'];
                        $assign_stmt = mysqli_prepare($conn, "INSERT INTO course_lecturers (course_id, lecturer_id) VALUES (?, ?)");
                        mysqli_stmt_bind_param($assign_stmt, "ii", $course_id, $lecturer_id);
                        mysqli_stmt_execute($assign_stmt);
                        mysqli_stmt_close($assign_stmt);
                    }
                    $success = 'Course created successfully!';
                } else {
                    $error = 'Failed to create course.';
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_stmt_close($check_stmt);
        }
    } elseif ($action === 'update') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        $course_code = trim($_POST['course_code'] ?? '');
        $course_name = trim($_POST['course_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'open';
        
        if ($course_id > 0 && !empty($course_code) && !empty($course_name)) {
            $check_stmt = mysqli_prepare($conn, "SELECT course_id FROM courses WHERE course_code = ? AND course_id != ?");
            mysqli_stmt_bind_param($check_stmt, "si", $course_code, $course_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error = 'Course code already exists.';
            } else {
                $stmt = mysqli_prepare($conn, "
                    UPDATE courses 
                    SET course_code = ?, course_name = ?, description = ?, status = ?
                    WHERE course_id = ?
                ");
                mysqli_stmt_bind_param($stmt, "ssssi", $course_code, $course_name, $description, $status, $course_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Course updated successfully!';
                } else {
                    $error = 'Failed to update course.';
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_stmt_close($check_stmt);
        }
    } elseif ($action === 'assign_lecturer') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        $lecturer_id = (int)($_POST['lecturer_id'] ?? 0);
        
        if ($course_id > 0 && $lecturer_id > 0) {
            // Remove existing assignments for this course
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM course_lecturers WHERE course_id = ?");
            mysqli_stmt_bind_param($delete_stmt, "i", $course_id);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);
            
            // Add new assignment
            $stmt = mysqli_prepare($conn, "INSERT INTO course_lecturers (course_id, lecturer_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ii", $course_id, $lecturer_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Lecturer assigned successfully!';
            } else {
                $error = 'Failed to assign lecturer.';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === 'toggle_enrollment') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? 'open';
        
        if ($course_id > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE courses SET status = ? WHERE course_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $new_status, $course_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Course enrollment status updated successfully!';
            } else {
                $error = 'Failed to update course status.';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === 'delete') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        
        if ($course_id > 0) {
            // Delete course lecturers first
            $delete_lecturers = mysqli_prepare($conn, "DELETE FROM course_lecturers WHERE course_id = ?");
            mysqli_stmt_bind_param($delete_lecturers, "i", $course_id);
            mysqli_stmt_execute($delete_lecturers);
            mysqli_stmt_close($delete_lecturers);
            
            // Delete course
            $stmt = mysqli_prepare($conn, "DELETE FROM courses WHERE course_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $course_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Course deleted successfully!';
            } else {
                $error = 'Failed to delete course.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get all courses with lecturer and enrollment info
$courses_query = "
    SELECT 
        c.course_id, c.course_code, c.course_name, c.description, c.status,
        GROUP_CONCAT(DISTINCT u.name SEPARATOR ', ') as lecturers,
        COUNT(DISTINCT e.student_id) as enrolled_students
    FROM courses c
    LEFT JOIN course_lecturers cl ON c.course_id = cl.course_id
    LEFT JOIN users u ON cl.lecturer_id = u.user_id
    LEFT JOIN enrollments e ON c.course_id = e.course_id
    GROUP BY c.course_id
    ORDER BY c.course_id DESC
";
$courses_result = mysqli_query($conn, $courses_query);
$courses = mysqli_fetch_all($courses_result, MYSQLI_ASSOC);

// Get all lecturers
$lecturers_query = "SELECT user_id, name, email FROM users WHERE role = 'lecturer' AND status = 'active' ORDER BY name";
$lecturers_result = mysqli_query($conn, $lecturers_query);
$lecturers = mysqli_fetch_all($lecturers_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Admin - Ez2Learn</title>
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

        .nav-menu a:hover, .nav-menu a.active {
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
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-container {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .page-header {
            padding: 30px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e3a5f;
        }

        .btn-add {
            background: #3198F8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-add:hover {
            background: #1e6bb8;
        }

        .content {
            padding: 30px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f3f4f6;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            color: #374151;
        }

        td {
            padding: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-open {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-closed {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }

        .btn-edit {
            background: #facc15;
            color: #111827;
        }

        .btn-assign {
            background: #3b82f6;
            color: white;
        }

        .btn-view {
            background: #10b981;
            color: white;
        }

        .btn-toggle {
            background: #8b5cf6;
            color: white;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
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
            min-height: 100px;
            resize: vertical;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin: 20px 30px;
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
                flex-direction: column;
                gap: 15px;
            }

            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
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
                    <li><a href="../index.php">Dashboard</a></li>
                    <li><a href="../module-usermanagement/index.php">Users</a></li>
                    <li><a href="index.php" class="active">Courses</a></li>
                    <li><a href="../module-assignments/index.php">Assignments</a></li>
                    <li><a href="../module-progress/index.php">Progress</a></li>
                </ul>
                <div class="profile-dropdown" id="profileDropdown">
                    <button class="profile-btn" onclick="toggleDropdown()">
                        <div class="profile-icon"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?></div>
                        <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                        <span>▼</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="../../edit-profile.php">Edit Profile</a>
                        <a href="../../logout.php" class="logout">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <h1 class="page-title">Manage Courses</h1>
                <button class="btn-add" onclick="openCreateModal()">+ Create Course</button>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="content">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Lecturer(s)</th>
                                <th>Enrolled Students</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($courses)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">No courses found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                        <td><?php echo htmlspecialchars($course['lecturers'] ?: 'Not Assigned'); ?></td>
                                        <td><?php echo $course['enrolled_students']; ?></td>
                                        <td><span class="badge badge-<?php echo $course['status']; ?>"><?php echo ucfirst($course['status']); ?></span></td>
                                        <td>
                                            <button class="btn-action btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($course)); ?>)">Edit</button>
                                            <button class="btn-action btn-assign" onclick="openAssignModal(<?php echo $course['course_id']; ?>, '<?php echo htmlspecialchars($course['course_name']); ?>')">Assign Lecturer</button>
                                            <button class="btn-action btn-view" onclick="viewEnrolledStudents(<?php echo $course['course_id']; ?>)">View Students</button>
                                            <button class="btn-action btn-toggle" onclick="toggleEnrollment(<?php echo $course['course_id']; ?>, '<?php echo $course['status']; ?>')">
                                                <?php echo $course['status'] === 'open' ? 'Close Enrollment' : 'Open Enrollment'; ?>
                                            </button>
                                            <button class="btn-action btn-delete" onclick="deleteCourse(<?php echo $course['course_id']; ?>, '<?php echo htmlspecialchars($course['course_name']); ?>')">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Course Modal -->
    <div id="courseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Create Course</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="courseForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="course_id" id="courseId">
                <div class="form-group">
                    <label class="form-label">Course Code *</label>
                    <input type="text" name="course_code" id="course_code" class="form-control" required maxlength="20">
                </div>
                <div class="form-group">
                    <label class="form-label">Course Name *</label>
                    <input type="text" name="course_name" id="course_name" class="form-control" required maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Assign Lecturer</label>
                    <select name="lecturer_id" id="lecturer_id" class="form-control">
                        <option value="">Select Lecturer (Optional)</option>
                        <?php foreach ($lecturers as $lecturer): ?>
                            <option value="<?php echo $lecturer['user_id']; ?>">
                                <?php echo htmlspecialchars($lecturer['name'] . ' (' . $lecturer['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="open" selected>Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn-action btn-toggle" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-action btn-edit">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Lecturer Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Assign Lecturer</h2>
                <button class="close-btn" onclick="closeAssignModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="assign_lecturer">
                <input type="hidden" name="course_id" id="assignCourseId">
                <div class="form-group">
                    <label class="form-label">Course Name</label>
                    <input type="text" id="assignCourseName" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Select Lecturer *</label>
                    <select name="lecturer_id" class="form-control" required>
                        <option value="">Select Lecturer</option>
                        <?php foreach ($lecturers as $lecturer): ?>
                            <option value="<?php echo $lecturer['user_id']; ?>">
                                <?php echo htmlspecialchars($lecturer['name'] . ' (' . $lecturer['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn-action btn-toggle" onclick="closeAssignModal()">Cancel</button>
                    <button type="submit" class="btn-action btn-assign">Assign</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Enrolled Students Modal -->
    <div id="studentsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Enrolled Students</h2>
                <button class="close-btn" onclick="closeStudentsModal()">&times;</button>
            </div>
            <div id="studentsList">
                <p style="text-align: center; padding: 20px; color: #6b7280;">Loading...</p>
            </div>
        </div>
    </div>

    <script>
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

        function openCreateModal() {
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Create Course';
            document.getElementById('courseId').value = '';
            document.getElementById('courseForm').reset();
            document.getElementById('courseModal').classList.add('active');
        }

        function openEditModal(course) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Edit Course';
            document.getElementById('courseId').value = course.course_id;
            document.getElementById('course_code').value = course.course_code;
            document.getElementById('course_name').value = course.course_name;
            document.getElementById('description').value = course.description || '';
            document.getElementById('status').value = course.status;
            document.getElementById('courseModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('courseModal').classList.remove('active');
        }

        function openAssignModal(courseId, courseName) {
            document.getElementById('assignCourseId').value = courseId;
            document.getElementById('assignCourseName').value = courseName;
            document.getElementById('assignModal').classList.add('active');
        }

        function closeAssignModal() {
            document.getElementById('assignModal').classList.remove('active');
        }

        function viewEnrolledStudents(courseId) {
            document.getElementById('studentsModal').classList.add('active');
            document.getElementById('studentsList').innerHTML = '<p style="text-align: center; padding: 20px; color: #6b7280;">Loading...</p>';
            
            // Fetch enrolled students via AJAX
            fetch(`view-students.php?course_id=${courseId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('studentsList').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('studentsList').innerHTML = '<p style="text-align: center; padding: 20px; color: #ef4444;">Error loading students</p>';
                });
        }

        function closeStudentsModal() {
            document.getElementById('studentsModal').classList.remove('active');
        }

        function toggleEnrollment(courseId, currentStatus) {
            const newStatus = currentStatus === 'open' ? 'closed' : 'open';
            if (confirm(`Are you sure you want to ${newStatus === 'open' ? 'open' : 'close'} enrollment for this course?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_enrollment">
                    <input type="hidden" name="course_id" value="${courseId}">
                    <input type="hidden" name="new_status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteCourse(courseId, courseName) {
            if (confirm(`Are you sure you want to delete course "${courseName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="course_id" value="${courseId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
<?php
mysqli_close($conn);
?>

