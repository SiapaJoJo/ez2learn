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

        /* HEADER (match main page) */
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
        .ez-brand-text h1 span { color: #fdd835; }
        .ez-brand-text small {
            font-size: 12px;
            display: block;
            opacity: 0.9;
        }

        /* NAV (same chip style) */
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
            body { padding: 20px 10px; }
            .page-container { border-radius: 16px; }
            .ez-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }
            .ez-main { padding: 25px 18px; }
        }
    </style>
</head>
<body>

<div class="page-container">

    <!-- ========== HEADER ========== -->
    <header class="ez-header">
        <div class="ez-brand">
            <div class="ez-logo-circle"><div class="ez-logo"></div></div>
            <div class="ez-brand-text">
                <h1>Ez2<span>Learn</span></h1>
                <small>UMPSA Learning System</small>
            </div>
        </div>
    </header>

    <!-- ========== NAV ========== -->
    <nav class="ez-nav">
        <div class="ez-nav-inner">
            <button class="active" type="button">Materials</button>
            <button type="button">Assessments</button>
            <button type="button">Profile</button>
            <button type="button">Home</button>
        </div>
    </nav>

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

</body>
</html>
