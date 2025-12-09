<?php
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
        'link'     => 'quiz2.php', // link to your quiz page
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

        /* HEADER (same style) */
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

</body>
</html>
