<?php
session_start();

// Check authentication and role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../module-usermanagement/login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'staff') {
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

$staff_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get assignment ID
if (!isset($_GET['id'])) {
    header('Location: staff-assignments.php');
    exit();
}

$assignment_id = (int)$_GET['id'];

// Fetch assignment and verify ownership
$stmt = mysqli_prepare($conn, "SELECT * FROM assignments WHERE id = ? AND created_by = ?");
mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $staff_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assignment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$assignment) {
    $_SESSION['error'] = 'Assignment not found or unauthorized access.';
    header('Location: staff-assignments.php');
    exit();
}

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'])) {
    $submission_id = (int)$_POST['submission_id'];
    $marks_awarded = isset($_POST['marks_awarded']) ? (int)$_POST['marks_awarded'] : null;
    $feedback = trim($_POST['feedback'] ?? '');
    
    // Validation
    if ($marks_awarded === null || $marks_awarded < 0) {
        $error = 'Please enter valid marks (0 or greater).';
    } elseif ($marks_awarded > $assignment['total_marks']) {
        $error = 'Marks awarded cannot exceed total marks (' . $assignment['total_marks'] . ').';
    } else {
        // Verify submission belongs to this assignment
        $verify_stmt = mysqli_prepare($conn, "
            SELECT s.id 
            FROM assignment_submissions s
            INNER JOIN assignments a ON s.assignment_id = a.id
            WHERE s.id = ? AND a.id = ? AND a.created_by = ?
        ");
        mysqli_stmt_bind_param($verify_stmt, "iii", $submission_id, $assignment_id, $staff_id);
        mysqli_stmt_execute($verify_stmt);
        $verify_result = mysqli_stmt_get_result($verify_stmt);
        
        if (mysqli_num_rows($verify_result) > 0) {
            // Update submission
            $update_stmt = mysqli_prepare($conn, "
                UPDATE assignment_submissions 
                SET marks_awarded = ?, feedback = ?, submission_status = 'graded'
                WHERE id = ?
            ");
            mysqli_stmt_bind_param($update_stmt, "isi", $marks_awarded, $feedback, $submission_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $success = 'Submission graded successfully!';
            } else {
                $error = 'Failed to grade submission. Please try again.';
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $error = 'Submission not found or unauthorized.';
        }
        mysqli_stmt_close($verify_stmt);
    }
}

// Fetch all submissions for this assignment
$submissions_stmt = mysqli_prepare($conn, "
    SELECT 
        s.*,
        u.username,
        u.first_name,
        u.last_name,
        u.email
    FROM assignment_submissions s
    INNER JOIN users u ON s.student_id = u.id
    WHERE s.assignment_id = ?
    ORDER BY s.submitted_at DESC
");
mysqli_stmt_bind_param($submissions_stmt, "i", $assignment_id);
mysqli_stmt_execute($submissions_stmt);
$result = mysqli_stmt_get_result($submissions_stmt);
$submissions = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($submissions_stmt);

// Calculate statistics
$total_submissions = count($submissions);
$graded_count = 0;
$pending_count = 0;
$average_marks = 0;
$total_marks_sum = 0;

foreach ($submissions as $sub) {
    if ($sub['submission_status'] === 'graded') {
        $graded_count++;
        $total_marks_sum += $sub['marks_awarded'];
    } else {
        $pending_count++;
    }
}

if ($graded_count > 0) {
    $average_marks = round($total_marks_sum / $graded_count, 2);
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions - <?php echo htmlspecialchars($assignment['title']); ?> - Ez2Learn</title>
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

        .assignment-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .assignment-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 15px;
        }

        .assignment-meta {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }

        .statistics {
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
            color: #3198F8;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
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

        .submissions-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .submissions-section h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 25px;
        }

        .submission-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .submission-card:hover {
            border-color: #3198F8;
            box-shadow: 0 4px 15px rgba(49, 152, 248, 0.2);
        }

        .submission-card.graded {
            border-color: #4CAF50;
            background: #f1f8f4;
        }

        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .student-info h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .student-info p {
            color: #666;
            font-size: 14px;
        }

        .submission-status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-submitted {
            background: #fff3cd;
            color: #856404;
        }

        .status-graded {
            background: #d4edda;
            color: #155724;
        }

        .submission-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .detail-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }

        .detail-value {
            font-size: 14px;
            color: #333;
        }

        .file-download {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #3198F8;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 16px;
            border: 2px solid #3198F8;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .file-download:hover {
            background: #3198F8;
            color: white;
        }

        .grading-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px dashed #e0e0e0;
        }

        .grading-form h4 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3198F8;
            box-shadow: 0 0 0 4px rgba(49, 152, 248, 0.1);
        }

        .form-group small {
            display: block;
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 15px;
        }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
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

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .graded-info {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .graded-info h4 {
            color: #2e7d32;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .marks-display {
            font-size: 24px;
            font-weight: bold;
            color: #2e7d32;
            margin-bottom: 10px;
        }

        .feedback-display {
            color: #333;
            line-height: 1.6;
            padding: 10px;
            background: white;
            border-radius: 6px;
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

            .assignment-meta {
                flex-direction: column;
                gap: 10px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .submission-header {
                flex-direction: column;
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
                    <li><a href="../module-usermanagement/dashboard-staff.php">Dashboard</a></li>
                    <li><a href="../module-managelearning/main.php">My Courses</a></li>
                    <li><a href="#">Students</a></li>
                    <li><a href="staff-assignments.php">Assignments</a></li>
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

        <div class="assignment-header">
            <h1><?php echo htmlspecialchars($assignment['title']); ?></h1>
            <div class="assignment-meta">
                <div class="meta-item">
                    <span>📅</span>
                    <span>Due: <?php echo date('M d, Y H:i', strtotime($assignment['due_date'])); ?></span>
                </div>
                <div class="meta-item">
                    <span>📊</span>
                    <span>Total Marks: <?php echo htmlspecialchars($assignment['total_marks']); ?></span>
                </div>
                <div class="meta-item">
                    <span>📌</span>
                    <span>Status: <?php echo ucfirst(htmlspecialchars($assignment['status'])); ?></span>
                </div>
            </div>
        </div>

        <div class="statistics">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_submissions; ?></div>
                <div class="stat-label">Total Submissions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $graded_count; ?></div>
                <div class="stat-label">Graded</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $pending_count; ?></div>
                <div class="stat-label">Pending Grading</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $graded_count > 0 ? $average_marks . '/' . $assignment['total_marks'] : 'N/A'; ?></div>
                <div class="stat-label">Average Marks</div>
            </div>
        </div>

        <div class="submissions-section">
            <h2>Student Submissions</h2>

            <?php if (empty($submissions)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>No Submissions Yet</h3>
                    <p>Students haven't submitted any assignments yet. Check back later!</p>
                </div>
            <?php else: ?>
                <?php foreach ($submissions as $submission): ?>
                    <div class="submission-card <?php echo $submission['submission_status'] === 'graded' ? 'graded' : ''; ?>">
                        <div class="submission-header">
                            <div class="student-info">
                                <h3>
                                    <?php 
                                    $student_name = trim(($submission['first_name'] ?? '') . ' ' . ($submission['last_name'] ?? ''));
                                    echo htmlspecialchars($student_name ?: $submission['username']); 
                                    ?>
                                </h3>
                                <p>@<?php echo htmlspecialchars($submission['username']); ?> • <?php echo htmlspecialchars($submission['email']); ?></p>
                            </div>
                            <span class="submission-status status-<?php echo $submission['submission_status']; ?>">
                                <?php echo ucfirst($submission['submission_status']); ?>
                            </span>
                        </div>

                        <div class="submission-details">
                            <div class="detail-item">
                                <span class="detail-label">Submitted At</span>
                                <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($submission['submitted_at'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Time Status</span>
                                <span class="detail-value" style="color: <?php echo strtotime($submission['submitted_at']) <= strtotime($assignment['due_date']) ? '#4CAF50' : '#f44336'; ?>;">
                                    <?php 
                                    if (strtotime($submission['submitted_at']) <= strtotime($assignment['due_date'])) {
                                        echo '✓ On Time';
                                    } else {
                                        $diff = strtotime($submission['submitted_at']) - strtotime($assignment['due_date']);
                                        $hours = floor($diff / 3600);
                                        $minutes = floor(($diff % 3600) / 60);
                                        echo '⚠ Late (' . $hours . 'h ' . $minutes . 'm)';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Submission File</span>
                                <a href="download-file.php?type=submission&id=<?php echo $submission['id']; ?>" class="file-download">
                                    📎 Download File
                                </a>
                            </div>
                        </div>

                        <?php if ($submission['submission_status'] === 'graded'): ?>
                            <div class="graded-info">
                                <h4>Grading Information</h4>
                                <div class="marks-display">
                                    <?php echo $submission['marks_awarded']; ?> / <?php echo $assignment['total_marks']; ?> marks
                                    (<?php echo round(($submission['marks_awarded'] / $assignment['total_marks']) * 100, 1); ?>%)
                                </div>
                                <?php if (!empty($submission['feedback'])): ?>
                                    <div class="detail-label">Feedback:</div>
                                    <div class="feedback-display">
                                        <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="grading-form">
                                <h4>Grade This Submission</h4>
                                <form method="POST" action="">
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="marks_<?php echo $submission['id']; ?>">Marks Awarded *</label>
                                            <input 
                                                type="number" 
                                                id="marks_<?php echo $submission['id']; ?>" 
                                                name="marks_awarded" 
                                                min="0" 
                                                max="<?php echo $assignment['total_marks']; ?>"
                                                required
                                                placeholder="0"
                                            >
                                            <small>Max: <?php echo $assignment['total_marks']; ?> marks</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="feedback_<?php echo $submission['id']; ?>">Feedback (Optional)</label>
                                            <textarea 
                                                id="feedback_<?php echo $submission['id']; ?>" 
                                                name="feedback" 
                                                placeholder="Provide constructive feedback to help the student improve..."
                                            ></textarea>
                                            <small>Help students understand their performance</small>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Submit Grade</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
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
