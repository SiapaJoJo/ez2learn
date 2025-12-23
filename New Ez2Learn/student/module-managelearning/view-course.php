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

$materials_query = "
    SELECT material_id, title, material_type, file_path, created_at
    FROM materials
    WHERE course_id = ?
    ORDER BY created_at DESC
";
$stmt = mysqli_prepare($conn, $materials_query);
mysqli_stmt_bind_param($stmt, "i", $course_id);
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

mysqli_close($conn);

$page_title = $course['course_code'] . ' - ' . $course['course_name'];
require_once '../../includes/header-student.php';
?>
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
                        <div class="material-item">
                            <div class="item-info">
                                <h4><?php echo htmlspecialchars($material['title']); ?></h4>
                                <p>
                                    <span class="badge badge-<?php echo $material['material_type']; ?>"><?php echo strtoupper($material['material_type']); ?></span>
                                    <span style="margin-left: 10px; color: #999;">Uploaded: <?php echo date('M d, Y', strtotime($material['created_at'])); ?></span>
                                </p>
                            </div>
                            <div>
                                <?php if ($material['material_type'] === 'link'): ?>
                                    <a href="<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank" class="btn-download">Open Link</a>
                                <?php else: ?>
                                    <a href="../../<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank" class="btn-download">Download</a>
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
</body>
</html>

