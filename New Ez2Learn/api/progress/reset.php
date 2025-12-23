<?php
/**
 * API Endpoint: Reset Progress
 * Admin/Lecturer endpoint to reset student progress
 */

header('Content-Type: application/json');

session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/progress_service.php';

// Check authentication and role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'lecturer'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$student_id = isset($input['student_id']) ? (int)$input['student_id'] : 0;
$course_id = isset($input['course_id']) ? (int)$input['course_id'] : 0;

if ($student_id <= 0 || $course_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Reset progress
$result = reset_student_course_progress($conn, $student_id, $course_id, $_SESSION['user_id'], $_SESSION['role']);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => $result['message']
    ]);
} else {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}

mysqli_close($conn);
?>
