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
$error = '';
$success = '';

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $course_id = (int)($_POST['course_id'] ?? 0);
    
    if ($course_id > 0) {
        // Check if course is open
        $check_course = mysqli_prepare($conn, "SELECT course_id, status FROM courses WHERE course_id = ?");
        mysqli_stmt_bind_param($check_course, "i", $course_id);
        mysqli_stmt_execute($check_course);
        $course_result = mysqli_stmt_get_result($check_course);
        $course = mysqli_fetch_assoc($course_result);
        mysqli_stmt_close($check_course);
        
        if ($course && $course['status'] === 'open') {
            // Check if already enrolled
            $check_enroll = mysqli_prepare($conn, "SELECT enrollment_id FROM enrollments WHERE course_id = ? AND student_id = ?");
            mysqli_stmt_bind_param($check_enroll, "ii", $course_id, $student_id);
            mysqli_stmt_execute($check_enroll);
            $enroll_result = mysqli_stmt_get_result($check_enroll);
            
            if (mysqli_num_rows($enroll_result) > 0) {
                $error = 'You are already enrolled in this course.';
            } else {
                $enroll_stmt = mysqli_prepare($conn, "INSERT INTO enrollments (course_id, student_id) VALUES (?, ?)");
                mysqli_stmt_bind_param($enroll_stmt, "ii", $course_id, $student_id);
                
                if (mysqli_stmt_execute($enroll_stmt)) {
                    $success = 'Successfully enrolled in the course!';
                } else {
                    $error = 'Failed to enroll. Please try again.';
                }
                mysqli_stmt_close($enroll_stmt);
            }
            mysqli_stmt_close($check_enroll);
        } else {
            $error = 'Course is not available for enrollment.';
        }
    }
}

// Get enrolled courses
$enrolled_query = "
    SELECT 
        c.course_id, c.course_code, c.course_name, c.description, c.status,
        GROUP_CONCAT(DISTINCT u.name SEPARATOR ', ') as lecturers,
        COUNT(DISTINCT m.material_id) as materials_count
    FROM enrollments e
    INNER JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN course_lecturers cl ON c.course_id = cl.course_id
    LEFT JOIN users u ON cl.lecturer_id = u.user_id
    LEFT JOIN materials m ON c.course_id = m.course_id
    WHERE e.student_id = ?
    GROUP BY c.course_id
    ORDER BY e.enrolled_at DESC
";
$stmt = mysqli_prepare($conn, $enrolled_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$enrolled_courses = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Get available courses (open courses not yet enrolled)
$available_query = "
    SELECT 
        c.course_id, c.course_code, c.course_name, c.description,
        GROUP_CONCAT(DISTINCT u.name SEPARATOR ', ') as lecturers
    FROM courses c
    LEFT JOIN course_lecturers cl ON c.course_id = cl.course_id
    LEFT JOIN users u ON cl.lecturer_id = u.user_id
    WHERE c.status = 'open'
    AND c.course_id NOT IN (SELECT course_id FROM enrollments WHERE student_id = ?)
    GROUP BY c.course_id
    ORDER BY c.course_code ASC
";
$stmt = mysqli_prepare($conn, $available_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$available_courses = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Student - Ez2Learn</title>
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
            margin-bottom: 30px;
        }

        .page-header {
            padding: 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e3a5f;
        }

        .section-title {
            padding: 20px 30px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .content {
            padding: 30px;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .course-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #3198F8;
        }

        .course-header {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            padding: 20px;
        }

        .course-code {
            font-size: 14px;
            font-weight: 600;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .course-title {
            font-size: 18px;
            font-weight: 600;
        }

        .course-body {
            padding: 20px;
        }

        .course-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .course-meta {
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
        }

        .course-actions {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-access {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
        }

        .btn-access:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(49, 152, 248, 0.4);
        }

        .btn-enroll {
            background: #10b981;
            color: white;
        }

        .btn-enroll:hover {
            background: #059669;
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

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
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

            .courses-grid {
                grid-template-columns: 1fr;
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
                    <li><a href="index.php" class="active">My Courses</a></li>
                    <li><a href="../module-assignments/index.php">Assignments</a></li>
                    <li><a href="../module-progress/index.php">Progress</a></li>
                    <li><a href="../module-usermanagement/index.php">Profile</a></li>
                </ul>
                <div class="profile-dropdown" id="profileDropdown">
                    <button class="profile-btn" onclick="toggleDropdown()">
                        <div class="profile-icon"><?php echo strtoupper(substr($_SESSION['name'] ?? 'S', 0, 1)); ?></div>
                        <span><?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?></span>
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
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Enrolled Courses -->
        <div class="page-container">
            <div class="section-title">My Enrolled Courses</div>
            <div class="content">
                <?php if (empty($enrolled_courses)): ?>
                    <div class="empty-state">
                        <p>You are not enrolled in any courses yet.</p>
                        <p style="margin-top: 10px; font-size: 14px;">Browse available courses below to enroll.</p>
                    </div>
                <?php else: ?>
                    <div class="courses-grid">
                        <?php foreach ($enrolled_courses as $course): ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                                    <div class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                </div>
                                <div class="course-body">
                                    <div class="course-description">
                                        <?php echo htmlspecialchars(substr($course['description'] ?? 'No description', 0, 100)); ?>
                                        <?php echo strlen($course['description'] ?? '') > 100 ? '...' : ''; ?>
                                    </div>
                                    <div class="course-meta">
                                        <strong>Lecturer:</strong> <?php echo htmlspecialchars($course['lecturers'] ?: 'Not assigned'); ?><br>
                                        <strong>Materials:</strong> <?php echo $course['materials_count']; ?>
                                    </div>
                                    <div class="course-actions">
                                        <a href="view-course.php?course_id=<?php echo $course['course_id']; ?>" class="btn-action btn-access">Access Course</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Available Courses -->
        <div class="page-container">
            <div class="section-title">Available Courses</div>
            <div class="content">
                <?php if (empty($available_courses)): ?>
                    <div class="empty-state">
                        <p>No available courses to enroll in at the moment.</p>
                    </div>
                <?php else: ?>
                    <div class="courses-grid">
                        <?php foreach ($available_courses as $course): ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                                    <div class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                </div>
                                <div class="course-body">
                                    <div class="course-description">
                                        <?php echo htmlspecialchars(substr($course['description'] ?? 'No description', 0, 100)); ?>
                                        <?php echo strlen($course['description'] ?? '') > 100 ? '...' : ''; ?>
                                    </div>
                                    <div class="course-meta">
                                        <strong>Lecturer:</strong> <?php echo htmlspecialchars($course['lecturers'] ?: 'Not assigned'); ?>
                                    </div>
                                    <div class="course-actions">
                                        <form method="POST" style="width: 100%;">
                                            <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                            <button type="submit" name="enroll" class="btn-action btn-enroll" style="width: 100%;">Enroll Now</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
    </script>
</body>
</html>

