<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'lecturer') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db-config.php';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$lecturer_id = $_SESSION['user_id'] ?? 0;

$stats = [];

$result = mysqli_query($conn, "
    SELECT COUNT(DISTINCT c.course_id) as count 
    FROM courses c
    INNER JOIN course_lecturers cl ON c.course_id = cl.course_id
    WHERE cl.lecturer_id = $lecturer_id
");
$stats['my_courses'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "
    SELECT COUNT(DISTINCT e.student_id) as count
    FROM enrollments e
    INNER JOIN course_lecturers cl ON e.course_id = cl.course_id
    WHERE cl.lecturer_id = $lecturer_id
");
$stats['total_students'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "
    SELECT COUNT(DISTINCT s.submission_id) as count
    FROM submissions s
    INNER JOIN assignments a ON s.assignment_id = a.assignment_id
    WHERE a.lecturer_id = $lecturer_id AND s.marks IS NULL
");
$stats['pending_assignments'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "
    SELECT COUNT(*) as count 
    FROM assignments 
    WHERE lecturer_id = $lecturer_id
");
$stats['total_assignments'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "
    SELECT COUNT(*) as count 
    FROM materials 
    WHERE lecturer_id = $lecturer_id
");
$stats['total_materials'] = mysqli_fetch_assoc($result)['count'];

$user_name = $_SESSION['name'] ?? 'Lecturer';
$user_email = $_SESSION['email'] ?? '';

mysqli_close($conn);

$page_title = 'Dashboard';
require_once '../includes/header-lecturer.php';
?>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .card h3 {
            color: #1e293b;
            margin-bottom: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .card p {
            color: #64748b;
            line-height: 1.6;
            font-size: 0.875rem;
        }

        .stat-card {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.3), 0 4px 6px -2px rgba(102, 126, 234, 0.2);
            margin-bottom: 2rem;
            color: white;
        }

        .welcome-section h1 {
            color: white;
            margin-bottom: 0.5rem;
            font-size: 2rem;
            font-weight: 700;
        }

        .welcome-section p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
        }

        .username {
            color: white;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            display: inline-block;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .action-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: block;
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.4);
        }

        .action-card h4 {
            margin-bottom: 0.5rem;
            font-size: 1.125rem;
            font-weight: 600;
        }

        .action-card p {
            font-size: 0.875rem;
            opacity: 0.9;
            color: rgba(255, 255, 255, 0.9);
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .welcome-section {
                padding: 1.5rem;
            }

            .welcome-section h1 {
                font-size: 1.5rem;
            }
        }
    </style>

    <div class="container">
        <div class="welcome-section">
            <h1>Welcome to Lecturer Dashboard!</h1>
            <p>You are logged in as <span class="username"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Lecturer'); ?></span> (Lecturer)</p>
        </div>

        <div class="dashboard-grid">
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['my_courses']; ?></div>
                <div class="stat-label">My Courses</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['pending_assignments']; ?></div>
                <div class="stat-label">Pending Assignments</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['total_assignments']; ?></div>
                <div class="stat-label">Total Assignments</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['total_materials']; ?></div>
                <div class="stat-label">Learning Materials</div>
            </div>
        </div>

        <div class="card">
            <h3>Quick Actions</h3>
            <div class="quick-actions">
                <a href="module-managelearning/index.php" class="action-card">
                    <h4>Manage Materials</h4>
                    <p>Upload and manage learning materials</p>
                </a>
                <a href="module-assignments/index.php" class="action-card">
                    <h4>Create Assignment</h4>
                    <p>Create new assignments and quizzes</p>
                </a>
                <a href="module-assignments/index.php?filter=pending" class="action-card">
                    <h4>Grade Assignments</h4>
                    <p>Mark and provide feedback</p>
                </a>
                <a href="module-assignments/create-quiz.php" class="action-card">
                    <h4>Create Quiz</h4>
                    <p>Create quizzes with questions</p>
                </a>
                <a href="module-progress/index.php" class="action-card">
                    <h4>View Progress</h4>
                    <p>Monitor student progress</p>
                </a>
            </div>
        </div>
    </div>

<?php require_once '../includes/footer.php'; ?>

