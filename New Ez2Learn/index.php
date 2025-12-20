<?php
session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $role = strtolower($_SESSION['role'] ?? 'student');
    if ($role == 'admin') {
        header('Location: admin/index.php');
    } elseif ($role == 'lecturer') {
        header('Location: lecturer/index.php');
    } else {
        header('Location: student/index.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ez2Learn - Your Learning Management System</title>
    <link rel="icon" type="image/x-icon" href="image/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="image/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            width: 600px;
            height: 600px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -300px;
            right: -300px;
            filter: blur(100px);
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            bottom: -250px;
            left: -250px;
            filter: blur(100px);
            z-index: 0;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1rem 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .nav-logo img {
            height: 40px;
            width: auto;
        }

        .nav-logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn-nav {
            padding: 0.625rem 1.5rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
        }

        .btn-nav-outline {
            color: #667eea;
            border-color: #667eea;
            background: transparent;
        }

        .btn-nav-outline:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-nav-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 14px 0 rgba(102, 126, 234, 0.39);
        }

        .btn-nav-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .hero {
            position: relative;
            z-index: 1;
            padding: 8rem 2rem 4rem;
            text-align: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero-logo {
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease-out;
        }

        .hero-logo img {
            height: 120px;
            width: auto;
            margin-bottom: 1.5rem;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
            line-height: 1.2;
            animation: fadeInUp 0.8s ease-out 0.2s both;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2.5rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        .btn-hero {
            padding: 1rem 2.5rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-hero-primary {
            background: white;
            color: #667eea;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.3);
        }

        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .features {
            position: relative;
            z-index: 1;
            padding: 5rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .features-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .features-subtitle {
            text-align: center;
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 3rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 14px rgba(102, 126, 234, 0.4);
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.75rem;
        }

        .feature-description {
            font-size: 1rem;
            color: #64748b;
            line-height: 1.6;
        }

        .footer {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 2rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 4rem;
        }

        .footer-text {
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }

            .nav-logo-text {
                font-size: 1.25rem;
            }

            .nav-buttons {
                gap: 0.5rem;
            }

            .btn-nav {
                padding: 0.5rem 1rem;
                font-size: 0.75rem;
            }

            .hero {
                padding: 6rem 1rem 3rem;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.125rem;
            }

            .btn-hero {
                padding: 0.875rem 2rem;
                font-size: 0.875rem;
            }

            .features {
                padding: 3rem 1rem;
            }

            .features-title {
                font-size: 2rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .feature-card {
                padding: 2rem;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2rem;
            }

            .hero-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn-hero {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-logo">
            <img src="image/logo-ez2learn.png" alt="Ez2Learn">
            <span class="nav-logo-text">Ez2Learn</span>
        </a>
        <div class="nav-buttons">
            <a href="login.php" class="btn-nav btn-nav-outline">Login</a>
            <a href="register.php" class="btn-nav btn-nav-primary">Sign Up</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-logo">
            <img src="image/logo-ez2learn.png" alt="Ez2Learn">
        </div>
        <h1 class="hero-title">Welcome to Ez2Learn</h1>
        <p class="hero-subtitle">Your comprehensive Learning Management System designed to make education accessible, engaging, and effective for everyone.</p>
        <div class="hero-buttons">
            <a href="register.php" class="btn-hero btn-hero-primary">Get Started</a>
            <a href="login.php" class="btn-hero btn-hero-secondary">Login</a>
        </div>
    </section>

    <section class="features">
        <h2 class="features-title">Why Choose Ez2Learn?</h2>
        <p class="features-subtitle">Experience a modern learning platform built for students, lecturers, and administrators</p>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📚</div>
                <h3 class="feature-title">Comprehensive Course Management</h3>
                <p class="feature-description">Organize and manage your courses with ease. Access learning materials, assignments, and quizzes all in one place.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📝</div>
                <h3 class="feature-title">Assignment & Submission System</h3>
                <p class="feature-description">Submit assignments, track deadlines, and receive feedback from lecturers. Everything you need for academic success.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3 class="feature-title">Interactive Quizzes</h3>
                <p class="feature-description">Test your knowledge with interactive quizzes. Get instant results and track your progress over time.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3 class="feature-title">Progress Tracking</h3>
                <p class="feature-description">Monitor your learning journey with detailed progress reports. See how far you've come and what's next.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3 class="feature-title">User Management</h3>
                <p class="feature-description">Efficient user management for administrators. Manage students, lecturers, and course enrollments seamlessly.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">💼</div>
                <h3 class="feature-title">Role-Based Access</h3>
                <p class="feature-description">Tailored dashboards for students, lecturers, and administrators. Each role gets the tools they need.</p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <p class="footer-text">&copy; <?php echo date('Y'); ?> Ez2Learn. All rights reserved.</p>
    </footer>
</body>
</html>

