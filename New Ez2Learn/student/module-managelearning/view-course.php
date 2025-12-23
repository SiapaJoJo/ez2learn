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

// MODULE 4: Include progress service
require_once '../../includes/progress_service.php';

$student_id = $_SESSION['user_id'] ?? 0;
$course_id = (int)($_GET['course_id'] ?? 0);

$verify_query = "
    SELECT c.course_id, c.course_code, c.course_name, c.description
    FROM courses c
    INNER JOIN enrollments e ON c.course_id = e.course_id
    WHERE c.course_id = ? AND e.student_id = ?
";
$stmt = mysqli_prepare($conn, $verify_query);
mysqli_stmt_bind_param($stmt, "ii", $course_id, $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$course = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$course) {
    header('Location: index.php');
    exit();
}

// MODULE 4: Ensure progress record exists for this enrollment
ensure_progress_record($conn, $student_id, $course_id);

// MODULE 4: Get material completion status
$materials_query = "
    SELECT m.material_id, m.title, m.material_type, m.file_path, m.created_at,
           mp.id as is_completed, mp.completed_at
    FROM materials m
    LEFT JOIN material_progress mp ON m.material_id = mp.material_id AND mp.student_id = ?
    WHERE m.course_id = ?
    ORDER BY m.created_at DESC
";
$stmt = mysqli_prepare($conn, $materials_query);
mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$materials = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$quizzes_query = "
    SELECT quiz_id, title, total_marks
    FROM quizzes
    WHERE course_id = ?
    ORDER BY quiz_id DESC
