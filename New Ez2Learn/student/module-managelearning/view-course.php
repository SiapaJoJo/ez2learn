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

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ez2learn';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['course_code']); ?> - Student - Ez2Learn</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .header {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            padding: 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-top {
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo-text {
            font-size: 24px;
            font-weight: bold;
        }

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .page-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .btn-back {
            background: #6b7280;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }

        .btn-back:hover {
            background: #4b5563;
        }

        .page-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .materials-list, .quizzes-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .material-item, .quiz-item {
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .material-item:hover, .quiz-item:hover {
            background: #f8f9fa;
            border-color: #3198F8;
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
            background: #3198F8;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-download:hover, .btn-attempt:hover {
            background: #1e6bb8;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div class="logo-text">Ez2Learn</div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></h1>
            <p style="color: #666; margin-top: 5px;"><?php echo htmlspecialchars($course['description'] ?? 'No description'); ?></p>
            <a href="index.php" class="btn-back">← Back to Courses</a>
        </div>

        <div class="page-container">
            <h2 class="section-title">Learning Materials</h2>
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

        <div class="page-container">
            <h2 class="section-title">Quizzes</h2>
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
</body>
</html>

