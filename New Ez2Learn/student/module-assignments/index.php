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
$filter = $_GET['filter'] ?? 'all';

// Get assignments for enrolled courses
$where_clause = "WHERE e.student_id = $student_id";
if ($filter === 'pending') {
    $where_clause .= " AND s.submission_id IS NULL AND (a.due_date IS NULL OR a.due_date >= CURDATE())";
} elseif ($filter === 'submitted') {
    $where_clause .= " AND s.submission_id IS NOT NULL";
} elseif ($filter === 'graded') {
    $where_clause .= " AND s.marks IS NOT NULL";
}

$assignments_query = "
    SELECT 
        a.assignment_id, a.title, a.description, a.due_date, a.total_marks,
        c.course_code, c.course_name,
        s.submission_id, s.marks, s.feedback, s.submitted_at,
        CASE 
            WHEN s.submission_id IS NOT NULL THEN 'submitted'
            WHEN a.due_date IS NOT NULL AND a.due_date < CURDATE() THEN 'overdue'
            ELSE 'pending'
        END as status
    FROM assignments a
    INNER JOIN enrollments e ON a.course_id = e.course_id
    LEFT JOIN courses c ON a.course_id = c.course_id
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = $student_id
    $where_clause
    ORDER BY 
        CASE 
            WHEN a.due_date IS NOT NULL AND a.due_date < CURDATE() THEN 0
            WHEN a.due_date IS NOT NULL THEN 1
            ELSE 2
        END,
        a.due_date ASC
";
$result = mysqli_query($conn, $assignments_query);
$assignments = mysqli_fetch_all($result, MYSQLI_ASSOC);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - Student - Ez2Learn</title>
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

        .nav-menu a:hover, .nav-menu a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .profile-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .profile-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .profile-dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a {
            display: block;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-menu a:first-child {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        .dropdown-menu a:last-child {
            border-bottom: none;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .dropdown-menu a:hover {
            background: #f8f9fa;
            color: #3198F8;
        }

        .dropdown-menu a.logout {
            color: #c33;
        }

        .dropdown-menu a.logout:hover {
            background: #fee;
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
            overflow: hidden;
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

        .filters {
            padding: 20px 30px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 15px;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            color: #333;
        }

        .filter-btn.active {
            background: #3198F8;
            color: white;
            border-color: #3198F8;
        }

        .content {
            padding: 30px;
        }

        .assignments-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .assignment-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .assignment-card:hover {
            border-color: #3198F8;
            box-shadow: 0 4px 12px rgba(49, 152, 248, 0.1);
        }

        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .assignment-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .assignment-course {
            color: #666;
            font-size: 14px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-submitted {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-graded {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        .assignment-details {
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            gap: 20px;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            min-width: 100px;
        }

        .detail-value {
            color: #333;
        }

        .assignment-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-action {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .btn-submit {
            background: #10b981;
            color: white;
        }

        .btn-submit:hover {
            background: #059669;
        }

        .btn-view {
            background: #3b82f6;
            color: white;
        }

        .btn-view:hover {
            background: #2563eb;
        }

        .btn-disabled {
            background: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .marks-display {
            font-size: 18px;
            font-weight: bold;
            color: #3198F8;
            margin-top: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .header-top {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }

            .assignment-header {
                flex-direction: column;
                gap: 10px;
            }
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
                    <li><a href="../module-managelearning/index.php">My Courses</a></li>
                    <li><a href="index.php" class="active">Assignments</a></li>
                    <li><a href="../module-progress/index.php">Progress</a></li>
                    <li><a href="../module-usermanagement/index.php">Profile</a></li>
                </ul>
                <div class="profile-dropdown" id="profileDropdown">
                    <button class="profile-btn" onclick="toggleDropdown()">
                        <div class="profile-icon"><?php echo strtoupper(substr($_SESSION['name'] ?? 'S', 0, 1)); ?></div>
                        <span><?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?></span>
                        <span>▼</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="../../edit-profile.php">Edit Profile</a>
                        <a href="../../logout.php" class="logout">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <h1 class="page-title">My Assignments</h1>
            </div>

            <div class="filters">
                <a href="index.php" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                <a href="index.php?filter=pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="index.php?filter=submitted" class="filter-btn <?php echo $filter === 'submitted' ? 'active' : ''; ?>">Submitted</a>
                <a href="index.php?filter=graded" class="filter-btn <?php echo $filter === 'graded' ? 'active' : ''; ?>">Graded</a>
            </div>

            <div class="content">
                <?php if (empty($assignments)): ?>
                    <div class="empty-state">
                        <p>No assignments found.</p>
                    </div>
                <?php else: ?>
                    <div class="assignments-list">
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="assignment-card">
                                <div class="assignment-header">
                                    <div>
                                        <div class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                        <div class="assignment-course"><?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?></div>
                                    </div>
                                    <span class="badge badge-<?php echo $assignment['status']; ?>">
                                        <?php echo ucfirst($assignment['status']); ?>
                                    </span>
                                </div>

                                <div class="assignment-details">
                                    <?php if ($assignment['description']): ?>
                                        <div class="detail-row">
                                            <span class="detail-label">Description:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars(substr($assignment['description'], 0, 150)); ?><?php echo strlen($assignment['description']) > 150 ? '...' : ''; ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Due Date:</span>
                                        <span class="detail-value">
                                            <?php 
                                            if ($assignment['due_date']) {
                                                $due_date = strtotime($assignment['due_date']);
                                                $now = time();
                                                if ($due_date < $now) {
                                                    echo '<span style="color: #ef4444;">' . date('M d, Y', $due_date) . ' (Overdue)</span>';
                                                } else {
                                                    echo date('M d, Y', $due_date);
                                                }
                                            } else {
                                                echo 'No due date';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Total Marks:</span>
                                        <span class="detail-value"><?php echo $assignment['total_marks'] ?? 'N/A'; ?></span>
                                    </div>
                                    <?php if ($assignment['submitted_at']): ?>
                                        <div class="detail-row">
                                            <span class="detail-label">Submitted:</span>
                                            <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($assignment['submitted_at'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($assignment['marks'] !== null): ?>
                                        <div class="marks-display">
                                            Marks: <?php echo $assignment['marks']; ?> / <?php echo $assignment['total_marks']; ?>
                                            <?php 
                                            $percentage = ($assignment['marks'] / $assignment['total_marks']) * 100;
                                            echo '(' . round($percentage, 1) . '%)';
                                            ?>
                                        </div>
                                        <?php if ($assignment['feedback']): ?>
                                            <div class="detail-row" style="margin-top: 10px;">
                                                <span class="detail-label">Feedback:</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($assignment['feedback']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="assignment-actions">
                                    <?php if ($assignment['status'] === 'pending' || $assignment['status'] === 'overdue'): ?>
                                        <a href="submit-assignment.php?assignment_id=<?php echo $assignment['assignment_id']; ?>" class="btn-action btn-submit">
                                            <?php echo $assignment['submission_id'] ? 'Resubmit' : 'Submit Assignment'; ?>
                                        </a>
                                    <?php endif; ?>
                                    <a href="view-assignment.php?assignment_id=<?php echo $assignment['assignment_id']; ?>" class="btn-action btn-view">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('active');
        }
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html>