";
$stmt = mysqli_prepare($conn, $quizzes_query);
mysqli_stmt_bind_param($stmt, "i", $course_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$quizzes = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// MODULE 4: Get current progress
$progress_data = recalc_and_persist_course_progress($conn, $student_id, $course_id);

mysqli_close($conn);

$page_title = $course['course_code'] . ' - ' . $course['course_name'];
require_once '../../includes/header-student.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['course_code']); ?> - Student - Ez2Learn</title>
    <!-- MODULE 4: Progress Tracker JS -->
    <script src="../../assets/js/progress-tracker.js"></script>
    <style>
        .page-header {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .page-header-content {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .btn-back {
            background: #6b7280;
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-back:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        .page-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .page-container-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .page-container-content {
            padding: 2rem;
        }

        .materials-list, .quizzes-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .material-item, .quiz-item {
            padding: 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
        }

        .material-item:hover, .quiz-item:hover {
            background: #f8fafc;
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .item-info h4 {
            color: #333;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .item-info p {
            color: #666;
            font-size: 12px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pdf {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-video {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-link {
            background: #d1fae5;
            color: #065f46;
        }

        .btn-download, .btn-attempt {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-download:hover, .btn-attempt:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
        }

        /* MODULE 4: Progress Bar Styles */
        .progress-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .progress-bar-wrapper {
            width: 100%;
            height: 30px;
            background: #e5e7eb;
            border-radius: 15px;
            overflow: hidden;
            margin: 15px 0;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            border-radius: 15px;
            transition: width 0.8s ease-out;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-mark-complete {
            background: #10b981;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }

        .btn-mark-complete:hover {
            background: #059669;
        }

        .btn-mark-complete:disabled, .btn-completed {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .completed-icon {
            color: #10b981;
            font-weight: bold;
            margin-left: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .page-container-content {
                padding: 1.5rem;
            }

            .page-header-content {
                padding: 1.25rem 1.5rem;
            }

            .material-item, .quiz-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>

    <div class="container">
        <div class="page-header">
            <div class="page-header-content">
                <a href="index.php" class="btn-back">← Back to Courses</a>
                <h1><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></h1>
                <p><?php echo htmlspecialchars($course['description'] ?? 'No description'); ?></p>
            </div>
        </div>

        <!-- MODULE 4: Progress Bar -->
        <div class="progress-container">
            <h3 style="margin-bottom: 10px;">Course Progress</h3>
            <div class="progress-bar-wrapper">
                <div class="progress-bar" style="width: <?php echo $progress_data['completed_percentage']; ?>%;" 
                     aria-valuenow="<?php echo $progress_data['completed_percentage']; ?>" 
                     aria-valuemin="0" aria-valuemax="100">
                    <span class="progress-percentage"><?php echo $progress_data['completed_percentage']; ?>%</span>
                </div>
            </div>
            <p style="color: #666; font-size: 14px;">
                Materials: <span class="materials-progress"><?php echo $progress_data['breakdown']['materials']['completed']; ?>/<?php echo $progress_data['breakdown']['materials']['total']; ?></span> | 
                Assignments: <span class="assignments-progress"><?php echo $progress_data['breakdown']['assignments']['completed']; ?>/<?php echo $progress_data['breakdown']['assignments']['total']; ?></span> | 
                Quizzes: <span class="quizzes-progress"><?php echo $progress_data['breakdown']['quizzes']['completed']; ?>/<?php echo $progress_data['breakdown']['quizzes']['total']; ?></span>
            </p>
        </div>

        <div class="page-container">
            <div class="page-container-header">
                <h2 class="section-title">Learning Materials</h2>
            </div>
            <div class="page-container-content">
            <?php if (empty($materials)): ?>
                <div class="empty-state">
                    <p>No learning materials available yet.</p>
                </div>
            <?php else: ?>
                <div class="materials-list">
                    <?php foreach ($materials as $material): ?>
                        <div class="material-item" id="material-<?php echo $material['material_id']; ?>">
                            <div class="item-info">
                                <h4>
                                    <?php echo htmlspecialchars($material['title']); ?>
                                    <?php if ($material['is_completed']): ?>
                                        <span class="completed-icon">✓ Completed</span>
                                    <?php endif; ?>
                                </h4>
                                <p>
                                    <span class="badge badge-<?php echo $material['material_type']; ?>"><?php echo strtoupper($material['material_type']); ?></span>
                                    <span style="margin-left: 10px; color: #999;">Uploaded: <?php echo date('M d, Y', strtotime($material['created_at'])); ?></span>
                                    <?php if ($material['is_completed']): ?>
                                        <span style="margin-left: 10px; color: #10b981;">Completed: <?php echo date('M d, Y', strtotime($material['completed_at'])); ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div>
                                <?php if ($material['material_type'] === 'link'): ?>
                                    <a href="https://www.youtube.com/watch?v=ycvPfQYinNI&list=PL8yOSWVm-vfhIge3Vftl57-oVu90vCnKG" target="_blank" class="btn-download">Open Link</a>
                                <?php elseif ($material['material_type'] === 'pdf'): ?>
                                    <a href="../../uploads/materials/Test material.pdf" target="_blank" class="btn-download">Download</a>
                                <?php elseif ($material['material_type'] === 'video'): ?>
                                    <a href="https://www.youtube.com/watch?v=ycvPfQYinNI&list=PL8yOSWVm-vfhIge3Vftl57-oVu90vCnKG" target="_blank" class="btn-download">Download</a>
                                <?php else: ?>
                                    <a href="../../<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank" class="btn-download">Download</a>
                                <?php endif; ?>
                                <!-- MODULE 4: Mark Complete Button -->
                                <?php if ($material['is_completed']): ?>
                                    <button class="btn-completed" disabled>✓ Completed</button>
                                <?php else: ?>
                                    <button class="btn-mark-complete" 
                                            onclick="markComplete(<?php echo $material['material_id']; ?>)">
                                        Mark Complete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            </div>
        </div>

        <div class="page-container">
            <div class="page-container-header">
                <h2 class="section-title">Quizzes</h2>
            </div>
            <div class="page-container-content">
                <?php if (empty($quizzes)): ?>
                    <div class="empty-state">
                        <p>No quizzes available yet.</p>
                    </div>
                <?php else: ?>
                    <div class="quizzes-list">
                        <?php foreach ($quizzes as $quiz): ?>
                            <div class="quiz-item">
                                <div class="item-info">
                                    <h4><?php echo htmlspecialchars($quiz['title']); ?></h4>
                                    <p>Total Marks: <?php echo $quiz['total_marks']; ?></p>
                                </div>
                                <a href="../module-assignments/attempt-quiz.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="btn-attempt">Attempt Quiz</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODULE 4: Mark Complete Script -->
    <script>
        function markComplete(materialId) {
            // Use global progress tracker
            if (window.progressTracker) {
                const button = document.querySelector(`#material-${materialId} .btn-mark-complete`);
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Processing...';
                }
                
                window.progressTracker.markMaterialComplete(materialId, 
                    () => {
                        // Success callback
                        if (button) {
                            button.textContent = '✓ Completed';
                            button.className = 'btn-completed';
                        }
                        // Add completed icon to title
                        const title = document.querySelector(`#material-${materialId} h4`);
                        if (title && !title.querySelector('.completed-icon')) {
                            const icon = document.createElement('span');
                            icon.className = 'completed-icon';
                            icon.textContent = '✓ Completed';
                            title.appendChild(icon);
                        }
                    },
                    () => {
                        // Error callback (queued for retry)
                        if (button) {
                            button.disabled = false;
                            button.textContent = 'Mark Complete';
                        }
                    }
                );
            }
        }
    </script>
