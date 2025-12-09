<?php
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
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 30px 15px;
        }

        .page-container {
            background: #ffffff;
            width: 100%;
            max-width: 1000px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .ez-header {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            padding: 18px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .ez-brand { display: flex; align-items: center; gap: 15px; }
        .ez-logo-circle {
            width: 60px; height: 60px; border-radius: 50%;
            background: white; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        .ez-logo {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            border-radius: 12px; position: relative;
        }
        .ez-logo::before {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 26px; height: 20px;
            background:
                linear-gradient(180deg, #1e3a5f 0%, #1e3a5f 100%) 0 0,
                linear-gradient(180deg, #3198F8 0%, #3198F8 100%) 0 8px,
                linear-gradient(180deg, #e91e63 0%, #e91e63 100%) 0 16px;
            background-size: 18px 6px, 18px 6px, 18px 6px;
            background-repeat: no-repeat;
            background-position: center top, center center, center bottom;
            border-radius: 2px;
        }
        .ez-brand-text h1 {
            margin: 0; font-size: 26px; font-weight: 700; letter-spacing: -0.5px;
        }
        .ez-brand-text span { color: #fdd835; }
        .ez-brand-text small { font-size: 12px; display: block; opacity: 0.9; }

        .ez-nav {
            background: #f5f7fb;
            padding: 10px 20px 0;
        }
        .ez-nav-inner {
            background: #e0e7ff;
            padding: 4px;
            border-radius: 999px;
            display: inline-flex;
            gap: 4px;
        }
        .ez-nav button {
            border: none;
            background: transparent;
            color: #1e3a5f;
            font-size: 13px;
            padding: 6px 18px;
            border-radius: 999px;
            cursor: pointer;
        }
        .ez-nav .active {
            background: white;
            color: #1e6bb8;
            font-weight: 600;
            box-shadow: 0 3px 8px rgba(0,0,0,0.07);
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
            body { padding: 20px 10px; }
            .page-container { border-radius: 16px; }
            .ez-header { flex-direction: column; align-items: flex-start; padding: 20px; }
            .ez-main { padding: 25px 18px; }
        }
    </style>
</head>
<body>

<div class="page-container">

    <header class="ez-header">
        <div class="ez-brand">
            <div class="ez-logo-circle"><div class="ez-logo"></div></div>
            <div class="ez-brand-text">
                <h1>Ez2<span>Learn</span></h1>
                <small>UMPSA Learning System</small>
            </div>
        </div>
    </header>

    <nav class="ez-nav">
        <div class="ez-nav-inner">
            <button class="active" type="button">Materials</button>
            <button type="button">Assessments</button>
            <button type="button">Profile</button>
            <button type="button">Home</button>
        </div>
    </nav>

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
</script>

</body>
</html>
