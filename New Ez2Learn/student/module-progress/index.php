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
require_once '../../includes/progress_service.php';

$student_id = $_SESSION['user_id'] ?? 0;

// Get all courses with progress
$courses = get_student_all_courses_progress($conn, $student_id);

// Calculate overall stats
$total_courses = count($courses);
$completed_courses = count(array_filter($courses, function($c) { return $c['completed_percentage'] >= 100; }));
$avg_progress = $total_courses > 0 ? round(array_sum(array_column($courses, 'completed_percentage')) / $total_courses) : 0;

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
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        padding: 30px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-number {
        font-size: 36px;
        font-weight: bold;
        color: #3198F8;
        margin-bottom: 10px;
    }

    .stat-label {
        font-size: 14px;
        color: #666;
    }

    .course-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .progress-bar-wrapper {
        height: 20px;
        background: #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
        margin: 10px 0;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #059669);
        transition: width 0.5s ease;
    }

    .certificate-badge {
        background: #fbbf24;
        color: #78350f;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .btn-details {
        color: #3198F8;
        text-decoration: none;
        font-weight: 500;
    }

    .btn-details:hover {
        text-decoration: underline;
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
                <div class="stat-number"><?php echo round($avg_progress); ?>%</div>
                <div class="stat-label">Average Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $completed_courses; ?></div>
                <div class="stat-label">Completed Courses</div>
            </div>
        </div>

        <div style="padding: 30px;">
            <h2 style="font-size: 20px; font-weight: 600; margin-bottom: 20px;">Course Progress</h2>
            
            <?php if (empty($courses)): ?>
                <p style="text-align: center; color: #666; padding: 40px;">No enrolled courses found.</p>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                            <div>
                                <h3><?php echo htmlspecialchars($course['course_code']); ?> - <?php echo htmlspecialchars($course['course_name']); ?></h3>
                                <p style="color: #666; font-size: 14px; margin-top: 5px;">
                                    Last updated: <?php echo $course['last_updated'] ? date('M d, Y', strtotime($course['last_updated'])) : 'Not started'; ?>
                                </p>
                            </div>
                            <?php if ($course['has_certificate']): ?>
                                <span class="certificate-badge">🏆 Certificate Earned</span>
                            <?php endif; ?>
                        </div>
                        <div class="progress-bar-wrapper">
                            <div class="progress-fill" style="width: <?php echo $course['completed_percentage']; ?>%;"></div>
                        </div>
                        <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 600; color: #3198F8;"><?php echo $course['completed_percentage']; ?>% Complete</span>
                            <a href="course-details.php?course_id=<?php echo $course['course_id']; ?>" class="btn-details">View Details →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>

