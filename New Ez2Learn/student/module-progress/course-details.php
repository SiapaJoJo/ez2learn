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
$course_id = (int)($_GET['course_id'] ?? 0);

// Verify enrollment
$stmt = mysqli_prepare($conn, "SELECT c.* FROM courses c INNER JOIN enrollments e ON c.course_id = e.course_id WHERE c.course_id = ? AND e.student_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $course_id, $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$course = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$course) {
    header('Location: index.php');
    exit();
}

// Get detailed breakdown
$breakdown = get_student_course_progress_breakdown($conn, $student_id, $course_id);
$progress_data = recalc_and_persist_course_progress($conn, $student_id, $course_id);

// Check for certificate
$cert_stmt = mysqli_prepare($conn, "SELECT certificate_code, issued_at FROM certificates WHERE student_id = ? AND course_id = ?");
mysqli_stmt_bind_param($cert_stmt, "ii", $student_id, $course_id);
mysqli_stmt_execute($cert_stmt);
$cert_result = mysqli_stmt_get_result($cert_stmt);
$certificate = mysqli_fetch_assoc($cert_result);
mysqli_stmt_close($cert_stmt);

$page_title = 'Course Progress Details';
require_once '../../includes/header-student.php';
?>
<style>
    .container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .page-header {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .progress-circle-container {
        background: white;
        padding: 40px;
        border-radius: 15px;
        text-align: center;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .circular-progress {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: conic-gradient(
            #10b981 0deg,
            #10b981 calc(<?php echo $progress_data['completed_percentage']; ?> * 3.6deg),
            #e5e7eb calc(<?php echo $progress_data['completed_percentage']; ?> * 3.6deg)
        );
        margin: 20px auto;
        position: relative;
    }

    .circular-progress::before {
        content: '';
        position: absolute;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: white;
    }

    .progress-text {
        position: relative;
        z-index: 1;
        font-size: 32px;
        font-weight: bold;
        color: #3198F8;
    }

    .breakdown-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin: 30px 0;
    }

    .breakdown-card {
        background: white;
        padding: 30px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.2s;
    }

    .breakdown-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .breakdown-number {
        font-size: 42px;
        font-weight: bold;
        color: #3198F8;
        margin-bottom: 10px;
    }

    .breakdown-label {
        font-size: 16px;
        color: #666;
        margin-bottom: 15px;
    }

    .breakdown-percentage {
        font-size: 24px;
        color: #10b981;
        font-weight: 600;
    }

    .certificate-banner {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: white;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        margin-top: 30px;
        box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
    }

    .certificate-banner h2 {
        margin-bottom: 15px;
    }

    .btn-back {
        display: inline-block;
        padding: 10px 20px;
        background: #6b7280;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .btn-back:hover {
        background: #4b5563;
    }
</style>

<div class="container">
    <a href="index.php" class="btn-back">← Back to Progress</a>

    <div class="page-header">
        <h1><?php echo htmlspecialchars($course['course_name']); ?></h1>
        <p style="color: #666; margin-top: 10px;"><?php echo htmlspecialchars($course['course_code']); ?> - Progress Details</p>
    </div>

    <div class="progress-circle-container">
        <h2 style="margin-bottom: 20px;">Overall Progress</h2>
        <div class="circular-progress">
            <div class="progress-text"><?php echo $progress_data['completed_percentage']; ?>%</div>
        </div>
        <p style="color: #666; margin-top: 20px;">
            Last updated: <?php 
            $stmt = mysqli_prepare($conn, "SELECT last_updated FROM progress WHERE student_id = ? AND course_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
            mysqli_stmt_execute($stmt);
            $prog_result = mysqli_stmt_get_result($stmt);
            $prog = mysqli_fetch_assoc($prog_result);
            mysqli_stmt_close($stmt);
            echo $prog && $prog['last_updated'] ? date('F d, Y \a\t g:i A', strtotime($prog['last_updated'])) : 'Not started';
            ?>
        </p>
    </div>

    <h2 style="font-size: 24px; margin-bottom: 20px; color: #333;">Detailed Breakdown</h2>
    <div class="breakdown-grid">
        <div class="breakdown-card">
            <div class="breakdown-number"><?php echo $breakdown['materials']['completed']; ?>/<?php echo $breakdown['materials']['total']; ?></div>
            <div class="breakdown-label">Learning Materials</div>
            <div class="breakdown-percentage"><?php echo $breakdown['materials']['percentage']; ?>%</div>
        </div>

        <div class="breakdown-card">
            <div class="breakdown-number"><?php echo $breakdown['assignments']['completed']; ?>/<?php echo $breakdown['assignments']['total']; ?></div>
            <div class="breakdown-label">Assignments Submitted</div>
            <div class="breakdown-percentage"><?php echo $breakdown['assignments']['percentage']; ?>%</div>
        </div>

        <div class="breakdown-card">
            <div class="breakdown-number"><?php echo $breakdown['quizzes']['completed']; ?>/<?php echo $breakdown['quizzes']['total']; ?></div>
            <div class="breakdown-label">Quizzes Attempted</div>
            <div class="breakdown-percentage"><?php echo $breakdown['quizzes']['percentage']; ?>%</div>
        </div>
    </div>

    <?php if ($certificate): ?>
        <div class="certificate-banner">
            <h2>🎉 Congratulations!</h2>
            <p style="font-size: 18px; margin-top: 10px;">You've completed this course and earned your certificate!</p>
            <p style="margin-top: 15px; font-size: 14px; opacity: 0.9;">
                Certificate Code: <strong><?php echo htmlspecialchars($certificate['certificate_code']); ?></strong><br>
                Issued on: <?php echo date('F d, Y', strtotime($certificate['issued_at'])); ?>
            </p>
        </div>
    <?php elseif ($progress_data['completed_percentage'] >= 100): ?>
        <div class="certificate-banner">
            <h2>🎉 Course Complete!</h2>
            <p style="font-size: 18px; margin-top: 10px;">Your certificate is being prepared...</p>
        </div>
    <?php endif; ?>
</div>

<?php
mysqli_close($conn);
require_once '../../includes/footer.php';
?>
