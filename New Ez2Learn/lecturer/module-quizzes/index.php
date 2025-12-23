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

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ez2learn';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$lecturer_id = $_SESSION['user_id'] ?? 0;
$error = '';
$success = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $quiz_id = (int)$_GET['id'];
    
    $stmt = mysqli_prepare($conn, "SELECT quiz_id FROM quizzes WHERE quiz_id = ? AND lecturer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $lecturer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $delete_stmt = mysqli_prepare($conn, "DELETE FROM quizzes WHERE quiz_id = ?");
        mysqli_stmt_bind_param($delete_stmt, "i", $quiz_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $success = 'Quiz deleted successfully!';
        } else {
            $error = 'Failed to delete quiz.';
        }
        mysqli_stmt_close($delete_stmt);
    } else {
        $error = 'Quiz not found or unauthorized.';
    }
    mysqli_stmt_close($stmt);
}

$quizzes_query = "
    SELECT 
        q.quiz_id, q.title, q.total_marks,
        c.course_code, c.course_name,
        COUNT(DISTINCT qq.question_id) as questions_count,
        COUNT(DISTINCT qa.student_id) as attempts_count
    FROM quizzes q
    LEFT JOIN courses c ON q.course_id = c.course_id
    LEFT JOIN quiz_questions qq ON q.quiz_id = qq.quiz_id
    LEFT JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id
    WHERE q.lecturer_id = ?
    GROUP BY q.quiz_id
    ORDER BY q.quiz_id DESC
";
$stmt = mysqli_prepare($conn, $quizzes_query);
mysqli_stmt_bind_param($stmt, "i", $lecturer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$quizzes = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

mysqli_close($conn);

$page_title = 'My Quizzes';
require_once '../../includes/header-lecturer.php';
?>
    <style>
        .page-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .page-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }

        .btn-add {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
        }

        .content {
            padding: 30px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f3f4f6;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            color: #374151;
        }

        td {
            padding: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .btn-action {
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.75rem;
            margin-right: 0.5rem;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-edit:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-view {
            background: #10b981;
            color: white;
        }

        .btn-view:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
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

        @media (max-width: 768px) {
            .page-header {
                padding: 1.25rem 1.5rem;
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <h1 class="page-title">My Quizzes</h1>
                <a href="create-quiz.php" class="btn-add">+ Create Quiz</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="content">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Course</th>
                                <th>Questions</th>
                                <th>Total Marks</th>
                                <th>Attempts</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($quizzes)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #6b7280;">No quizzes found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($quizzes as $quiz): ?>
                                    <tr>
                                        <td><?php echo $quiz['quiz_id']; ?></td>
                                        <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                        <td><?php echo htmlspecialchars($quiz['course_code'] . ' - ' . $quiz['course_name']); ?></td>
                                        <td><?php echo $quiz['questions_count']; ?></td>
                                        <td><?php echo $quiz['total_marks'] ?? 'N/A'; ?></td>
                                        <td><?php echo $quiz['attempts_count']; ?></td>
                                        <td>
                                            <a href="edit-quiz.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="btn-action btn-edit">Edit</a>
                                            <a href="view-quiz.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="btn-action btn-view">View</a>
                                            <button class="btn-action btn-delete" onclick="deleteQuiz(<?php echo $quiz['quiz_id']; ?>, '<?php echo htmlspecialchars($quiz['title']); ?>')">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteQuiz(quizId, title) {
            if (confirm(`Are you sure you want to delete quiz "${title}"? This will also delete all questions and attempts.`)) {
                window.location.href = `index.php?action=delete&id=${quizId}`;
            }
        }
    </script>
</body>
</html>

