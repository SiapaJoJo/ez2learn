<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../module-usermanagement/login.php');
    exit();
}

$user_role = strtolower($_SESSION['role'] ?? 'student');
$dashboard_url = '';
$assignments_url = '';
$grades_url = '';

if ($user_role == 'admin') {
    $dashboard_url = '../module-usermanagement/dashboard-admin.php';
    $assignments_url = '#';
    $grades_url = '#';
} elseif ($user_role == 'staff') {
    $dashboard_url = '../module-usermanagement/dashboard-staff.php';
    $assignments_url = '../module-assignments/staff-assignments.php';
    $grades_url = '#';
} else {
    $dashboard_url = '../module-usermanagement/dashboard-student.php';
    $assignments_url = '../module-assignments/student-assignments.php';
    $grades_url = '../module-assignments/student-grades.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ez2Learn - Teacher Materials Management</title>
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

        /* MAIN */
        .ez-main {
            padding: 30px 30px 35px;
            background: #f5f7fb;
        }
        .ez-main h2 {
            text-align: center;
            font-weight: 700;
            margin-bottom: 30px;
            color: #1e3a5f;
        }

        /* TOOLBAR */
        .ez-toolbar {
            max-width: 1000px;
            margin: 0 auto 16px auto;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
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
            flex: 0 1 170px;
        }
        .ez-filter-select select {
            width: 100%;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            padding: 7px 14px;
            font-size: 13px;
            background: #f9fafb;
        }
        .ez-add-btn {
            flex: 0 0 auto;
            border-radius: 999px !important;
            padding-inline: 24px;
        }

        /* TABLE */
        .ez-table-wrapper {
            max-width: 1000px; margin: 0 auto;
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
        .view-btn   { background: #2563eb; color: #fff; border-color: #2563eb; }
        .edit-btn   { background: #facc15; color: #111827; border-color: #facc15; }
        .delete-btn { background: #f97373; color: #fff; border-color: #f97373; }

        /* MODAL */
        .modal-backdrop-custom {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .modal-box {
            background: white;
            width: 520px;
            max-width: 95%;
            border-radius: 12px;
            padding: 20px 22px;
            box-shadow: 0 20px 40px rgba(15,23,42,0.4);
        }
        .modal-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .modal-header-custom h5 { margin: 0; }
        .modal-close {
            border: none;
            background: transparent;
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
        }
        .form-label { font-size: 13px; }

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
                    <?php if ($user_role == 'staff'): ?>
                        <li><a href="#">Students</a></li>
                        <li><a href="<?php echo $assignments_url; ?>">Assignments</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $assignments_url; ?>">Assignments</a></li>
                        <li><a href="<?php echo $grades_url; ?>">Grades</a></li>
                    <?php endif; ?>
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

    <!-- ========== MAIN CONTENT ========== -->
    <main class="ez-main">
        <h2>Assigned Materials (ULE4625) – Teacher View</h2>

        <!-- Toolbar -->
        <div class="ez-toolbar">
            <div class="ez-search">
                <span>🔍</span>
                <input id="searchInput" type="text" placeholder="Search Materials by Name">
            </div>

            <div class="ez-filter-select">
                <select id="filterFileType">
                    <option value="all">All File Types</option>
                    <option value="pdf">PDF</option>
                    <option value="link">Link</option>
                    <option value="pdf,link">PDF + Link</option>
                    <option value="none">Not Assigned</option>
                </select>
            </div>

            <button id="addMaterialBtn" class="btn btn-success ez-add-btn">
                Add
            </button>
        </div>

        <!-- Table -->
        <div class="ez-table-wrapper">
            <table id="materialsTable">
                <thead>
                    <tr style="background:#fde047;">
                        <th>Materials</th>
                        <th>Assigned File</th>
                        <th>Date</th>
                        <th style="width:240px">Actions</th>
                    </tr>
                </thead>
                <tbody id="materialsBody">
                    <!-- rows created by JS -->
                </tbody>
            </table>
        </div>
    </main>
        </div>
    </div>

<!-- ========== ADD / EDIT MODAL ========== -->
<div id="materialModal" class="modal-backdrop-custom">
    <div class="modal-box">
        <div class="modal-header-custom">
            <h5 id="modalTitle">Add Material</h5>
            <button class="modal-close" onclick="closeMaterialModal()">&times;</button>
        </div>
        <form id="materialForm">
            <input type="hidden" id="materialId">

            <div class="mb-2">
                <label class="form-label">Course Title</label>
                <input type="text" id="courseTitle" class="form-control" required>
            </div>

            <div class="mb-2">
                <label class="form-label">Material Link (URL)</label>
                <input type="url" id="materialLink" class="form-control" placeholder="https://...">
            </div>

            <div class="mb-2">
                <label class="form-label">Attach PDF</label>
                <input type="file" id="materialPdf" class="form-control" accept="application/pdf">
                <small class="text-muted">For demo, only the PDF file name is stored.</small>
            </div>

            <div class="mb-2">
                <label class="form-label">Teachers</label>
                <select id="teachers" class="form-select" multiple>
                    <option value="Dr. Ahmad">Dr. Ahmad</option>
                    <option value="Pn. Siti">Pn. Siti</option>
                    <option value="Mr. Lee">Mr. Lee</option>
                    <option value="Dr. Noraini">Dr. Noraini</option>
                </select>
                <small class="text-muted">Hold CTRL (Windows) / CMD (Mac) to select multiple.</small>
            </div>

            <div class="mb-2">
                <label class="form-label">Date</label>
                <input type="date" id="materialDate" class="form-control">
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="closeMaterialModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Material</button>
            </div>
        </form>
    </div>
</div>

<!-- ========== JS (no backend, all in-memory) ========== -->
<script>
    let materials = [
        {
            id: 1,
            title: "Marriage (Nikah) in Islam",
            pdfName: "nikah-notes.pdf",
            link: "https://example.com/nikah-notes",
            teachers: ["Dr. Ahmad"],
            date: "2024-11-12"
        },
        {
            id: 2,
            title: "Rights and Responsibilities",
            pdfName: "",
            link: "https://example.com/rights-video",
            teachers: ["Pn. Siti", "Mr. Lee"],
            date: "2024-11-15"
        },
        {
            id: 3,
            title: "Divorce (Talaq)",
            pdfName: "",
            link: "",
            teachers: ["Dr. Noraini"],
            date: ""
        }
    ];

    let editingId = null;

    const materialsBody   = document.getElementById("materialsBody");
    const searchInput     = document.getElementById("searchInput");
    const filterFileType  = document.getElementById("filterFileType");
    const modal           = document.getElementById("materialModal");
    const modalTitle      = document.getElementById("modalTitle");
    const materialForm    = document.getElementById("materialForm");

    document.getElementById("addMaterialBtn").addEventListener("click", openAddMaterial);

    function renderTable() {
        const searchText = searchInput.value.toLowerCase();
        const fileFilter = filterFileType.value; // all, pdf, link, pdf,link, none

        materialsBody.innerHTML = "";

        materials.forEach(mat => {
            if (searchText && !mat.title.toLowerCase().includes(searchText)) return;

            const hasPdf  = !!mat.pdfName;
            const hasLink = !!mat.link;
            let typeString;
            if (hasPdf && hasLink) typeString = "pdf,link";
            else if (hasPdf)       typeString = "pdf";
            else if (hasLink)      typeString = "link";
            else                   typeString = "none";

            if (fileFilter !== "all" && fileFilter !== typeString) return;

            let assignedFile = "Not Assigned";
            if (hasPdf && hasLink) assignedFile = "PDF, Link";
            else if (hasPdf)       assignedFile = "PDF";
            else if (hasLink)      assignedFile = "Link";

            const dateDisplay = mat.date ? new Date(mat.date).toLocaleDateString("en-GB") : "-";

            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>${mat.title}</td>
                <td>${assignedFile}</td>
                <td>${dateDisplay}</td>
                <td>
                    <button class="btn-small view-btn" onclick="viewMaterial(${mat.id})">View</button>
                    <button class="btn-small edit-btn" onclick="openEditMaterial(${mat.id})">Edit</button>
                    <button class="btn-small delete-btn" onclick="deleteMaterial(${mat.id})">Delete</button>
                </td>
            `;
            materialsBody.appendChild(tr);
        });
    }

    function viewMaterial(id) {
        const mat = materials.find(m => m.id === id);
        if (!mat) return;
        alert(
            "Title: " + mat.title +
            "\nPDF: " + (mat.pdfName || "None") +
            "\nLink: " + (mat.link || "None") +
            "\nTeachers: " + (mat.teachers.join(", ") || "None") +
            "\nDate: " + (mat.date || "None")
        );
    }

    function openAddMaterial() {
        editingId = null;
        modalTitle.textContent = "Add Material";
        materialForm.reset();
        document.getElementById("materialId").value = "";
        clearTeacherSelection();
        document.getElementById("materialPdf").value = "";
        modal.style.display = "flex";
    }

    function openEditMaterial(id) {
        const mat = materials.find(m => m.id === id);
        if (!mat) return;

        editingId = id;
        modalTitle.textContent = "Edit Material";
        document.getElementById("materialId").value = id;
        document.getElementById("courseTitle").value = mat.title;
        document.getElementById("materialLink").value = mat.link;
        document.getElementById("materialDate").value = mat.date || "";

        const teacherSelect = document.getElementById("teachers");
        [...teacherSelect.options].forEach(opt => {
            opt.selected = mat.teachers.includes(opt.value);
        });

        document.getElementById("materialPdf").value = "";
        modal.style.display = "flex";
    }

    function closeMaterialModal() {
        modal.style.display = "none";
    }

    function clearTeacherSelection() {
        const teacherSelect = document.getElementById("teachers");
        [...teacherSelect.options].forEach(opt => opt.selected = false);
    }

    materialForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const title = document.getElementById("courseTitle").value.trim();
        const link  = document.getElementById("materialLink").value.trim();
        const date  = document.getElementById("materialDate").value;

        const teacherSelect = document.getElementById("teachers");
        const teachers = [...teacherSelect.options]
            .filter(opt => opt.selected)
            .map(opt => opt.value);

        const pdfInput = document.getElementById("materialPdf");
        let pdfName = "";
        if (pdfInput.files.length > 0) {
            pdfName = pdfInput.files[0].name;
        }

        if (!title) {
            alert("Please enter course title.");
            return;
        }

        if (editingId) {
            const mat = materials.find(m => m.id === editingId);
            if (!mat) return;
            mat.title    = title;
            mat.link     = link;
            mat.date     = date;
            mat.teachers = teachers;
            if (pdfName) mat.pdfName = pdfName;
        } else {
            const newId = materials.length ? Math.max(...materials.map(m => m.id)) + 1 : 1;
            materials.push({
                id: newId,
                title,
                pdfName,
                link,
                teachers,
                date
            });
        }

        closeMaterialModal();
        renderTable();
    });

    function deleteMaterial(id) {
        if (!confirm("Are you sure you want to delete this material?")) return;
        materials = materials.filter(m => m.id !== id);
        renderTable();
    }

    searchInput.addEventListener("keyup", renderTable);
    filterFileType.addEventListener("change", renderTable);

    renderTable();
</script>

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
</body>
</html>
