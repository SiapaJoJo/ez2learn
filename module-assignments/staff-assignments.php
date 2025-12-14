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

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $assignment_id = (int)$_GET['id'];
    
    // Verify assignment belongs to this staff
    $stmt = mysqli_prepare($conn, "SELECT id FROM assignments WHERE id = ? AND created_by = ?");
    mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $staff_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $delete_stmt = mysqli_prepare($conn, "DELETE FROM assignments WHERE id = ?");
        mysqli_stmt_bind_param($delete_stmt, "i", $assignment_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $success = 'Assignment deleted successfully!';
        } else {
            $error = 'Failed to delete assignment.';
        }
        mysqli_stmt_close($delete_stmt);
    } else {
        $error = 'Assignment not found or unauthorized.';
    }
    mysqli_stmt_close($stmt);
}

// Handle status change (publish/close)
if (isset($_GET['action']) && in_array($_GET['action'], ['publish', 'close', 'draft']) && isset($_GET['id'])) {
    $assignment_id = (int)$_GET['id'];
    $new_status = $_GET['action'] === 'publish' ? 'published' : ($_GET['action'] === 'close' ? 'closed' : 'draft');
    
    // Verify assignment belongs to this staff
    $stmt = mysqli_prepare($conn, "SELECT id FROM assignments WHERE id = ? AND created_by = ?");
    mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $staff_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $update_stmt = mysqli_prepare($conn, "UPDATE assignments SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $assignment_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success = 'Assignment status updated successfully!';
        } else {
            $error = 'Failed to update assignment status.';
        }
        mysqli_stmt_close($update_stmt);
    } else {
        $error = 'Assignment not found or unauthorized.';
    }
    mysqli_stmt_close($stmt);
}

