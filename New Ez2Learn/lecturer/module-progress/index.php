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

$progress_query = "
    SELECT 
        c.course_id, c.course_code, c.course_name,
        COUNT(DISTINCT e.student_id) as enrolled_students,
        COUNT(DISTINCT p.student_id) as students_with_progress,
        AVG(p.completed_percentage) as avg_completion
    FROM courses c
    INNER JOIN course_lecturers cl ON c.course_id = cl.course_id
    LEFT JOIN enrollments e ON c.course_id = e.course_id
    LEFT JOIN progress p ON c.course_id = p.course_id
    WHERE cl.lecturer_id = ?
    GROUP BY c.course_id
    ORDER BY c.course_code ASC
";
$stmt = mysqli_prepare($conn, $progress_query);
mysqli_stmt_bind_param($stmt, "i", $lecturer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$course_progress = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

mysqli_close($conn);

$page_title = 'Student Progress';
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

    </style>

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <h1 class="page-title">Student Progress</h1>
            </div>

            <div class="content">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Enrolled Students</th>
                                <th>Students with Progress</th>
                                <th>Average Completion</th>
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
                                            <div class="progress-bar">
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
    </div>

<?php require_once '../../includes/footer.php'; ?>

