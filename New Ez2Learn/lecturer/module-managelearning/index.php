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

$courses_query = "
    SELECT 
        c.course_id, c.course_code, c.course_name, c.description, c.status,
        COUNT(DISTINCT m.material_id) as materials_count,
        COUNT(DISTINCT e.student_id) as enrolled_students
    FROM courses c
    INNER JOIN course_lecturers cl ON c.course_id = cl.course_id
    LEFT JOIN materials m ON c.course_id = m.course_id AND m.lecturer_id = ?
    LEFT JOIN enrollments e ON c.course_id = e.course_id
    WHERE cl.lecturer_id = ?
    GROUP BY c.course_id
    ORDER BY c.course_code ASC
";
$stmt = mysqli_prepare($conn, $courses_query);
mysqli_stmt_bind_param($stmt, "ii", $lecturer_id, $lecturer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$courses = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

mysqli_close($conn);

$page_title = 'Manage Learning Materials';
require_once '../../includes/header-lecturer.php';
?>
    <style>
        .page-header {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .page-header h1 {
            color: #1e293b;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #64748b;
            font-size: 1rem;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .course-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .course-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }

        .course-code {
            font-size: 0.875rem;
            font-weight: 600;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .course-title {
            font-size: 1.125rem;
            font-weight: 600;
        }

        .course-body {
            padding: 1.5rem;
        }

        .course-stats {
            display: flex;
            gap: 1.25rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .stat-item {
            flex: 1;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #64748b;
        }

        .course-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-action {
            flex: 1;
            min-width: 120px;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-manage {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-manage:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
        }

        .btn-view {
            background: #f8fafc;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }

        .btn-view:hover {
            background: white;
            border-color: #667eea;
            color: #667eea;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #666;
        }

        @media (max-width: 768px) {
            .courses-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="container">
        <div class="page-header">
            <h1>Manage Learning Materials</h1>
            <p>Upload, edit, and manage learning materials for your assigned courses</p>
        </div>

        <?php if (empty($courses)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📚</div>
                <h3>No Courses Found</h3>
                <p>You are not assigned as a lecturer for any courses.</p>
                <p style="margin-top: 10px; color: #999; font-size: 14px;">Contact the administrator to be assigned to courses.</p>
            </div>
        <?php else: ?>
            <div class="courses-grid">
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <div class="course-header">
                            <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                            <div class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></div>
                        </div>
                        <div class="course-body">
                            <div class="course-stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $course['materials_count']; ?></div>
                                    <div class="stat-label">Materials</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $course['enrolled_students']; ?></div>
                                    <div class="stat-label">Students</div>
                                </div>
                            </div>
                            <div class="course-actions">
                                <a href="manage-materials.php?course_id=<?php echo $course['course_id']; ?>" class="btn-action btn-manage">📤 Upload/Edit Materials</a>
                                <a href="view-students.php?course_id=<?php echo $course['course_id']; ?>" class="btn-action btn-view">👥 View Students</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php require_once '../../includes/footer.php'; ?>

