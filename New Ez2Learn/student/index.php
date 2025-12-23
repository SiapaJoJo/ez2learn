<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db-config.php';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$student_id = $_SESSION['user_id'] ?? 0;

$stats = [];

$result = mysqli_query($conn, "
    SELECT COUNT(DISTINCT e.course_id) as count 
    FROM enrollments e
    WHERE e.student_id = $student_id
");
$stats['enrolled_courses'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "
    SELECT COUNT(DISTINCT a.assignment_id) as count
    FROM assignments a
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = $student_id
    INNER JOIN enrollments e ON a.course_id = e.course_id
    WHERE e.student_id = $student_id 
    AND s.submission_id IS NULL
    AND (a.due_date IS NULL OR a.due_date >= CURDATE())
");
$stats['pending_assignments'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "
    SELECT COUNT(DISTINCT p.course_id) as count
    FROM progress p
    WHERE p.student_id = $student_id AND p.completed_percentage >= 100
");
$stats['completed_courses'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "
    SELECT COUNT(*) as count 
    FROM submissions 
    WHERE student_id = $student_id
");
$stats['submitted_assignments'] = mysqli_fetch_assoc($result)['count'];

$user_name = $_SESSION['name'] ?? 'Student';
$user_email = $_SESSION['email'] ?? '';

mysqli_close($conn);

$page_title = 'Dashboard';
require_once '../includes/header-student.php';
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
            <h1>Welcome to Student Dashboard!</h1>
            <p>You are logged in as <span class="username"><?php echo htmlspecialchars($user_name); ?></span> (Student)</p>
        </div>

        <div class="dashboard-grid">
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['enrolled_courses']; ?></div>
                <div class="stat-label">Enrolled Courses</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['pending_assignments']; ?></div>
                <div class="stat-label">Pending Assignments</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['completed_courses']; ?></div>
                <div class="stat-label">Completed Courses</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['submitted_assignments']; ?></div>
                <div class="stat-label">Assignments Submitted</div>
            </div>
        </div>

        <div class="card">
            <h3>Quick Actions</h3>
            <div class="quick-actions">
                <a href="module-managelearning/index.php" class="action-card">
                    <h4>My Courses</h4>
                    <p>View and access your enrolled courses</p>
                </a>
                <a href="module-assignments/index.php" class="action-card">
                    <h4>Assignments</h4>
                    <p>View and submit assignments</p>
                </a>
                <a href="module-progress/index.php" class="action-card">
                    <h4>My Progress</h4>
                    <p>Track your learning progress</p>
                </a>
                <a href="module-usermanagement/index.php" class="action-card">
                    <h4>Profile</h4>
                    <p>View and edit your profile</p>
                </a>
            </div>
        </div>
    </div>

<?php require_once '../includes/footer.php'; ?>

