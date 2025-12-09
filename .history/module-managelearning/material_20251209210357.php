<?php
// Dummy data for this topic (PHP version, no database)
$materials = [
    [
        'id'         => 1,
        'title'      => 'Chapter 1: Marriage (Nikah) in Islam',
        'type'       => 'pdf', // pdf | slide | quiz
        'dateText'   => '15 Nov, 9:30 PM',
        'link'       => 'https://example.com/nikah-chapter1.pdf'
    ],
    [
        'id'         => 2,
        'title'      => 'Quiz 2',
        'type'       => 'quiz',
        'dateText'   => '30 min, 10:30 PM',
        'link'       => 'https://quiz.ez2learn.com/quiz2'
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ez2Learn - Topic Materials</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            background: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
        }

        /* HEADER (same style as other Ez2Learn pages) */
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

        /* MAIN CONTENT */
        .ez-main { padding: 40px 60px; max-width: 900px; margin: 0 auto; }

        .course-title {
            font-size: 20px;
            font-weight: 700;
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

    <!-- Course Header -->
    <div class="course-header mb-4">
        <div class="course-title">ULE4625 Family System In Islam</div>
        <div class="course-location">Islamic Centre</div>
    </div>

    <!-- Materials List -->
    <div id="materialsList">
        <?php foreach ($materials as $item): ?>
            <div class="material-card">
                <div class="material-type-badge">
                    <?php echo $item['type'] === 'quiz' ? 'Quiz' : 'Learning Material'; ?>
                </div>
                <div class="material-title">
                    <?php echo htmlspecialchars($item['title']); ?>
                </div>
                <div class="material-meta">
                    <?php echo htmlspecialchars($item['dateText']); ?>
                </div>
                <div class="d-flex gap-2">
                    <!-- View button just opens the link in a new tab -->
                    <a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank"
                       class="btn btn-success btn-sm">
                        View
                    </a>

                    <?php if ($item['type'] !== 'quiz'): ?>
                        <!-- Save button: just a dummy alert for now -->
                        <button class="btn btn-outline-secondary btn-sm"
                                onclick="alert('Saved: <?php echo htmlspecialchars($item['title']); ?>');">
                            Save
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination (dummy) -->
    <div class="pagination-ez">
        <span class="active-page">1</span>
        <span>2</span>
        <span>3</span>
        <span>...</span>
        <span>9</span>
        <span>10</span>
    </div>

</main>

</body>
</html>
