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
        'title'   => 'Marriage (Nikah) in Islam',
        'file'    => 'Notes, Quizzes',
        'date'    => '12 Nov 2024',
        'tags'    => 'notes,quizzes',
        'hasDate' => 1,
    ],
    [
        'title'   => 'Rights and Responsibilities',
        'file'    => 'Video, Notes',
        'date'    => '15 Nov 2024',
        'tags'    => 'video,notes',
        'hasDate' => 1,
    ],
    [
        'title'   => 'Divorce (Talaq)',
        'file'    => 'Not Assigned',
        'date'    => '-',
        'tags'    => 'not-assigned',
        'hasDate' => 0,
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ez2Learn - Assigned Materials</title>
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

        .ez-main { padding: 30px 30px 35px; background: #f5f7fb; }
        .ez-main h2 { text-align: center; font-weight: 700; margin-bottom: 30px; color: #1e3a5f; }

        .ez-toolbar {
            max-width: 900px;
            margin: 0 auto 16px auto;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .ez-search {
            flex: 1 1 260px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            padding: 7px 14px;
            display: flex;
            align-items: center;
            background: #f9fafb;
        }
        .ez-search span { margin-right: 8px; }
        .ez-search input {
            border: none;
            outline: none;
            flex: 1;
            background: transparent;
        }
        .ez-filter-select {
            flex: 0 1 190px;
        }
        .ez-filter-select select {
            width: 100%;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            padding: 7px 14px;
            font-size: 13px;
            background: #f9fafb;
        }

        .ez-table-wrapper {
            max-width: 900px; margin: 0 auto;
            border-radius: 12px; border: 1px solid #e5e7eb;
            overflow: hidden; background: white;
        }
        table { width: 100%; font-size: 14px; border-collapse: collapse; }
        thead { background: #f3f4f6; }
        thead th { padding: 10px 14px; font-weight: 600; }
        tbody td { padding: 9px 14px; border-top: 1px solid #e5e7eb; }

        .btn-small {
            border-radius: 999px; font-size: 12px;
            padding: 4px 14px; border: 1px solid #d1d5db;
        }
        .view-btn { background: #2563eb; color: white; border-color: #2563eb; }
        .share-btn { background: #e5e7eb; color: #111827; }

        .radio-cell { text-align: center; }
        .radio-cell input { width: 16px; height: 16px; }

        .share-modal-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); display: none;
            justify-content: center; align-items: center; z-index: 9999;
        }
        .share-modal {
            background: white; padding: 25px;
            width: 380px; border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        }
        .share-header { font-weight: bold; font-size: 18px; margin-bottom: 12px; }
        .share-option {
            border: 1px solid #e5e7eb; padding: 10px; border-radius: 8px;
            margin-bottom: 8px; cursor: pointer;
        }
        .close-btn {
            float: right; cursor: pointer; font-size: 20px;
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
        <h2>Assigned Materials (ULE4625)</h2>

        <div class="ez-toolbar">
            <div class="ez-search">
                <span>🔍</span>
                <input id="searchInput" type="text" placeholder="Search Materials by Name">
            </div>

            <div class="ez-filter-select">
                <select id="filterDate">
                    <option value="all">All Dates</option>
                    <option value="has-date">Has Date</option>
                    <option value="no-date">No Date</option>
                </select>
            </div>
        </div>

        <div class="ez-table-wrapper">
            <table id="materialsTable">
                <thead>
                    <tr>
                        <th>Materials</th>
                        <th>Assigned File</th>
                        <th>Date</th>
                        <th>Actions</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($materials as $mat): ?>
                        <tr
                            data-title="<?php echo htmlspecialchars($mat['title']); ?>"
                            data-tags="<?php echo htmlspecialchars($mat['tags']); ?>"
                            data-hasdate="<?php echo $mat['hasDate'] ? '1' : '0'; ?>"
                        >
                            <td><?php echo htmlspecialchars($mat['title']); ?></td>
                            <td><?php echo htmlspecialchars($mat['file']); ?></td>
                            <td><?php echo htmlspecialchars($mat['date']); ?></td>
                            <td>
                                <button class="btn-small view-btn">View</button>
                                <button class="btn-small share-btn"
                                        onclick="openShareModal('<?php echo htmlspecialchars($mat['title']); ?>')">
                                    Share
                                </button>
                            </td>
                            <td class="radio-cell"><input type="radio" name="selected"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<div id="shareModalBg" class="share-modal-bg">
    <div class="share-modal">
        <span class="close-btn" onclick="closeShareModal()">&times;</span>
        <div class="share-header">Share Material</div>
        <p id="shareMaterialName" class="text-muted mb-2"></p>

        <div class="share-option" onclick="copyLink()">
            🔗 Copy Link
        </div>
        <div class="share-option" onclick="shareEmail()">
            📧 Share via Email
        </div>
        <div class="share-option" onclick="shareFacebook()">
            📘 Share on Facebook
        </div>
    </div>
</div>

<script>
    const searchInput = document.getElementById("searchInput");
    const filterDate  = document.getElementById("filterDate");
    const tableRows   = document.querySelectorAll("#materialsTable tbody tr");

    function applyFilters() {
        const searchText = searchInput.value.toLowerCase();
        const dateFilter = filterDate.value;

        tableRows.forEach(row => {
            const title   = row.dataset.title.toLowerCase();
            const hasDate = row.dataset.hasdate;

            let visible = true;

            if (searchText && !title.includes(searchText)) visible = false;
            if (visible && dateFilter === "has-date" && hasDate !== "1") visible = false;
            if (visible && dateFilter === "no-date"  && hasDate !== "0") visible = false;

            row.style.display = visible ? "" : "none";
        });
    }

    searchInput.addEventListener("keyup", applyFilters);
    filterDate.addEventListener("change", applyFilters);

    let selectedMaterial = "";

    function openShareModal(materialName) {
        selectedMaterial = materialName;
        document.getElementById("shareMaterialName").innerText = materialName;
        document.getElementById("shareModalBg").style.display = "flex";
    }

    function closeShareModal() {
        document.getElementById("shareModalBg").style.display = "none";
    }

    function copyLink() {
        const link = "https://ez2learn.com/material/" + encodeURIComponent(selectedMaterial);
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(link);
            alert("Link copied:\n" + link);
        } else {
            alert("Link:\n" + link);
        }
    }

    function shareEmail() {
        const link    = "https://ez2learn.com/material/" + encodeURIComponent(selectedMaterial);
        const subject = encodeURIComponent("Sharing Material: " + selectedMaterial);
        const body    = encodeURIComponent("Hi, here is the material:\n\n" + link);
        window.location.href = `mailto:?subject=${subject}&body=${body}`;
    }

    function shareFacebook() {
        const url = encodeURIComponent("https://ez2learn.com/material/" + selectedMaterial);
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, "_blank");
    }

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
