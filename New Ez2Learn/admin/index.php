<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
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

// Get statistics
$stats = [];

// Total users
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$stats['total_users'] = mysqli_fetch_assoc($result)['count'];

// Active users
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE status = 'active'");
$stats['active_users'] = mysqli_fetch_assoc($result)['count'];

// Total courses
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM courses");
$stats['total_courses'] = mysqli_fetch_assoc($result)['count'];

// Open courses
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM courses WHERE status = 'open'");
$stats['open_courses'] = mysqli_fetch_assoc($result)['count'];

// Total students
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stats['total_students'] = mysqli_fetch_assoc($result)['count'];

// Total lecturers
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'lecturer'");
$stats['total_lecturers'] = mysqli_fetch_assoc($result)['count'];

// Total enrollments
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM enrollments");
$stats['total_enrollments'] = mysqli_fetch_assoc($result)['count'];

// Total assignments
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM assignments");
$stats['total_assignments'] = mysqli_fetch_assoc($result)['count'];

// Get user info
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['username'] ?? 'Admin';
$user_email = '';

if ($user_id) {
    $stmt = mysqli_prepare($conn, "SELECT name, email FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $user_name = $row['name'] ?? $user_name;
        $user_email = $row['email'] ?? '';
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Ez2Learn</title>
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

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .card p {
            color: #666;
            line-height: 1.6;
        }

        .stat-card {
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #3198F8;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .welcome-section h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }

        .welcome-section p {
            color: #666;
            font-size: 18px;
        }

        .username {
            color: #3198F8;
            font-weight: 600;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .action-card {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: transform 0.3s ease;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-3px);
        }

        .action-card h4 {
            margin-bottom: 5px;
            font-size: 18px;
        }

        .action-card p {
            font-size: 14px;
            opacity: 0.9;
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

            .dashboard-grid {
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
                    <li><a href="index.php" class="active">Dashboard</a></li>
                    <li><a href="module-usermanagement/index.php">Users</a></li>
                    <li><a href="module-managelearning/index.php">Courses</a></li>
                    <li><a href="module-assignments/index.php">Assignments</a></li>
                    <li><a href="module-progress/index.php">Progress</a></li>
                </ul>
                <div class="profile-dropdown" id="profileDropdown">
                    <button class="profile-btn" onclick="toggleDropdown()">
                        <div class="profile-icon"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                        <span>▼</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="../edit-profile.php">Edit Profile</a>
                        <a href="../logout.php" class="logout">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <h1>Welcome to Admin Dashboard!</h1>
            <p>You are logged in as <span class="username"><?php echo htmlspecialchars($user_name); ?></span> (Admin)</p>
        </div>

        <div class="dashboard-grid">
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['total_courses']; ?></div>
                <div class="stat-label">Total Courses</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['open_courses']; ?></div>
                <div class="stat-label">Open Courses</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['total_lecturers']; ?></div>
                <div class="stat-label">Total Lecturers</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['total_enrollments']; ?></div>
                <div class="stat-label">Total Enrollments</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['total_assignments']; ?></div>
                <div class="stat-label">Total Assignments</div>
            </div>
        </div>

        <div class="card">
            <h3>Quick Actions</h3>
            <div class="quick-actions">
                <a href="module-usermanagement/index.php" class="action-card">
                    <h4>Manage Users</h4>
                    <p>Create, edit, activate/deactivate users</p>
                </a>
                <a href="module-managelearning/index.php" class="action-card">
                    <h4>Manage Courses</h4>
                    <p>Create courses and assign lecturers</p>
                </a>
                <a href="module-assignments/index.php" class="action-card">
                    <h4>View Assignments</h4>
                    <p>Monitor all assignments in the system</p>
                </a>
                <a href="module-progress/index.php" class="action-card">
                    <h4>View Progress</h4>
                    <p>System-wide progress and reports</p>
                </a>
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

