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

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ez2learn';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$assignment_id = (int)($_GET['assignment_id'] ?? 0);

if ($assignment_id > 0) {
    // Get assignment info
    $assignment_query = "SELECT title FROM assignments WHERE assignment_id = ?";
    $stmt = mysqli_prepare($conn, $assignment_query);
    mysqli_stmt_bind_param($stmt, "i", $assignment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $assignment = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Get submissions
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions - Admin - Ez2Learn</title>
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

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-menu {
            display: flex;
            gap: 10px;
            list-style: none;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-container {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
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
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background: #4b5563;
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
            background: #3b82f6;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div class="logo-text">Ez2Learn</div>
            <div class="header-right">
                <ul class="nav-menu">
                    <li><a href="../index.php">Dashboard</a></li>
                    <li><a href="../module-usermanagement/index.php">Users</a></li>
                    <li><a href="../module-managelearning/index.php">Courses</a></li>
                    <li><a href="index.php">Assignments</a></li>
                    <li><a href="../module-progress/index.php">Progress</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-container">
            <a href="index.php" class="btn-back">← Back to Assignments</a>
            
            <?php if ($assignment): ?>
                <h1 style="margin-bottom: 20px; color: #1e3a5f;">Submissions: <?php echo htmlspecialchars($assignment['title']); ?></h1>
                
                <?php if (empty($submissions)): ?>
                    <p style="text-align: center; padding: 40px; color: #6b7280;">No submissions found for this assignment.</p>
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
                <p style="text-align: center; padding: 40px; color: #6b7280;">Assignment not found</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

