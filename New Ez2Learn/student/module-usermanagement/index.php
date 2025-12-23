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

$user_query = "SELECT user_id, name, email, role, status, created_at FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$enrollment_stats = [];
$result = mysqli_query($conn, "
    SELECT COUNT(DISTINCT e.course_id) as enrolled_courses
    FROM enrollments e
    WHERE e.student_id = $student_id
");
$row = mysqli_fetch_assoc($result);
$enrollment_stats['enrolled_courses'] = $row['enrolled_courses'] ?? 0;

$result = mysqli_query($conn, "
    SELECT COUNT(DISTINCT s.assignment_id) as submitted_assignments
    FROM submissions s
    WHERE s.student_id = $student_id
");
$row = mysqli_fetch_assoc($result);
$enrollment_stats['submitted_assignments'] = $row['submitted_assignments'] ?? 0;

$result = mysqli_query($conn, "
    SELECT COUNT(DISTINCT qa.quiz_id) as attempted_quizzes
    FROM quiz_attempts qa
    WHERE qa.student_id = $student_id
");
$row = mysqli_fetch_assoc($result);
$enrollment_stats['attempted_quizzes'] = $row['attempted_quizzes'] ?? 0;

mysqli_close($conn);

$page_title = 'My Profile';
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
            padding: 2rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            color: white;
            margin: 0 auto 1.25rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 0.875rem;
        }

        .content {
            padding: 2rem;
        }

        .info-section {
            margin-bottom: 2rem;
        }

        .info-section h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
            min-width: 150px;
            font-size: 0.875rem;
        }

        .info-value {
            color: #1e293b;
            font-size: 0.875rem;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
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

        .btn-edit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
        }

        .btn-edit:hover {
            background: #1e6bb8;
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
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <h1 class="page-title"><?php echo htmlspecialchars($user['name']); ?></h1>
                <p class="page-subtitle">Student Profile</p>
            </div>

            <div class="content">
                <div class="info-section">
                    <h3>Personal Information</h3>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Role:</span>
                        <span class="info-value">
                            <span class="badge badge-active"><?php echo ucfirst($user['role']); ?></span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="badge badge-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Member Since:</span>
                        <span class="info-value"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>

                <div class="info-section">
                    <h3>Academic Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $enrollment_stats['enrolled_courses']; ?></div>
                            <div class="stat-label">Enrolled Courses</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $enrollment_stats['submitted_assignments']; ?></div>
                            <div class="stat-label">Assignments Submitted</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $enrollment_stats['attempted_quizzes']; ?></div>
                            <div class="stat-label">Quizzes Attempted</div>
                        </div>
                    </div>
                </div>

                <div style="text-align: center;">
                    <a href="../../edit-profile.php" class="btn-edit">Edit Profile</a>
                </div>
            </div>
        </div>
    </div>

<?php require_once '../../includes/footer.php'; ?>

