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

$stats = [];

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM progress");
$stats['total_progress'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "SELECT AVG(completed_percentage) as avg FROM progress");
$avg_completion = mysqli_fetch_assoc($result);
$stats['avg_completion'] = round($avg_completion['avg'] ?? 0, 2);

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM quiz_attempts");
$stats['total_quiz_attempts'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM submissions");
$stats['total_submissions'] = mysqli_fetch_assoc($result)['count'];

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

$page_title = 'Progress & Reports';
require_once '../../includes/header-admin.php';
?>
<style>

        .page-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            margin-bottom: 2rem;
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
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.4);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .content {
            padding: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1.25rem;
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

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

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

<?php require_once '../../includes/footer.php'; ?>

