<?php
session_start();

// Check authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    die('Access denied. Please login.');
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ez2learn';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    http_response_code(500);
    die("Connection failed: " . mysqli_connect_error());
}

// Get file type and ID
$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!$id || !in_array($type, ['assignment', 'submission'])) {
    http_response_code(400);
    die('Invalid request.');
}

$file_path = null;
$allowed = false;

if ($type === 'assignment') {
    // Download teacher's assignment file
    $stmt = mysqli_prepare($conn, "SELECT assignment_file FROM assignments WHERE id = ? AND status = 'published'");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $assignment = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($assignment && !empty($assignment['assignment_file'])) {
        $file_path = $assignment['assignment_file'];
        $allowed = true; // Any logged-in user can download published assignment files
    }
} elseif ($type === 'submission') {
    // Download student submission file
    $user_role = strtolower($_SESSION['role'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    $stmt = mysqli_prepare($conn, "
        SELECT s.file_path, a.created_by as staff_id, s.student_id
        FROM assignment_submissions s
        JOIN assignments a ON s.assignment_id = a.id
        WHERE s.id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $submission = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($submission) {
        $file_path = $submission['file_path'];
        
        // Check permissions: staff who created assignment OR student who submitted
        if ($user_role === 'staff' && $submission['staff_id'] == $user_id) {
            $allowed = true;
        } elseif ($user_role === 'student' && $submission['student_id'] == $user_id) {
            $allowed = true;
        }
    }
}

mysqli_close($conn);

// Validate and download
if (!$allowed || !$file_path) {
    http_response_code(403);
    die('Access denied or file not found.');
}

$full_path = __DIR__ . '/' . $file_path;

if (!file_exists($full_path)) {
    http_response_code(404);
    die('File not found on server.');
}

// Security: prevent directory traversal
$real_path = realpath($full_path);
$base_dir = realpath(__DIR__ . '/uploads/assignments/');

if (strpos($real_path, $base_dir) !== 0) {
    http_response_code(403);
    die('Invalid file path.');
}

// Get file info
$file_name = basename($file_path);
$file_size = filesize($full_path);
$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Set content type based on extension
$content_types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain',
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];

$content_type = $content_types[$file_ext] ?? 'application/octet-stream';

// Send headers
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Length: ' . $file_size);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Clear output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Read and output file
readfile($full_path);
exit;
?>
