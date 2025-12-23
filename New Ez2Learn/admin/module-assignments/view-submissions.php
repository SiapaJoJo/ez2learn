<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

require_once '../../includes/db-config.php';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$assignment_id = (int)($_GET['assignment_id'] ?? 0);

if ($assignment_id > 0) {

    $assignment_query = "SELECT title FROM assignments WHERE assignment_id = ?";
    $stmt = mysqli_prepare($conn, $assignment_query);
    mysqli_stmt_bind_param($stmt, "i", $assignment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $assignment = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    $submissions_query = "
        SELECT 
            s.*,
            u.name as student_name, u.email as student_email
        FROM submissions s
        JOIN users u ON s.student_id = u.user_id
        WHERE s.assignment_id = ?
        ORDER BY s.submitted_at DESC
    ";
    $stmt = mysqli_prepare($conn, $submissions_query);
    mysqli_stmt_bind_param($stmt, "i", $assignment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $submissions = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    $assignment = null;
    $submissions = [];
}

mysqli_close($conn);

$page_title = 'View Submissions';
require_once '../../includes/header-admin.php';
?>
<style>
        .page-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
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
            margin-bottom: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-back:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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

        .btn-download {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-download:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }
    </style>

    <div class="container">
        <div class="page-container">
            <a href="index.php" class="btn-back">← Back to Assignments</a>
            
            <?php if ($assignment): ?>
                <h1 style="margin-bottom: 1.5rem; color: #1e293b; font-size: 1.5rem; font-weight: 700;">Submissions: <?php echo htmlspecialchars($assignment['title']); ?></h1>
                
                <?php if (empty($submissions)): ?>
                    <p style="text-align: center; padding: 2.5rem; color: #64748b;">No submissions found for this assignment.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Submitted At</th>
                                <th>Marks</th>
                                <th>Feedback</th>
                                <th>File</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($submission['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($submission['student_email']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($submission['submitted_at'])); ?></td>
                                    <td><?php echo $submission['marks'] ?? 'Not graded'; ?></td>
                                    <td><?php echo htmlspecialchars($submission['feedback'] ?? 'No feedback'); ?></td>
                                    <td>
                                        <?php if ($submission['file_path']): ?>
                                            <a href="../../download-file.php?file=<?php echo urlencode($submission['file_path']); ?>" class="btn-download">Download</a>
                                        <?php else: ?>
                                            No file
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 2.5rem; color: #64748b;">Assignment not found</p>
            <?php endif; ?>
        </div>
    </div>
<?php require_once '../../includes/footer.php'; ?>

