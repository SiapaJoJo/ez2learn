<?php
$user_name = $_SESSION['name'] ?? 'Lecturer';
$user_email = $_SESSION['email'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];

$script_path = dirname($_SERVER['SCRIPT_NAME']);
if (strpos($script_path, '/lecturer/module-') !== false) {
    $base_path = '../';
    $image_path = '../../image/logo-ez2learn.png';
    $favicon_path = '../../image/favicon.ico';
} else {
    $base_path = '';
    $image_path = '../image/logo-ez2learn.png';
    $favicon_path = '../image/favicon.ico';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Lecturer - Ez2Learn</title>
    <link rel="icon" type="image/x-icon" href="<?php echo $favicon_path; ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo $favicon_path; ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            padding: 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-top {
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1600px;
            margin: 0 auto;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-menu {
            display: flex;
            gap: 0.5rem;
            list-style: none;
        }

        .nav-menu a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 0.625rem 1rem;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .nav-menu a.active {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            font-weight: 600;
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.875rem;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .profile-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .profile-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            overflow: hidden;
        }

        .profile-dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            color: #1e293b;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.875rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .dropdown-menu a:last-child {
            border-bottom: none;
        }

        .dropdown-menu a:hover {
            background: #f8fafc;
            color: #6366f1;
        }

        .dropdown-menu a.logout {
            color: #ef4444;
        }

        .dropdown-menu a.logout:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        .container {
            max-width: 1600px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        @media (max-width: 1024px) {
            .header-top {
                padding: 1rem 1.5rem;
            }

            .nav-menu {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .header-top {
                padding: 1rem;
                flex-wrap: wrap;
            }

            .logo-img {
                height: 32px;
            }

            .logo-text {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <a href="<?php echo $base_path; ?>index.php" style="text-decoration: none; color: white;">
                <div class="logo-section">
                    <img src="<?php echo $image_path; ?>" alt="Ez2Learn" class="logo-img">
                    <div class="logo-text">Ez2Learn</div>
                </div>
            </a>
            <div class="header-right">
                <ul class="nav-menu">
                    <li><a href="<?php echo $base_path; ?>index.php" class="<?php echo ($current_page === 'index.php' && $base_path === '') ? 'active' : ''; ?>">📊 Dashboard</a></li>
                    <li><a href="<?php echo $base_path; ?>module-managelearning/index.php" class="<?php echo strpos($current_path, 'module-managelearning') !== false ? 'active' : ''; ?>">📚 Materials</a></li>
                    <li><a href="<?php echo $base_path; ?>module-assignments/index.php" class="<?php echo strpos($current_path, 'module-assignments') !== false ? 'active' : ''; ?>">📝 Assignments</a></li>
                    <li><a href="<?php echo $base_path; ?>module-progress/index.php" class="<?php echo strpos($current_path, 'module-progress') !== false ? 'active' : ''; ?>">📈 Progress</a></li>
                    <li><a href="<?php echo $base_path; ?>module-usermanagement/index.php" class="<?php echo strpos($current_path, 'module-usermanagement') !== false ? 'active' : ''; ?>">👥 Students</a></li>
                </ul>
                <div class="profile-dropdown" id="profileDropdown">
                    <button class="profile-btn" onclick="toggleDropdown()">
                        <div class="profile-icon"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                        <span style="font-size: 0.75rem;">▼</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="<?php echo $base_path === '' ? '../' : '../../'; ?>edit-profile.php">👤 Edit Profile</a>
                        <a href="<?php echo $base_path === '' ? '../' : '../../'; ?>logout.php" class="logout">🚪 Logout</a>
                    </div>
                </div>
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

