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
            max-width: 1100px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* HEADER */
        .ez-header {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            padding: 18px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .ez-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .ez-logo-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .ez-logo {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            border-radius: 12px;
            position: relative;
        }

        .ez-logo::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 26px;
            height: 20px;
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
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .ez-brand-text h1 span {
            color: #fdd835;
        }

        .ez-brand-text small {
            display: block;
            font-size: 12px;
            opacity: 0.9;
        }

        .ez-user-info {
            text-align: right;
            font-size: 14px;
        }

        .ez-user-info strong {
            font-weight: 600;
        }

        /* NAV */
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
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.07);
        }

        /* MAIN */
        .ez-main {
            padding: 30px 30px 35px;
            background: #f5f7fb;
        }

        .ez-main-header {
            margin-bottom: 25px;
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
            .course-card { width: calc(50% - 10px); }
        }

        @media (max-width: 600px) {
            body { padding: 20px 10px; }
            .page-container { border-radius: 16px; }
            .ez-header { flex-direction: column; align-items: flex-start; }
            .course-card { width: 100%; }
        }
    </style>
</head>
<body>

<div class="page-container">

    <!-- HEADER -->
    <header class="ez-header">
        <div class="ez-brand">
            <div class="ez-logo-circle">
                <div class="ez-logo"></div>
            </div>
            <div class="ez-brand-text">
                <h1>Ez2<span>Learn</span></h1>
                <small>UMPSA Learning System</small>
            </div>
        </div>

        <div class="ez-user-info">
            <div>Logged in as <strong>Nur Faqihah</strong> (Student)</div>
        </div>
    </header>

    <!-- NAV -->
    <nav class="ez-nav">
        <div class="ez-nav-inner">
            <button class="active" type="button">Materials</button>
            <button type="button">Assessments</button>
            <button type="button">Profile</button>
            <button type="button">Home</button>
        </div>
    </nav>

    <!-- MAIN -->
    <main class="ez-main">
        <div class="ez-main-header">
            <div class="ez-main-title">List of Enrolled Courses</div>
            <div class="ez-main-subtitle">
                These are the courses you are currently enrolled in for this semester.
            </div>
        </div>

        <div class="course-container">

            <div class="course-card">
                <img src="https://images.pexels.com/photos/5380664/pexels-photo-5380664.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Cybersecurity">
                <div class="course-body">
                    <div class="course-code">BC12342</div>
                    <div class="course-title">CyberSecurity Ethique Computing</div>
                    <div class="course-meta">3 credit hours · Lecturer: Dr. Aisyah</div>
                    <span class="badge">Active</span>
                </div>
            </div>

            <div class="course-card">
                <img src="https://images.pexels.com/photos/11623384/pexels-photo-11623384.jpeg?auto=compress&cs=tinysrgb&w=800" alt="German">
                <div class="course-body">
                    <div class="course-code">ULE1272</div>
                    <div class="course-title">German Beginner Language</div>
                    <div class="course-meta">2 credit hours · Lecturer: Mr. Daniel</div>
                    <span class="badge">Active</span>
                </div>
            </div>

            <div class="course-card">
                <img src="https://images.pexels.com/photos/8089095/pexels-photo-8089095.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Islam">
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

</body>
</html>
