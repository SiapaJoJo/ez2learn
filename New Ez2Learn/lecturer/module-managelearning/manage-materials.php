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

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ez2learn';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Materials - <?php echo htmlspecialchars($course['course_code']); ?> - Ez2Learn</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            color: #333;
            font-size: 28px;
        }

        .btn-back {
            background: #6b7280;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-back:hover {
            background: #4b5563;
        }

        .btn-add {
            background: #3198F8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-add:hover {
            background: #1e6bb8;
        }

        .page-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f3f4f6;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            color: #374151;
        }

        td {
            padding: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
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
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
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
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

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
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div class="logo-text">Ez2Learn</div>
            <div class="header-right">
                <ul class="nav-menu">
                    <li><a href="../index.php">Dashboard</a></li>
                    <li><a href="index.php">Materials</a></li>
                    <li><a href="../module-assignments/index.php">Assignments</a></li>
                    <li><a href="../module-progress/index.php">Progress</a></li>
                    <li><a href="../module-usermanagement/index.php">Students</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <div>
                <h1><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></h1>
                <p style="color: #666; margin-top: 5px;">Manage Learning Materials</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="index.php" class="btn-back">← Back</a>
                <button class="btn-add" onclick="openUploadModal()">+ Upload Material</button>
            </div>
        </div>

        <div class="page-container">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (empty($materials)): ?>
                <p style="text-align: center; padding: 40px; color: #6b7280;">No materials uploaded yet. Click "Upload Material" to get started.</p>
            <?php else: ?>
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
            <?php endif; ?>
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
                    <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx" id="fileInput">
                </div>
                <div class="form-group" id="linkGroup" style="display: none;">
                    <label class="form-label">Link *</label>
                    <input type="url" name="link" class="form-control" placeholder="https://...">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn-action" style="background: #6b7280; color: white;" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-action" style="background: #3198F8; color: white;">Upload</button>
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

