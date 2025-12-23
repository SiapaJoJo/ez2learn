<?php
/**
 * API Endpoint: Recalculate Progress
 * Internal endpoint to trigger progress recalculation
 */

header('Content-Type: application/json');

session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/progress_service.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
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

// Authorization: student can only recalc their own, lecturer/admin can recalc any
if ($_SESSION['role'] === 'student' && $_SESSION['user_id'] != $student_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// Verify enrollment or lecturer access
if ($_SESSION['role'] === 'student') {
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM enrollments WHERE student_id = ? AND course_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) == 0) {
        mysqli_stmt_close($stmt);
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not enrolled']);
        exit;
    }
    mysqli_stmt_close($stmt);
} elseif ($_SESSION['role'] === 'lecturer') {
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM course_lecturers WHERE course_id = ? AND lecturer_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $course_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) == 0) {
        mysqli_stmt_close($stmt);
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not your course']);
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Recalculate
$progress_data = recalc_and_persist_course_progress($conn, $student_id, $course_id);

echo json_encode([
    'success' => true,
    'completed_percentage' => $progress_data['completed_percentage'],
    'breakdown' => $progress_data['breakdown'],
    'weights' => $progress_data['weights']
]);

mysqli_close($conn);
?>
