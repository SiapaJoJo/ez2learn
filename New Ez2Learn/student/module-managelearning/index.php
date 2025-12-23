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
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $course_id = (int)($_POST['course_id'] ?? 0);
    
    if ($course_id > 0) {

        $check_course = mysqli_prepare($conn, "SELECT course_id, status FROM courses WHERE course_id = ?");
        mysqli_stmt_bind_param($check_course, "i", $course_id);
        mysqli_stmt_execute($check_course);
        $course_result = mysqli_stmt_get_result($check_course);
        $course = mysqli_fetch_assoc($course_result);
        mysqli_stmt_close($check_course);
        
        if ($course && $course['status'] === 'open') {

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

// MODULE 4: Ensure progress records exist for all enrollments
require_once '../../includes/progress_service.php';
foreach ($enrolled_courses as $course) {
    ensure_progress_record($conn, $student_id, $course['course_id']);
}

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

$page_title = 'My Courses';
require_once '../../includes/header-student.php';
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

        .section-title {
            padding: 1.25rem 2rem;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
        }

        .content {
            padding: 2rem;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .course-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-color: #667eea;
        }

        .course-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-access:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
        }

        .btn-enroll {
            background: #10b981;
            color: white;
        }

        .btn-enroll:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin: 1.25rem 2rem;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            animation: slideUp 0.3s;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #ecfdf5;
            border-color: #10b981;
            color: #065f46;
        }

        .alert-danger {
            background: #fef2f2;
            border-color: #ef4444;
            color: #991b1b;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .courses-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

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
<?php require_once '../../includes/footer.php'; ?>

