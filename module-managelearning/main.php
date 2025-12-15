<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../module-usermanagement/login.php');
    exit();
}

$user_role = strtolower($_SESSION['role'] ?? 'student');
$dashboard_url = '';
$assignments_url = '';
$grades_url = '';

if ($user_role == 'admin') {
    $dashboard_url = '../module-usermanagement/dashboard-admin.php';
    $assignments_url = '#';
    $grades_url = '#';
} elseif ($user_role == 'staff') {
    $dashboard_url = '../module-usermanagement/dashboard-staff.php';
    $assignments_url = '../module-assignments/staff-assignments.php';
    $grades_url = '#';
} else {
    $dashboard_url = '../module-usermanagement/dashboard-student.php';
    $assignments_url = '../module-assignments/student-assignments.php';
    $grades_url = '../module-assignments/student-grades.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ez2Learn - Enrolled Courses</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

        .page-container {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ez-main {
            padding: 30px 30px 35px;
            background: #f5f7fb;
        }

        .ez-main-header {
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .ez-main-title {
            font-size: 22px;
            font-weight: 700;
            color: #1e3a5f;
        }

        .ez-main-subtitle {
            font-size: 13px;
            color: #6c757d;
        }

        .course-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .course-card {
            background: #ffffff;
            border-radius: 16px;
            width: calc(33.333% - 14px);
            min-width: 240px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
        }

        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px rgba(0, 0, 0, 0.12);
        }

        .course-card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        .course-body {
            padding: 14px 16px 16px;
        }

        .course-code {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #1e6bb8;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .course-title {
            font-weight: 600;
            font-size: 14px;
            color: #1e3a5f;
            margin-bottom: 4px;
        }

        .course-meta {
            font-size: 12px;
            color: #6c757d;
        }

        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background: #e8f3ff;
            color: #1e6bb8;
            margin-top: 6px;
        }

        @media (max-width: 900px) {
            .course-card {
                width: calc(50% - 10px);
            }
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

            .course-card {
                width: 100%;
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
                    <li><a href="<?php echo $dashboard_url; ?>">Dashboard</a></li>
                    <li><a href="main.php">My Courses</a></li>
                    <?php if ($user_role == 'staff'): ?>
                        <li><a href="#">Students</a></li>
                        <li><a href="<?php echo $assignments_url; ?>">Assignments</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $assignments_url; ?>">Assignments</a></li>
                        <li><a href="<?php echo $grades_url; ?>">Grades</a></li>
                    <?php endif; ?>
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
        <div class="page-container">

    <!-- ========== MAIN CONTENT ========== -->
    <main class="ez-main">
        <div class="ez-main-header">
            <div>
                <div class="ez-main-title">List of Enrolled Courses</div>
                <div class="ez-main-subtitle">
                    These are the courses you are currently enrolled in for this semester.
                </div>
            </div>
        </div>

        <div class="course-container">

            <!-- Course 1 -->
            <div class="course-card">
                <img src="https://images.pexels.com/photos/5380664/pexels-photo-5380664.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Cybersecurity">
                <div class="course-body">
                    <div class="course-code">BC12342</div>
                    <div class="course-title">CyberSecurity Ethique Computing</div>
                    <div class="course-meta">3 credit hours · Lecturer: Dr. Aisyah</div>
                    <span class="badge">Active</span>
                </div>
            </div>

            <!-- Course 2 -->
            <div class="course-card">
                <img src="https://images.pexels.com/photos/11623384/pexels-photo-11623384.jpeg?auto=compress&cs=tinysrgb&w=800" alt="German language">
                <div class="course-body">
                    <div class="course-code">ULE1272</div>
                    <div class="course-title">German Beginner Language</div>
                    <div class="course-meta">2 credit hours · Lecturer: Mr. Daniel</div>
                    <span class="badge">Active</span>
                </div>
            </div>

            <!-- Course 3 -->
            <div class="course-card">
                <img src="https://images.pexels.com/photos/8089095/pexels-photo-8089095.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Family System in Islam">
                <div class="course-body">
                    <div class="course-code">ULE4625</div>
                    <div class="course-title">Family System In Islam (Elective)</div>
                    <div class="course-meta">3 credit hours · Lecturer: Ustaz Hafiz</div>
                    <span class="badge">Active</span>
                </div>
            </div>

        </div>
    </main>
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
