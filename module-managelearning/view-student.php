<?php
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
</script>

</body>
</html>
