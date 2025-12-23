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

$progress_query = "
    SELECT 
        c.course_id, c.course_code, c.course_name,
        p.completed_percentage,
        COUNT(DISTINCT m.material_id) as total_materials,
        COUNT(DISTINCT a.assignment_id) as total_assignments,
        COUNT(DISTINCT s.assignment_id) as submitted_assignments,
        COUNT(DISTINCT q.quiz_id) as total_quizzes,
        COUNT(DISTINCT qa.quiz_id) as attempted_quizzes
    FROM enrollments e
    INNER JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN progress p ON c.course_id = p.course_id AND p.student_id = ?
    LEFT JOIN materials m ON c.course_id = m.course_id
    LEFT JOIN assignments a ON c.course_id = a.course_id
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = ?
    LEFT JOIN quizzes q ON c.course_id = q.course_id
    LEFT JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id AND qa.student_id = ?
    WHERE e.student_id = ?
    GROUP BY c.course_id, p.completed_percentage
    ORDER BY c.course_code ASC
";
$stmt = mysqli_prepare($conn, $progress_query);
mysqli_stmt_bind_param($stmt, "iiii", $student_id, $student_id, $student_id, $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$course_progress = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$total_courses = count($course_progress);
$completed_courses = 0;
$total_assignments = 0;
$submitted_assignments = 0;
$total_quizzes = 0;
$attempted_quizzes = 0;

foreach ($course_progress as $course) {
    if (($course['completed_percentage'] ?? 0) >= 100) {
        $completed_courses++;
    }
    $total_assignments += $course['total_assignments'];
    $submitted_assignments += $course['submitted_assignments'];
    $total_quizzes += $course['total_quizzes'];
    $attempted_quizzes += $course['attempted_quizzes'];
}

$overall_completion = $total_courses > 0 ? round(($completed_courses / $total_courses) * 100, 1) : 0;

mysqli_close($conn);

$page_title = 'My Progress';
require_once '../../includes/header-student.php';
?>
<style>

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
                <h1 class="page-title">My Learning Progress</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_courses; ?></div>
                    <div class="stat-label">Enrolled Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $completed_courses; ?></div>
                    <div class="stat-label">Completed Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $overall_completion; ?>%</div>
                    <div class="stat-label">Overall Completion</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $submitted_assignments; ?> / <?php echo $total_assignments; ?></div>
                    <div class="stat-label">Assignments Submitted</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $attempted_quizzes; ?> / <?php echo $total_quizzes; ?></div>
                    <div class="stat-label">Quizzes Attempted</div>
                </div>
            </div>
        </div>

        <div class="page-container">
            <div class="content">
                <h2 style="font-size: 20px; font-weight: 600; color: #333; margin-bottom: 20px;">Course Progress</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Materials</th>
                                <th>Assignments</th>
                                <th>Quizzes</th>
                                <th>Completion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($course_progress)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">No enrolled courses</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($course_progress as $course): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                        <td><?php echo $course['total_materials']; ?></td>
                                        <td><?php echo $course['submitted_assignments']; ?> / <?php echo $course['total_assignments']; ?></td>
                                        <td><?php echo $course['attempted_quizzes']; ?> / <?php echo $course['total_quizzes']; ?></td>
                                        <td>
                                            <?php 
                                            $completion = round($course['completed_percentage'] ?? 0, 1);
                                            echo $completion . '%';
                                            ?>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo min($completion, 100); ?>%;"></div>
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
    </div>

<?php require_once '../../includes/footer.php'; ?>

