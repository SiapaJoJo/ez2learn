<?php
/**
 * API Endpoint: Mark Material Complete
 * POST JSON endpoint for material completion
 * Handles offline queue retries
 */

header('Content-Type: application/json');

session_start();
require_once '../../includes/db-config.php';
require_once '../../includes/progress_service.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
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
$material_id = isset($input['material_id']) ? (int)$input['material_id'] : 0;

if ($material_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid material_id']);
    exit;
}

$student_id = $_SESSION['user_id'];

// Verify student is enrolled in this material's course
$stmt = mysqli_prepare($conn, "
    SELECT m.course_id 
    FROM materials m
    INNER JOIN enrollments e ON m.course_id = e.course_id
    WHERE m.material_id = ? AND e.student_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $material_id, $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    mysqli_stmt_close($stmt);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not enrolled in this course']);
    exit;
}

$row = mysqli_fetch_assoc($result);
$course_id = $row['course_id'];
mysqli_stmt_close($stmt);

// Mark material complete
$result = mark_material_complete($conn, $student_id, $material_id);

if ($result['success']) {
    // Get updated progress
    $progress_data = recalc_and_persist_course_progress($conn, $student_id, $course_id);
    
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'already_completed' => $result['already_completed'],
        'completed_percentage' => $progress_data['completed_percentage'],
        'breakdown' => $progress_data['breakdown']
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}

mysqli_close($conn);
?>
