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

// System-wide statistics
$stats = [];

// Total progress records
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM progress");
$stats['total_progress'] = mysqli_fetch_assoc($result)['count'];

// Average completion percentage
$result = mysqli_query($conn, "SELECT AVG(completed_percentage) as avg FROM progress");
$avg_completion = mysqli_fetch_assoc($result);
$stats['avg_completion'] = round($avg_completion['avg'] ?? 0, 2);

// Total quiz attempts
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM quiz_attempts");
$stats['total_quiz_attempts'] = mysqli_fetch_assoc($result)['count'];

// Total submissions
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM submissions");
$stats['total_submissions'] = mysqli_fetch_assoc($result)['count'];

// Lecturer workload
$lecturer_workload_query = "
    SELECT 
        u.user_id, u.name, u.email,
        COUNT(DISTINCT cl.course_id) as courses_count,
        COUNT(DISTINCT a.assignment_id) as assignments_count,
        COUNT(DISTINCT m.material_id) as materials_count
    FROM users u
    LEFT JOIN course_lecturers cl ON u.user_id = cl.lecturer_id
    LEFT JOIN assignments a ON u.user_id = a.lecturer_id
    LEFT JOIN materials m ON u.user_id = m.lecturer_id
    WHERE u.role = 'lecturer' AND u.status = 'active'
    GROUP BY u.user_id
    ORDER BY courses_count DESC, assignments_count DESC
";
$lecturer_workload_result = mysqli_query($conn, $lecturer_workload_query);
$lecturer_workload = mysqli_fetch_all($lecturer_workload_result, MYSQLI_ASSOC);

// Course progress summary
$course_progress_query = "
    SELECT 
        c.course_id, c.course_code, c.course_name,
        COUNT(DISTINCT e.student_id) as enrolled_students,
        COUNT(DISTINCT p.student_id) as students_with_progress,
        AVG(p.completed_percentage) as avg_completion
    FROM courses c
    LEFT JOIN enrollments e ON c.course_id = e.course_id
    LEFT JOIN progress p ON c.course_id = p.course_id
    GROUP BY c.course_id
    ORDER BY enrolled_students DESC
";
$course_progress_result = mysqli_query($conn, $course_progress_query);
$course_progress = mysqli_fetch_all($course_progress_result, MYSQLI_ASSOC);

// Student progress summary
$student_progress_query = "
    SELECT 
        u.user_id, u.name, u.email,
        COUNT(DISTINCT e.course_id) as enrolled_courses,
        COUNT(DISTINCT p.course_id) as courses_with_progress,
        AVG(p.completed_percentage) as avg_completion,
        COUNT(DISTINCT s.assignment_id) as assignments_submitted,
        COUNT(DISTINCT qa.quiz_id) as quizzes_attempted
    FROM users u
    LEFT JOIN enrollments e ON u.user_id = e.student_id
    LEFT JOIN progress p ON u.user_id = p.student_id
    LEFT JOIN submissions s ON u.user_id = s.student_id
    LEFT JOIN quiz_attempts qa ON u.user_id = qa.student_id
    WHERE u.role = 'student' AND u.status = 'active'
    GROUP BY u.user_id
    ORDER BY avg_completion DESC
    LIMIT 20
";
$student_progress_result = mysqli_query($conn, $student_progress_query);
$student_progress = mysqli_fetch_all($student_progress_result, MYSQLI_ASSOC);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress & Reports - Admin - Ez2Learn</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e3a5f;
            margin-bottom: 20px;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-bottom: 30px;
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

        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3198F8 0%, #1e6bb8 100%);
            transition: width 0.3s ease;
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

            .stats-grid {
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
                    <li><a href="../module-usermanagement/index.php">Users</a></li>
                    <li><a href="../module-managelearning/index.php">Courses</a></li>
                    <li><a href="../module-assignments/index.php">Assignments</a></li>
                    <li><a href="index.php" class="active">Progress</a></li>
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
                <h1 class="page-title">System Progress & Reports</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_progress']; ?></div>
                    <div class="stat-label">Progress Records</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['avg_completion']; ?>%</div>
                    <div class="stat-label">Avg Completion</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_quiz_attempts']; ?></div>
                    <div class="stat-label">Quiz Attempts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_submissions']; ?></div>
                    <div class="stat-label">Submissions</div>
                </div>
            </div>
        </div>

        <div class="page-container">
            <div class="content">
                <h2 class="section-title">Lecturer Workload</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Lecturer Name</th>
                                <th>Email</th>
                                <th>Courses</th>
                                <th>Assignments</th>
                                <th>Materials</th>
                                <th>Total Workload</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lecturer_workload)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">No lecturers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lecturer_workload as $lecturer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($lecturer['name']); ?></td>
                                        <td><?php echo htmlspecialchars($lecturer['email']); ?></td>
                                        <td><?php echo $lecturer['courses_count']; ?></td>
                                        <td><?php echo $lecturer['assignments_count']; ?></td>
                                        <td><?php echo $lecturer['materials_count']; ?></td>
                                        <td><strong><?php echo $lecturer['courses_count'] + $lecturer['assignments_count'] + $lecturer['materials_count']; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="page-container">
            <div class="content">
                <h2 class="section-title">Course Progress Summary</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Enrolled Students</th>
                                <th>Students with Progress</th>
                                <th>Avg Completion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($course_progress)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #6b7280;">No courses found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($course_progress as $course): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                        <td><?php echo $course['enrolled_students']; ?></td>
                                        <td><?php echo $course['students_with_progress']; ?></td>
                                        <td>
                                            <?php 
                                            $avg = round($course['avg_completion'] ?? 0, 1);
                                            echo $avg . '%';
                                            ?>
                                            <div class="progress-bar" style="margin-top: 5px;">
                                                <div class="progress-fill" style="width: <?php echo min($avg, 100); ?>%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="page-container">
            <div class="content">
                <h2 class="section-title">Top Student Progress</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Enrolled Courses</th>
                                <th>Avg Completion</th>
                                <th>Assignments Submitted</th>
                                <th>Quizzes Attempted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($student_progress)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">No students found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($student_progress as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo $student['enrolled_courses']; ?></td>
                                        <td>
                                            <?php 
                                            $avg = round($student['avg_completion'] ?? 0, 1);
                                            echo $avg . '%';
                                            ?>
                                            <div class="progress-bar" style="margin-top: 5px;">
                                                <div class="progress-fill" style="width: <?php echo min($avg, 100); ?>%;"></div>
                                            </div>
                                        </td>
                                        <td><?php echo $student['assignments_submitted']; ?></td>
                                        <td><?php echo $student['quizzes_attempted']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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

