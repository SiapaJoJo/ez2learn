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
    $query = "
        SELECT 
            a.*,
            c.course_code, c.course_name,
            u.name as lecturer_name, u.email as lecturer_email
        FROM assignments a
        LEFT JOIN courses c ON a.course_id = c.course_id
        LEFT JOIN users u ON a.lecturer_id = u.user_id
        WHERE a.assignment_id = ?
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $assignment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $assignment = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} else {
    $assignment = null;
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Assignment - Admin - Ez2Learn</title>
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

        .assignment-details {
            margin-top: 20px;
        }

        .detail-row {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #1e3a5f;
            font-size: 16px;
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
                <h1 style="margin-bottom: 20px; color: #1e3a5f;"><?php echo htmlspecialchars($assignment['title']); ?></h1>
                
                <div class="assignment-details">
                    <div class="detail-row">
                        <div class="detail-label">Course</div>
                        <div class="detail-value"><?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Lecturer</div>
                        <div class="detail-value"><?php echo htmlspecialchars($assignment['lecturer_name'] . ' (' . $assignment['lecturer_email'] . ')'); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Due Date</div>
                        <div class="detail-value"><?php echo $assignment['due_date'] ? date('F d, Y', strtotime($assignment['due_date'])) : 'Not set'; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Total Marks</div>
                        <div class="detail-value"><?php echo $assignment['total_marks'] ?? 'Not set'; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Description</div>
                        <div class="detail-value" style="white-space: pre-wrap;"><?php echo htmlspecialchars($assignment['description'] ?? 'No description provided.'); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 40px; color: #6b7280;">Assignment not found</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

