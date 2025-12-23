<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'lecturer') {
    header('Location: ../../login.php');
    exit();
}

require_once '../../includes/db-config.php';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$lecturer_id = $_SESSION['user_id'] ?? 0;
$course_id = (int)($_GET['course_id'] ?? 0);

$verify_stmt = mysqli_prepare($conn, "
    SELECT c.course_id, c.course_code, c.course_name 
    FROM courses c
    INNER JOIN course_lecturers cl ON c.course_id = cl.course_id
    WHERE c.course_id = ? AND cl.lecturer_id = ?
");
mysqli_stmt_bind_param($verify_stmt, "ii", $course_id, $lecturer_id);
mysqli_stmt_execute($verify_stmt);
$verify_result = mysqli_stmt_get_result($verify_stmt);
$course = mysqli_fetch_assoc($verify_result);
mysqli_stmt_close($verify_stmt);

if (!$course) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'upload') {
        $title = trim($_POST['title'] ?? '');
        $material_type = $_POST['material_type'] ?? 'pdf';
        $file_path = '';
        
        if (empty($title)) {
            $error = 'Title is required.';
        } else {
            if ($material_type === 'pdf' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/materials/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['file']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
                    $file_path = 'uploads/materials/' . $file_name;
                } else {
                    $error = 'Failed to upload file.';
                }
            } elseif ($material_type === 'link') {
                $file_path = trim($_POST['link'] ?? '');
                if (empty($file_path)) {
                    $error = 'Link is required for link type materials.';
                }
            }
            
            if (empty($error)) {
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO materials (course_id, lecturer_id, title, material_type, file_path)
                    VALUES (?, ?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($stmt, "iisss", $course_id, $lecturer_id, $title, $material_type, $file_path);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Material uploaded successfully!';
                } else {
                    $error = 'Failed to save material.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($action === 'delete') {
        $material_id = (int)($_POST['material_id'] ?? 0);
        
        if ($material_id > 0) {

            $get_stmt = mysqli_prepare($conn, "SELECT file_path FROM materials WHERE material_id = ? AND lecturer_id = ?");
            mysqli_stmt_bind_param($get_stmt, "ii", $material_id, $lecturer_id);
            mysqli_stmt_execute($get_stmt);
            $get_result = mysqli_stmt_get_result($get_stmt);
            $material = mysqli_fetch_assoc($get_result);
            mysqli_stmt_close($get_stmt);

            $delete_stmt = mysqli_prepare($conn, "DELETE FROM materials WHERE material_id = ? AND lecturer_id = ?");
            mysqli_stmt_bind_param($delete_stmt, "ii", $material_id, $lecturer_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {

                if ($material && !empty($material['file_path']) && file_exists('../../' . $material['file_path'])) {
                    unlink('../../' . $material['file_path']);
                }
                $success = 'Material deleted successfully!';
            } else {
                $error = 'Failed to delete material.';
            }
            mysqli_stmt_close($delete_stmt);
        }
    }
}

$materials_query = "
    SELECT material_id, title, material_type, file_path, created_at
    FROM materials
    WHERE course_id = ? AND lecturer_id = ?
    ORDER BY created_at DESC
";
$stmt = mysqli_prepare($conn, $materials_query);
mysqli_stmt_bind_param($stmt, "ii", $course_id, $lecturer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$materials = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

mysqli_close($conn);

$page_title = 'Manage Materials - ' . $course['course_code'];
require_once '../../includes/header-lecturer.php';
?>
    <style>
        .page-header {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .page-header-content {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header-left h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .page-header-left p {
            color: #64748b;
            font-size: 0.875rem;
        }

        .page-header-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn-back {
            background: #6b7280;
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-back:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        .btn-add {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
        }

        .page-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .page-container-content {
            padding: 2rem;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            animation: slideUp 0.3s;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #ecfdf5;
            border-color: #10b981;
            color: #065f46;
        }

        .alert-danger {
            background: #fef2f2;
            border-color: #ef4444;
            color: #991b1b;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f3f4f6;
        }

        th {
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            color: #374151;
        }

        td {
            padding: 0.75rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.875rem;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        table a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        table a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pdf {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-video {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-link {
            background: #d1fae5;
            color: #065f46;
        }

        .btn-action {
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.75rem;
            margin-right: 0.5rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .close-btn:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f8fafc;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .modal-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .btn-cancel {
            background: #6b7280;
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-cancel:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        .btn-upload {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .page-container-content {
                padding: 1.5rem;
            }

            .page-header-content {
                padding: 1.25rem 1.5rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .page-header-actions {
                width: 100%;
            }
        }
    </style>

    <div class="container">
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-header-left">
                    <h1><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></h1>
                    <p>Manage Learning Materials</p>
                </div>
                <div class="page-header-actions">
                    <a href="index.php" class="btn-back">← Back</a>
                    <button class="btn-add" onclick="openUploadModal()">+ Upload Material</button>
                </div>
            </div>
        </div>

        <div class="page-container">
            <div class="page-container-content">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (empty($materials)): ?>
                    <div class="empty-state">
                        <p>No materials uploaded yet. Click "Upload Material" to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>File/Link</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $material): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($material['title']); ?></td>
                                <td><span class="badge badge-<?php echo $material['material_type']; ?>"><?php echo strtoupper($material['material_type']); ?></span></td>
                                <td>
                                    <?php if ($material['material_type'] === 'link'): ?>
                                        <a href="<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank"><?php echo htmlspecialchars($material['file_path']); ?></a>
                                    <?php else: ?>
                                        <a href="../../<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank">Download</a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($material['created_at'])); ?></td>
                                <td>
                                    <button class="btn-action btn-delete" onclick="deleteMaterial(<?php echo $material['material_id']; ?>, '<?php echo htmlspecialchars($material['title']); ?>')">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Upload Material</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="action" value="upload">
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Material Type *</label>
                    <select name="material_type" id="material_type" class="form-control" required onchange="toggleFileInput()">
                        <option value="pdf">PDF</option>
                        <option value="video">Video</option>
                        <option value="link">Link</option>
                    </select>
                </div>
                <div class="form-group" id="fileGroup">
                    <label class="form-label">File *</label>
                    <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.mp4,.avi,.mov" id="fileInput">
                </div>
                <div class="form-group" id="linkGroup" style="display: none;">
                    <label class="form-label">Link *</label>
                    <input type="url" name="link" class="form-control" placeholder="https://...">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-upload">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleFileInput() {
            const type = document.getElementById('material_type').value;
            const fileGroup = document.getElementById('fileGroup');
            const linkGroup = document.getElementById('linkGroup');
            const fileInput = document.getElementById('fileInput');
            
            if (type === 'link') {
                fileGroup.style.display = 'none';
                linkGroup.style.display = 'block';
                fileInput.removeAttribute('required');
            } else {
                fileGroup.style.display = 'block';
                linkGroup.style.display = 'none';
                fileInput.setAttribute('required', 'required');
            }
        }

        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('uploadModal').classList.remove('active');
            document.getElementById('uploadForm').reset();
            toggleFileInput();
        }

        function deleteMaterial(materialId, title) {
            if (confirm(`Are you sure you want to delete "${title}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="material_id" value="${materialId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

