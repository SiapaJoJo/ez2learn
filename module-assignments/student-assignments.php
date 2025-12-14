<?php
session_start();

// Check authentication and role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../module-usermanagement/login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../module-usermanagement/login.php');
    exit();
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ez2learn';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$student_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Check for session messages
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Fetch all published and closed assignments with student's submission status
$stmt = mysqli_prepare($conn, "
    SELECT 
        a.*,
        u.username as staff_username,
        u.first_name as staff_first_name,
        u.last_name as staff_last_name,
        s.id as submission_id,
        s.submitted_at,
        s.marks_awarded,
        s.submission_status,
        s.feedback,
        CASE 
            WHEN s.id IS NOT NULL THEN 1
            ELSE 0
        END as has_submitted
    FROM assignments a
    LEFT JOIN users u ON a.created_by = u.id
    LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
    WHERE a.status IN ('published', 'closed')
    ORDER BY 
        CASE 
            WHEN a.due_date >= NOW() THEN 0
            ELSE 1
        END,
        a.due_date ASC
");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assignments = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Calculate statistics for each assignment
$pending_count = 0;
$overdue_count = 0;
$submitted_count = 0;

foreach ($assignments as &$assignment) {
    $is_past_due = strtotime($assignment['due_date']) < time();
    $is_closed = $assignment['status'] === 'closed';
    
    if ($assignment['has_submitted']) {
        $assignment['display_status'] = 'submitted';
        $submitted_count++;
    } elseif ($is_closed) {
        $assignment['display_status'] = 'closed';
        $overdue_count++; // Count closed as overdue for stats
    } elseif ($is_past_due) {
        $assignment['display_status'] = 'overdue';
        $overdue_count++;
    } else {
        $assignment['display_status'] = 'pending';
        $pending_count++;
    }
}
unset($assignment); // Break reference

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

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .summary-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .summary-label {
            color: #666;
            font-size: 14px;
        }

        .summary-card.pending .summary-value {
            color: #FF9800;
        }

        .summary-card.overdue .summary-value {
            color: #f44336;
        }

        .summary-card.submitted .summary-value {
            color: #4CAF50;
        }

        .assignments-section {
            margin-bottom: 40px;
        }

        .section-header {
            background: white;
            padding: 20px 25px;
            border-radius: 15px 15px 0 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .section-header h2 {
            color: #333;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .assignments-grid {
            background: white;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }

        .assignment-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            position: relative;
        }

        .assignment-card:hover {
            border-color: #3198F8;
            box-shadow: 0 4px 15px rgba(49, 152, 248, 0.2);
        }

        .assignment-card.overdue {
            border-color: #f44336;
            background: #ffebee;
        }

        .assignment-card.submitted {
            border-color: #4CAF50;
            background: #f1f8f4;
        }

        .assignment-card h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .assignment-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 15px 0;
            font-size: 14px;
            color: #666;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .assignment-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .assignment-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(49, 152, 248, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(49, 152, 248, 0.5);
        }

        .btn-secondary {
            background: #9E9E9E;
            color: white;
        }

        .btn-secondary:hover {
            background: #757575;
        }

        .btn-success {
            background: #4CAF50;
            color: white;
        }

        .btn-success:hover {
            background: #388E3C;
        }

        .submission-info {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .submission-info h4 {
            color: #2e7d32;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .submission-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 14px;
            color: #333;
        }

        .marks-display {
            font-size: 20px;
            font-weight: bold;
            color: #2e7d32;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .time-remaining {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .time-urgent {
            background: #ffebee;
            color: #c62828;
        }

        .time-soon {
            background: #fff3cd;
            color: #856404;
        }

        .time-good {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }

        .status-submitted {
            background: #d4edda;
            color: #155724;
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

            .summary-cards {
                grid-template-columns: 1fr;
            }

            .assignment-meta {
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
                    <li><a href="../dashboard-student.php">Dashboard</a></li>
                    <li><a href="../module-managelearning/main.php">My Courses</a></li>
                    <li><a href="student-assignments.php" class="active">Assignments</a></li>
                    <li><a href="student-grades.php">Grades</a></li>
                </ul>
                <div class="profile-dropdown" id="profileDropdown">
                    <button class="profile-btn" onclick="toggleDropdown()">
                        <div class="profile-icon"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <span>▼</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="../module-usermanagement/edit-profile.php">Edit Profile</a>
                        <a href="../module-usermanagement/logout.php" class="logout">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1>My Assignments</h1>
            <p>Track and submit your assignments</p>
        </div>

        <div class="summary-cards">
            <div class="summary-card pending">
                <div class="summary-value"><?php echo $pending_count; ?></div>
                <div class="summary-label">Pending</div>
            </div>
            <div class="summary-card overdue">
                <div class="summary-value"><?php echo $overdue_count; ?></div>
                <div class="summary-label">Overdue</div>
            </div>
            <div class="summary-card submitted">
                <div class="summary-value"><?php echo $submitted_count; ?></div>
                <div class="summary-label">Submitted</div>
            </div>
            <div class="summary-card">
                <div class="summary-value" style="color: #3198F8;"><?php echo count($assignments); ?></div>
                <div class="summary-label">Total</div>
            </div>
        </div>

        <!-- All Assignments -->
        <?php if (!empty($assignments)): ?>
            <div class="assignments-section">
                <div class="section-header">
                    <h2>All Assignments</h2>
                </div>
                <div class="assignments-grid">
                    <?php foreach ($assignments as $assignment): 
                        $status = $assignment['display_status'];
                        $card_class = '';
                        $status_text = '';
                        $status_badge_class = '';
                        
                        if ($status === 'submitted') {
                            $card_class = 'submitted';
                            $status_text = 'Submitted';
                            $status_badge_class = 'status-submitted';
                        } elseif ($status === 'closed') {
                            $card_class = 'overdue';
                            $status_text = 'Closed';
                            $status_badge_class = 'status-overdue';
                        } elseif ($status === 'overdue') {
                            $card_class = 'overdue';
                            $status_text = 'Overdue';
                            $status_badge_class = 'status-overdue';
                        } else {
                            $status_text = 'Pending';
                            $status_badge_class = 'status-pending';
                        }
                        
                        // Calculate time remaining for pending assignments
                        $time_display = '';
                        if ($status === 'pending') {
                            $time_diff = strtotime($assignment['due_date']) - time();
                            $days_remaining = floor($time_diff / 86400);
                            $hours_remaining = floor(($time_diff % 86400) / 3600);
                            
                            if ($days_remaining < 1) {
                                $time_class = 'time-urgent';
                                $time_display = '<span class="time-remaining ' . $time_class . '">' . $hours_remaining . 'h left</span>';
                            } elseif ($days_remaining <= 2) {
                                $time_class = 'time-soon';
                                $time_display = '<span class="time-remaining ' . $time_class . '">' . $days_remaining . 'd ' . $hours_remaining . 'h left</span>';
                            } else {
                                $time_class = 'time-good';
                                $time_display = '<span class="time-remaining ' . $time_class . '">' . $days_remaining . ' days left</span>';
                            }
                        }
                    ?>
                        <div class="assignment-card <?php echo $card_class; ?>">
                            <span class="status-badge <?php echo $status_badge_class; ?>"><?php echo $status_text; ?></span>
                            <h3>
                                <?php echo htmlspecialchars($assignment['title']); ?>
                                <?php echo $time_display; ?>
                            </h3>
                            
                            <div class="assignment-meta">
                                <div class="meta-item">
                                    <span <?php echo $status === 'overdue' ? 'style="color: #f44336; font-weight: 600;"' : ''; ?>>
                                        Due: <?php echo date('M d, Y H:i', strtotime($assignment['due_date'])); ?>
                                        <?php echo $status === 'overdue' ? '(OVERDUE)' : ''; ?>
                                    </span>
                                </div>
                                <div class="meta-item">
                                    <span>Marks: <?php echo htmlspecialchars($assignment['total_marks']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span>
                                        Instructor: <?php 
                                        $staff_name = trim(($assignment['staff_first_name'] ?? '') . ' ' . ($assignment['staff_last_name'] ?? ''));
                                        echo htmlspecialchars($staff_name ?: $assignment['staff_username']);
                                        ?>
                                    </span>
                                </div>
                                <?php if ($status === 'submitted'): ?>
                                    <div class="meta-item">
                                        <span>Submitted: <?php echo date('M d, Y H:i', strtotime($assignment['submitted_at'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="assignment-description">
                                <?php echo nl2br(htmlspecialchars(substr($assignment['description'], 0, 200))); ?>
                                <?php echo strlen($assignment['description']) > 200 ? '...' : ''; ?>
                            </div>

                            <?php if ($status === 'submitted'): ?>
                                <div class="submission-info">
                                    <h4>Submission Status</h4>
                                    <div class="submission-details">
                                        <?php if ($assignment['submission_status'] === 'graded'): ?>
                                            <div class="marks-display">
                                                Score: <?php echo $assignment['marks_awarded']; ?> / <?php echo $assignment['total_marks']; ?>
                                                (<?php echo round(($assignment['marks_awarded'] / $assignment['total_marks']) * 100, 1); ?>%)
                                            </div>
                                            <?php if (!empty($assignment['feedback'])): ?>
                                                <div style="margin-top: 10px;">
                                                    <strong>Instructor Feedback:</strong>
                                                    <div style="margin-top: 5px; padding: 10px; background: white; border-radius: 6px;">
                                                        <?php echo nl2br(htmlspecialchars($assignment['feedback'])); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #666;">⏳ Waiting for grading...</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="assignment-actions">
                                <?php if ($status === 'pending'): ?>
                                    <a href="submit-assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-primary">
                                        Submit Assignment
                                    </a>
                                <?php elseif ($status === 'closed'): ?>
                                    <button class="btn btn-secondary" disabled>
                                        Closed by Instructor
                                    </button>
                                <?php elseif ($status === 'overdue'): ?>
                                    <button class="btn btn-secondary" disabled>
                                        Submission Deadline Passed
                                    </button>
                                <?php elseif ($status === 'submitted'): ?>
                                    <button class="btn btn-success" disabled>
                                        Submitted
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($assignments)): ?>
            <div class="assignments-section">
                <div class="section-header">
                    <h2>All Assignments</h2>
                </div>
                <div class="assignments-grid">
                    <div class="empty-state">
                        <div class="empty-state-icon"></div>
                        <h3>No Assignments Available</h3>
                        <p>Your instructors haven't published any assignments yet. Check back later!</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