// Fetch all assignments created by this staff
$stmt = mysqli_prepare($conn, "
    SELECT a.*, 
           COUNT(DISTINCT s.id) as submission_count,
           COUNT(DISTINCT CASE WHEN s.submission_status = 'graded' THEN s.id END) as graded_count
    FROM assignments a
    LEFT JOIN assignment_submissions s ON a.id = s.assignment_id
    WHERE a.created_by = ?
    GROUP BY a.id
    ORDER BY a.created_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $staff_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assignments = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - Staff - Ez2Learn</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h1 {
            color: #333;
            font-size: 28px;
        }

        .page-header p {
            color: #666;
            margin-top: 5px;
        }

        .btn {
            padding: 12px 24px;
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

        .assignments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .assignment-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
        }

        .assignment-card:hover {
            transform: translateY(-5px);
        }

        .assignment-status {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-draft {
            background: #e0e0e0;
            color: #666;
        }

        .status-published {
            background: #e8f5e9;
            color: #388e3c;
        }

        .status-closed {
            background: #ffebee;
            color: #c62828;
        }

        .assignment-card h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 10px;
            margin-right: 100px;
        }

        .assignment-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 15px 0;
            font-size: 14px;
            color: #666;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meta-icon {
            width: 20px;
            text-align: center;
        }

        .assignment-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .assignment-stats {
            display: flex;
            gap: 20px;
            padding: 15px 0;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
            margin: 15px 0;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #3198F8;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
        }

        .assignment-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view {
            background: #2196F3;
            color: white;
        }

        .btn-view:hover {
            background: #1976D2;
        }

        .btn-edit {
            background: #FF9800;
            color: white;
        }

        .btn-edit:hover {
            background: #F57C00;
        }

        .btn-publish {
            background: #4CAF50;
            color: white;
        }

        .btn-publish:hover {
            background: #388E3C;
        }

        .btn-close {
            background: #f44336;
            color: white;
        }

        .btn-close:hover {
            background: #d32f2f;
        }

        .btn-draft {
            background: #9E9E9E;
            color: white;
        }

        .btn-draft:hover {
            background: #757575;
        }

        .btn-delete {
            background: #f44336;
            color: white;
        }

        .btn-delete:hover {
            background: #d32f2f;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 20px;
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

            .page-header {
                text-align: center;
            }

            .assignments-grid {
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
                    <li><a href="../dashboard-staff.php">Dashboard</a></li>
                    <li><a href="staff-assignments.php" class="active">Assignments</a></li>
                    <li><a href="#">My Courses</a></li>
                    <li><a href="#">Students</a></li>
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
            <div>
                <h1>My Assignments</h1>
                <p>Create, manage and grade student assignments</p>
            </div>
            <a href="create-assignment.php" class="btn btn-primary">+ Create New Assignment</a>
        </div>

        <?php if (empty($assignments)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📝</div>
                <h3>No Assignments Yet</h3>
                <p>You haven't created any assignments. Click the button below to create your first assignment.</p>
                <a href="create-assignment.php" class="btn btn-primary">Create Your First Assignment</a>
            </div>
        <?php else: ?>
            <div class="assignments-grid">
                <?php foreach ($assignments as $assignment): ?>
                    <?php
                    $status_class = 'status-' . $assignment['status'];
                    $is_overdue = strtotime($assignment['due_date']) < time();
                    ?>
                    <div class="assignment-card">
                        <span class="assignment-status <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($assignment['status']); ?>
                        </span>
                        
                        <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                        
                        <div class="assignment-meta">
                            <div class="meta-item">
                                <span class="meta-icon">📅</span>
                                <span>Due: <?php echo date('M d, Y H:i', strtotime($assignment['due_date'])); ?></span>
                                <?php if ($is_overdue && $assignment['status'] === 'published'): ?>
                                    <span style="color: #f44336; font-weight: 600;">(Overdue)</span>
                                <?php endif; ?>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon">📊</span>
                                <span>Total Marks: <?php echo htmlspecialchars($assignment['total_marks']); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon">📆</span>
                                <span>Created: <?php echo date('M d, Y', strtotime($assignment['created_at'])); ?></span>
                            </div>
                        </div>

                        <div class="assignment-description">
                            <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                        </div>

                        <div class="assignment-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $assignment['submission_count']; ?></div>
                                <div class="stat-label">Submissions</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $assignment['graded_count']; ?></div>
                                <div class="stat-label">Graded</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">
                                    <?php 
                                    $pending = $assignment['submission_count'] - $assignment['graded_count'];
                                    echo $pending;
                                    ?>
                                </div>
                                <div class="stat-label">Pending</div>
                            </div>
                        </div>

                        <div class="assignment-actions">
                            <a href="view-submissions.php?id=<?php echo $assignment['id']; ?>" class="btn-small btn-view">
                                View Submissions
                            </a>
                            <a href="edit-assignment.php?id=<?php echo $assignment['id']; ?>" class="btn-small btn-edit">
                                Edit
                            </a>
                            
                            <?php if ($assignment['status'] === 'draft'): ?>
                                <a href="?action=publish&id=<?php echo $assignment['id']; ?>" 
                                   class="btn-small btn-publish"
                                   onclick="return confirm('Publish this assignment? Students will be able to view and submit.');">
                                    Publish
                                </a>
                            <?php elseif ($assignment['status'] === 'published'): ?>
                                <a href="?action=close&id=<?php echo $assignment['id']; ?>" 
                                   class="btn-small btn-close"
                                   onclick="return confirm('Close this assignment? No more submissions will be accepted.');">
                                    Close
                                </a>
                            <?php elseif ($assignment['status'] === 'closed'): ?>
                                <a href="?action=publish&id=<?php echo $assignment['id']; ?>" 
                                   class="btn-small btn-publish"
                                   onclick="return confirm('Re-open this assignment?');">
                                    Re-open
                                </a>
                            <?php endif; ?>
                            
                            <a href="?action=delete&id=<?php echo $assignment['id']; ?>" 
                               class="btn-small btn-delete"
                               onclick="return confirm('Are you sure you want to delete this assignment? This will also delete all submissions.');">
                                Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
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
