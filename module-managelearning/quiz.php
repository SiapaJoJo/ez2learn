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

$courseCode      = 'ULE4625';
$courseTitle     = 'Family System In Islam';
$quizTitle       = 'Quiz 2';
$durationMinutes = 30;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ez2Learn - <?php echo htmlspecialchars($quizTitle); ?></title>
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

        .quiz-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 24px;
        }
        .quiz-course-code {
            font-size: 20px;
            font-weight: 700;
            color: #1e3a5f;
        }
        .quiz-course-title {
            font-size: 18px;
            color: #1f2933;
        }
        .quiz-meta {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .quiz-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .question-card {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 16px 18px;
            margin-bottom: 16px;
            background: #ffffff;
        }
        .question-title {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 6px;
        }
        .question-text {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .quiz-submit-wrapper {
            text-align: center;
            margin-top: 15px;
        }

        .timer-badge {
            padding: 4px 10px;
            border-radius: 999px;
            background: #ffffff;
            color: #0f172a;
            font-size: 14px;
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
        <div class="quiz-header">
            <div>
                <div class="quiz-course-code"><?php echo htmlspecialchars($courseCode); ?></div>
                <div class="quiz-course-title"><?php echo htmlspecialchars($courseTitle); ?></div>
            </div>

            <div class="quiz-meta">
                <span><?php echo htmlspecialchars($quizTitle); ?></span>
                <span>⏱ <span id="quizDuration"><?php echo (int)$durationMinutes; ?> minutes</span></span>
                <span class="timer-badge">
                    Time left: <span id="timerDisplay"><?php echo sprintf('%02d:00', (int)$durationMinutes); ?></span>
                </span>
            </div>
        </div>

        <form id="quizForm">
            <div class="question-card">
                <div class="question-title">QUESTION 1</div>
                <div class="question-text">Explain what Nikah is in Islam.</div>
                <textarea class="form-control" name="q1" rows="3" placeholder="Answer"></textarea>
            </div>

            <div class="question-card">
                <div class="question-title">QUESTION 2</div>
                <div class="question-text">Who is considered your Mahram?</div>

                <div class="row">
                    <div class="col-md-3 col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="q2" id="q2_father" value="Father">
                            <label class="form-check-label" for="q2_father">Father</label>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="q2" id="q2_niece" value="Niece">
                            <label class="form-check-label" for="q2_niece">Niece</label>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="q2" id="q2_cousin" value="Cousin">
                            <label class="form-check-label" for="q2_cousin">Cousin</label>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="q2" id="q2_grandfather" value="Grandfather">
                            <label class="form-check-label" for="q2_grandfather">Grandfather</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="question-card">
                <div class="question-title">QUESTION 3</div>
                <div class="question-text">List out the 5 Pillars of Islam (Rukun Islam).</div>
                <textarea class="form-control" name="q3" rows="3" placeholder="Answer"></textarea>
            </div>

            <div class="quiz-submit-wrapper">
                <button id="submitBtn" type="submit" class="btn btn-success px-4">Submit</button>
            </div>
        </form>
    </main>
</div>

<script>
    let totalSeconds = <?php echo (int)$durationMinutes; ?> * 60;

    const timerDisplay = document.getElementById("timerDisplay");
    const quizForm = document.getElementById("quizForm");
    const submitBtn = document.getElementById("submitBtn");

    function formatTime(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return m.toString().padStart(2, "0") + ":" + s.toString().padStart(2, "0");
    }

    function updateTimer() {
        timerDisplay.textContent = formatTime(totalSeconds);

        if (totalSeconds <= 0) {
            clearInterval(timerInterval);
            timeUp();
        } else {
            totalSeconds--;
        }
    }

    function timeUp() {
        const inputs = quizForm.querySelectorAll("input, textarea, button");
        inputs.forEach(el => el.disabled = true);
        alert("Time is up! Your quiz has been locked. (Demo only)");
    }

    const timerInterval = setInterval(updateTimer, 1000);
    updateTimer();

    quizForm.addEventListener("submit", function (e) {
        e.preventDefault();
        clearInterval(timerInterval);
        submitBtn.disabled = true;
        alert("Quiz submitted successfully! (Demo only – no backend)");
    });

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
