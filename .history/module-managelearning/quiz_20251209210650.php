<?php
// Simple PHP variables so page is "MyPHP", no database needed.
$courseCode     = 'ULE4625';
$courseTitle    = 'Family System In Islam';
$quizTitle      = 'Quiz 2';
$durationMinutes = 30; // quiz duration
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ez2Learn - <?php echo htmlspecialchars($quizTitle); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            background: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
        }

        /* HEADER */
        .ez-header {
            background: #0d6efd;
            color: white;
            padding: 12px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .ez-brand { display: flex; align-items: center; gap: 10px; }
        .ez-logo-circle {
            width: 50px; height: 50px; border-radius: 50%;
            background: white; display: flex; align-items: center; justify-content: center;
        }
        .ez-logo {
            width: 35px; height: 35px; background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            border-radius: 10px; position: relative;
        }
        .ez-logo::before, .ez-logo::after {
            content: ""; position: absolute; left: 6px; right: 6px;
            height: 6px; background: white; border-radius: 4px;
        }
        .ez-logo::before { top: 10px; }
        .ez-logo::after  { top: 20px; }
        .ez-brand-text h1 { margin: 0; font-size: 26px; font-weight: 700; }
        .ez-brand-text span { color: #fdd835; }
        .ez-brand-text small { font-size: 12px; display: block; }

        .ez-nav {
            background: #0a58ca; padding: 4px;
            border-radius: 999px; display: flex; gap: 4px;
        }
        .ez-nav button {
            border: none; background: transparent; color: white;
            font-size: 13px; padding: 6px 18px; border-radius: 999px;
            cursor: pointer;
        }
        .ez-nav .active { background: white; color: #0a58ca; font-weight: 600; }

        /* MAIN */
        .ez-main {
            padding: 40px 60px;
            max-width: 950px;
            margin: 0 auto;
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
        }
        .quiz-course-title {
            font-size: 18px;
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
            .ez-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }
            .ez-main {
                padding: 30px 16px;
            }
        }
    </style>
</head>
<body>

<!-- ========== HEADER ========== -->
<header class="ez-header">
    <div class="ez-brand">
        <div class="ez-logo-circle"><div class="ez-logo"></div></div>
        <div class="ez-brand-text">
            <h1>Ez2<span>Learn</span></h1>
            <small>UMPSA Learning System</small>
        </div>
    </div>

    <nav class="ez-nav">
        <button class="active">Materials</button>
        <button>Assessments</button>
        <button>Profile</button>
        <button>Home</button>
    </nav>
</header>

<!-- ========== MAIN CONTENT ========== -->
<main class="ez-main">
    <!-- Quiz Header -->
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

    <!-- Quiz Form -->
    <form id="quizForm">
        <!-- Question 1 -->
        <div class="question-card">
            <div class="question-title">QUESTION 1</div>
            <div class="question-text">Nikah in Islam?</div>
            <textarea class="form-control" name="q1" rows="3" placeholder="Answer"></textarea>
        </div>

        <!-- Question 2 -->
        <div class="question-card">
            <div class="question-title">QUESTION 2</div>
            <div class="question-text">Who is your Mahram?</div>

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

        <!-- Question 3 -->
        <div class="question-card">
            <div class="question-title">QUESTION 3</div>
            <div class="question-text">List out 5 Rukun Islam</div>
            <textarea class="form-control" name="q3" rows="3" placeholder="Answer"></textarea>
        </div>

        <div class="quiz-submit-wrapper">
            <button id="submitBtn" type="submit" class="btn btn-success px-4">Submit</button>
        </div>
    </form>
</main>

<!-- ========== COUNTDOWN SCRIPT ========== -->
<script>
    // Total quiz time in seconds (from PHP variable)
    let totalSeconds = <?php echo (int)$durationMinutes; ?> * 60;

    const timerDisplay = document.getElementById("timerDisplay");
    const quizForm = document.getElementById("quizForm");
    const submitBtn = document.getElementById("submitBtn");

    function formatTime(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        const mm = m.toString().padStart(2, "0");
        const ss = s.toString().padStart(2, "0");
        return mm + ":" + ss;
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
        // Disable all inputs & submit
        const inputs = quizForm.querySelectorAll("input, textarea, button");
        inputs.forEach(el => el.disabled = true);

        alert("Time is up! Your quiz has been locked. (Demo only)");
        // In real app: auto-submit to backend here.
    }

    const timerInterval = setInterval(updateTimer, 1000);
    updateTimer(); // initial display

    // Handle manual submit
    quizForm.addEventListener("submit", function (e) {
        e.preventDefault();

        clearInterval(timerInterval);
        submitBtn.disabled = true;

        alert("Quiz submitted successfully! (Demo only – no backend)");
    });
</script>

</body>
</html>
