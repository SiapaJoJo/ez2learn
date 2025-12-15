<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../module-usermanagement/login.php');
    exit();
}

$user_role = strtolower($_SESSION['role'] ?? 'student');
$dashboard_url = '';
if ($user_role == 'admin') {
    $dashboard_url = '../module-usermanagement/dashboard-admin.php';
} elseif ($user_role == 'staff') {
    $dashboard_url = '../module-usermanagement/dashboard-staff.php';
} else {
    $dashboard_url = '../module-usermanagement/dashboard-student.php';
}

$materials = [
    [
        'id'       => 1,
        'title'    => 'Chapter 1: Marriage (Nikah) in Islam',
        'type'     => 'pdf',
        'dateText' => '15 Nov, 9:30 PM',
        'link'     => 'https://example.com/nikah-chapter1.pdf',
    ],
    [
        'id'       => 2,
        'title'    => 'Quiz 2',
        'type'     => 'quiz',
        'dateText' => '30 min, 10:30 PM',
        'link'     => 'quiz.php',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ez2Learn - Topic Materials</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

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
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .ez-main {
            padding: 30px 30px 35px;
            background: #f5f7fb;
        }

        .course-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e3a5f;
        }
        .course-location {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 24px;
        }

        .material-card {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 16px 18px;
            margin-bottom: 12px;
            background: #ffffff;
        }
        .material-title {
            font-weight: 700;
            font-size: 15px;
        }
        .material-meta {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
            margin-bottom: 8px;
        }
        .material-type-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            display: inline-block;
            margin-bottom: 4px;
        }

        .pagination-ez {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
        }
        .pagination-ez span {
            width: 26px; height: 26px;
            border-radius: 999px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
        }
        .pagination-ez .active-page {
            background: #111827;
            color: white;
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

            .ez-main { padding: 25px 18px; }
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
                    <li><a href="../module-assignments/student-assignments.php">Assignments</a></li>
                    <li><a href="../module-assignments/student-grades.php">Grades</a></li>
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

    <main class="ez-main">
        <div class="course-header mb-4">
            <div class="course-title">ULE4625 Family System In Islam</div>
            <div class="course-location">Islamic Centre</div>
        </div>

        <div id="materialsList">
            <?php foreach ($materials as $item): ?>
                <div class="material-card">
                    <div class="material-type-badge">
                        <?php echo $item['type'] === 'quiz' ? 'Quiz' : 'Learning Material'; ?>
                    </div>
                    <div class="material-title"><?php echo htmlspecialchars($item['title']); ?></div>
                    <div class="material-meta"><?php echo htmlspecialchars($item['dateText']); ?></div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank"
                           class="btn btn-success btn-sm">
                            View
                        </a>

                        <?php if ($item['type'] !== 'quiz'): ?>
                            <button class="btn btn-outline-secondary btn-sm"
                                    onclick="alert('Saved: <?php echo htmlspecialchars($item['title']); ?>');">
                                Save
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pagination-ez">
            <span class="active-page">1</span>
            <span>2</span>
            <span>3</span>
            <span>...</span>
            <span>9</span>
            <span>10</span>
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
