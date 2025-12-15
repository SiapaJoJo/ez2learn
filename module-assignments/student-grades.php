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

// Fetch all graded submissions
$stmt = mysqli_prepare($conn, "
    SELECT 
        a.id,
        a.title,
        a.description,
        a.total_marks,
        a.due_date,
        s.submitted_at,
        s.marks_awarded,
        s.feedback,
        s.submission_status,
        u.username as staff_username,
        u.first_name as staff_first_name,
        u.last_name as staff_last_name
    FROM assignment_submissions s
    JOIN assignments a ON s.assignment_id = a.id
    LEFT JOIN users u ON a.created_by = u.id
    WHERE s.student_id = ? AND s.submission_status = 'graded'
    ORDER BY s.submitted_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$graded_assignments = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Calculate statistics
$total_graded = count($graded_assignments);
$total_marks_earned = 0;
$total_marks_possible = 0;

foreach ($graded_assignments as $assignment) {
    $total_marks_earned += $assignment['marks_awarded'];
    $total_marks_possible += $assignment['total_marks'];
}

$average_percentage = $total_marks_possible > 0 ? round(($total_marks_earned / $total_marks_possible) * 100, 2) : 0;

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - Student - Ez2Learn</title>
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

        .nav-menu a:hover,
        .nav-menu a.active {
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

        .dropdown-menu a:first-child {
            border-radius: 10px 10px 0 0;
        }

        .dropdown-menu a:last-child {
            border-radius: 0 0 10px 10px;
        }

        .dropdown-menu a.logout {
            color: #f44336;
            border-top: 1px solid #eee;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
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

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .stat-card.total .stat-value {
            color: #3198F8;
        }

        .stat-card.earned .stat-value {
            color: #4CAF50;
        }

        .stat-card.average .stat-value {
            color: #FF9800;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .grades-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-header h2 {
            color: #333;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .grade-item {
            background: #f9f9f9;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid #3198F8;
            transition: all 0.3s ease;
        }

        .grade-item:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .grade-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .grade-title {
            flex: 1;
            min-width: 200px;
        }

        .grade-title h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 8px;
        }

        .grade-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            color: #666;
            font-size: 14px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .grade-score {
            text-align: right;
        }

        .score-display {
            font-size: 32px;
            font-weight: bold;
            color: #2e7d32;
            line-height: 1;
            margin-bottom: 5px;
        }

        .score-fraction {
            color: #666;
            font-size: 14px;
        }

        .percentage-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 5px;
        }

        .percentage-excellent {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .percentage-good {
            background: #e3f2fd;
            color: #1565c0;
        }

        .percentage-average {
            background: #fff3cd;
            color: #856404;
        }

        .percentage-poor {
            background: #ffebee;
            color: #c62828;
        }

        .feedback-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .feedback-section h4 {
            color: #555;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .feedback-content {
            background: white;
            padding: 15px;
            border-radius: 8px;
            color: #333;
            line-height: 1.6;
            font-size: 14px;
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

        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
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

            .grade-header {
                flex-direction: column;
            }

            .grade-score {
                text-align: left;
            }

            .stats-overview {
                grid-template-columns: 1fr;
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
                    <li><a href="../module-usermanagement/dashboard-student.php">Dashboard</a></li>
                    <li><a href="../module-managelearning/main.php">My Courses</a></li>
                    <li><a href="student-assignments.php">Assignments</a></li>
                    <li><a href="student-grades.php" class="active">Grades</a></li>
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
        <div class="page-header">
            <h1>📊 My Grades</h1>
            <p>View your graded assignments and performance</p>
        </div>

        <div class="stats-overview">
            <div class="stat-card total">
                <div class="stat-value"><?php echo $total_graded; ?></div>
                <div class="stat-label">Graded Assignments</div>
            </div>
            <div class="stat-card earned">
                <div class="stat-value"><?php echo $total_marks_earned; ?></div>
                <div class="stat-label">Total Marks Earned</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #666;"><?php echo $total_marks_possible; ?></div>
                <div class="stat-label">Total Marks Possible</div>
            </div>
            <div class="stat-card average">
                <div class="stat-value"><?php echo $average_percentage; ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
        </div>

        <div class="grades-section">
            <div class="section-header">
                <h2>🎓 Graded Assignments</h2>
            </div>

            <?php if (!empty($graded_assignments)): ?>
                <?php foreach ($graded_assignments as $assignment): 
                    $percentage = round(($assignment['marks_awarded'] / $assignment['total_marks']) * 100, 1);
                    
                    // Determine grade badge color
                    if ($percentage >= 90) {
                        $badge_class = 'percentage-excellent';
                    } elseif ($percentage >= 70) {
                        $badge_class = 'percentage-good';
                    } elseif ($percentage >= 50) {
                        $badge_class = 'percentage-average';
                    } else {
                        $badge_class = 'percentage-poor';
                    }
                ?>
                    <div class="grade-item">
                        <div class="grade-header">
                            <div class="grade-title">
                                <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                <div class="grade-meta">
                                    <div class="meta-item">
                                        <span>📅</span>
                                        <span>Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span>✓</span>
                                        <span>Submitted: <?php echo date('M d, Y', strtotime($assignment['submitted_at'])); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span>👨‍🏫</span>
                                        <span>
                                            <?php 
                                            $staff_name = trim(($assignment['staff_first_name'] ?? '') . ' ' . ($assignment['staff_last_name'] ?? ''));
                                            echo htmlspecialchars($staff_name ?: $assignment['staff_username']);
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="grade-score">
                                <div class="score-display"><?php echo $percentage; ?>%</div>
                                <div class="score-fraction">
                                    <?php echo $assignment['marks_awarded']; ?> / <?php echo $assignment['total_marks']; ?> marks
                                </div>
                                <div class="percentage-badge <?php echo $badge_class; ?>">
                                    <?php 
                                    if ($percentage >= 90) echo '🌟 Excellent';
                                    elseif ($percentage >= 70) echo '✅ Good';
                                    elseif ($percentage >= 50) echo '📝 Average';
                                    else echo '📉 Needs Improvement';
                                    ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($assignment['feedback'])): ?>
                            <div class="feedback-section">
                                <h4>💬 Instructor Feedback:</h4>
                                <div class="feedback-content">
                                    <?php echo nl2br(htmlspecialchars($assignment['feedback'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>No Grades Yet</h3>
                    <p>Your graded assignments will appear here once your instructor reviews your submissions.</p>
                </div>
            <?php endif; ?>
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
