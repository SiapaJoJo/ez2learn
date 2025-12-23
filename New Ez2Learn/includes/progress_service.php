<?php
/**
 * Progress Service Layer
 * 
 * Centralizes all progress calculation and persistence logic.
 * MAINTAINABILITY: All progress logic in one place for easy modification.
 * MODIFIABILITY: Weighting changes isolated to progress_config.php.
 */

require_once __DIR__ . '/progress_config.php';

/**
 * Ensure a progress record exists for a student-course pair
 * Called on enrollment or first interaction
 * 
 * @param mysqli $conn Database connection
 * @param int $student_id
 * @param int $course_id
 * @return bool Success
 */
function ensure_progress_record($conn, $student_id, $course_id) {
    // Check if record exists
    $stmt = mysqli_prepare($conn, "SELECT progress_id FROM progress WHERE student_id = ? AND course_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        mysqli_stmt_close($stmt);
        return true; // Already exists
    }
    mysqli_stmt_close($stmt);
    
    // Create new record
    $stmt = mysqli_prepare($conn, "INSERT INTO progress (student_id, course_id, completed_percentage, last_updated) VALUES (?, ?, 0, NOW())");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Log creation
    log_progress_event($conn, $student_id, $course_id, 'recalculate', null, json_encode(['action' => 'initial_create']), 'success');
    
    return $success;
}

/**
 * Mark a learning material as complete
 * 
 * @param mysqli $conn
 * @param int $student_id
 * @param int $material_id
 * @return array ['success' => bool, 'message' => string, 'already_completed' => bool]
 */
function mark_material_complete($conn, $student_id, $material_id) {
    // Check if already completed
    $stmt = mysqli_prepare($conn, "SELECT id FROM material_progress WHERE student_id = ? AND material_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $material_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        mysqli_stmt_close($stmt);
        return ['success' => true, 'message' => 'Already completed', 'already_completed' => true];
    }
    mysqli_stmt_close($stmt);
    
    // Get course_id for this material
    $stmt = mysqli_prepare($conn, "SELECT course_id FROM materials WHERE material_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $material_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Material not found', 'already_completed' => false];
    }
    
    $row = mysqli_fetch_assoc($result);
    $course_id = $row['course_id'];
    mysqli_stmt_close($stmt);
    
    // Insert completion record
    $stmt = mysqli_prepare($conn, "INSERT INTO material_progress (student_id, material_id, completed_at) VALUES (?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $material_id);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if (!$success) {
        log_progress_event($conn, $student_id, $course_id, 'material_complete', $material_id, null, 'failed', mysqli_error($conn));
        return ['success' => false, 'message' => 'Database error', 'already_completed' => false];
    }
    
    // Log event
    log_progress_event($conn, $student_id, $course_id, 'material_complete', $material_id, json_encode(['material_id' => $material_id]), 'success');
    
    // Recalculate progress
    recalc_and_persist_course_progress($conn, $student_id, $course_id);
    
    return ['success' => true, 'message' => 'Material marked complete', 'already_completed' => false];
}

/**
 * Recalculate and persist course progress percentage
 * Core calculation logic using weighted components
 * 
 * @param mysqli $conn
 * @param int $student_id
 * @param int $course_id
 * @return array ['completed_percentage' => int, 'breakdown' => array]
 */
function recalc_and_persist_course_progress($conn, $student_id, $course_id) {
    // Ensure progress record exists
    ensure_progress_record($conn, $student_id, $course_id);
    
    // Get breakdown
    $breakdown = get_student_course_progress_breakdown($conn, $student_id, $course_id);
    
    // Calculate weighted percentage
    $weights = get_adjusted_weights(
        $breakdown['materials']['total'],
        $breakdown['assignments']['total'],
        $breakdown['quizzes']['total']
    );
    
    $materials_pct = $breakdown['materials']['total'] > 0 
        ? $breakdown['materials']['completed'] / $breakdown['materials']['total'] 
        : 0;
    $assignments_pct = $breakdown['assignments']['total'] > 0 
        ? $breakdown['assignments']['completed'] / $breakdown['assignments']['total'] 
        : 0;
    $quizzes_pct = $breakdown['quizzes']['total'] > 0 
        ? $breakdown['quizzes']['completed'] / $breakdown['quizzes']['total'] 
        : 0;
    
    $weighted_sum = ($materials_pct * $weights['materials']) +
                    ($assignments_pct * $weights['assignments']) +
                    ($quizzes_pct * $weights['quizzes']);
    
    $completed_percentage = round($weighted_sum * 100);
    
    // Persist to database
    $stmt = mysqli_prepare($conn, "UPDATE progress SET completed_percentage = ?, last_updated = NOW() WHERE student_id = ? AND course_id = ?");
    mysqli_stmt_bind_param($stmt, "iii", $completed_percentage, $student_id, $course_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Check certificate eligibility
    if ($completed_percentage >= PROGRESS_CERTIFICATE_THRESHOLD) {
        issue_certificate_if_eligible($conn, $student_id, $course_id);
    }
    
    return [
        'completed_percentage' => $completed_percentage,
        'breakdown' => $breakdown,
        'weights' => $weights
    ];
}

/**
 * Get detailed progress breakdown for a student in a course
 * 
 * @param mysqli $conn
 * @param int $student_id
 * @param int $course_id
 * @return array Breakdown of materials, assignments, quizzes
 */
function get_student_course_progress_breakdown($conn, $student_id, $course_id) {
    // Materials
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM materials WHERE course_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $total_materials = mysqli_fetch_assoc($result)['total'];
    mysqli_stmt_close($stmt);
    
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as completed FROM material_progress mp INNER JOIN materials m ON mp.material_id = m.material_id WHERE mp.student_id = ? AND m.course_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $completed_materials = mysqli_fetch_assoc($result)['completed'];
    mysqli_stmt_close($stmt);
    
    // Assignments
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM assignments WHERE course_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $total_assignments = mysqli_fetch_assoc($result)['total'];
    mysqli_stmt_close($stmt);
    
    $stmt = mysqli_prepare($conn, "SELECT COUNT(DISTINCT a.assignment_id) as completed FROM submissions s INNER JOIN assignments a ON s.assignment_id = a.assignment_id WHERE s.student_id = ? AND a.course_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $completed_assignments = mysqli_fetch_assoc($result)['completed'];
    mysqli_stmt_close($stmt);
    
    // Quizzes
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM quizzes WHERE course_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $total_quizzes = mysqli_fetch_assoc($result)['total'];
    mysqli_stmt_close($stmt);
    
    $stmt = mysqli_prepare($conn, "SELECT COUNT(DISTINCT q.quiz_id) as completed FROM quiz_attempts qa INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id WHERE qa.student_id = ? AND q.course_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $completed_quizzes = mysqli_fetch_assoc($result)['completed'];
    mysqli_stmt_close($stmt);
    
    return [
        'materials' => [
            'total' => (int)$total_materials,
            'completed' => (int)$completed_materials,
            'percentage' => $total_materials > 0 ? round(($completed_materials / $total_materials) * 100) : 0
        ],
        'assignments' => [
            'total' => (int)$total_assignments,
            'completed' => (int)$completed_assignments,
            'percentage' => $total_assignments > 0 ? round(($completed_assignments / $total_assignments) * 100) : 0
        ],
        'quizzes' => [
            'total' => (int)$total_quizzes,
            'completed' => (int)$completed_quizzes,
            'percentage' => $total_quizzes > 0 ? round(($completed_quizzes / $total_quizzes) * 100) : 0
        ]
    ];
}

/**
 * Reset student progress for a course
 * Admin/Lecturer function with proper authorization
 * 
 * @param mysqli $conn
 * @param int $student_id
 * @param int $course_id
 * @param int $actor_id Who is performing the reset
 * @param string $actor_role admin or lecturer
 * @return array ['success' => bool, 'message' => string]
 */
function reset_student_course_progress($conn, $student_id, $course_id, $actor_id, $actor_role) {
    // Authorization check for lecturer
    if ($actor_role === 'lecturer') {
        $stmt = mysqli_prepare($conn, "SELECT 1 FROM course_lecturers WHERE course_id = ? AND lecturer_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $course_id, $actor_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 0) {
            mysqli_stmt_close($stmt);
            return ['success' => false, 'message' => 'Unauthorized: Not your course'];
        }
        mysqli_stmt_close($stmt);
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        // Delete material progress
        $stmt = mysqli_prepare($conn, "DELETE mp FROM material_progress mp INNER JOIN materials m ON mp.material_id = m.material_id WHERE mp.student_id = ? AND m.course_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Reset progress percentage
        $stmt = mysqli_prepare($conn, "UPDATE progress SET completed_percentage = 0, last_updated = NOW() WHERE student_id = ? AND course_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Delete certificate if exists
        $stmt = mysqli_prepare($conn, "DELETE FROM certificates WHERE student_id = ? AND course_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Log reset event
        log_progress_event($conn, $student_id, $course_id, 'manual_reset', null, json_encode(['actor_id' => $actor_id, 'actor_role' => $actor_role]), 'success');
        
        mysqli_commit($conn);
        return ['success' => true, 'message' => 'Progress reset successfully'];
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'message' => 'Reset failed: ' . $e->getMessage()];
    }
}

/**
 * Issue certificate if student is eligible
 * 
 * @param mysqli $conn
 * @param int $student_id
 * @param int $course_id
 */
function issue_certificate_if_eligible($conn, $student_id, $course_id) {
    // Check if already issued
    $stmt = mysqli_prepare($conn, "SELECT id FROM certificates WHERE student_id = ? AND course_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        mysqli_stmt_close($stmt);
        return; // Already issued
    }
    mysqli_stmt_close($stmt);
    
    // Generate unique certificate code
    $certificate_code = 'EZ2L-' . strtoupper(substr(md5($student_id . $course_id . time()), 0, 10));
    
    // Issue certificate
    $stmt = mysqli_prepare($conn, "INSERT INTO certificates (student_id, course_id, certificate_code, issued_at) VALUES (?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "iis", $student_id, $course_id, $certificate_code);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Log progress event for audit trail and recoverability
 * 
 * @param mysqli $conn
 * @param int $student_id
 * @param int $course_id
 * @param string $event_type
 * @param int|null $ref_id
 * @param string|null $payload_json
 * @param string $status
 * @param string|null $error_message
 */
function log_progress_event($conn, $student_id, $course_id, $event_type, $ref_id, $payload_json, $status, $error_message = null) {
    $stmt = mysqli_prepare($conn, "INSERT INTO progress_update_logs (student_id, course_id, event_type, ref_id, payload_json, status, error_message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "iisisss", $student_id, $course_id, $event_type, $ref_id, $payload_json, $status, $error_message);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Get all courses for a student with progress
 * 
 * @param mysqli $conn
 * @param int $student_id
 * @return array
 */
function get_student_all_courses_progress($conn, $student_id) {
    $stmt = mysqli_prepare($conn, "
        SELECT c.course_id, c.course_code, c.course_name, 
               COALESCE(p.completed_percentage, 0) as completed_percentage,
               p.last_updated,
               (SELECT COUNT(*) FROM certificates WHERE student_id = ? AND course_id = c.course_id) as has_certificate
        FROM enrollments e
        INNER JOIN courses c ON e.course_id = c.course_id
        LEFT JOIN progress p ON p.student_id = e.student_id AND p.course_id = c.course_id
        WHERE e.student_id = ?
        ORDER BY p.last_updated DESC, c.course_name ASC
    ");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $courses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $courses[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    return $courses;
}

/**
 * Get course progress summary for lecturer
 * 
 * @param mysqli $conn
 * @param int $lecturer_id
 * @param int $course_id
 * @return array
 */
function get_lecturer_course_progress_summary($conn, $lecturer_id, $course_id) {
    // Verify lecturer owns this course
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM course_lecturers WHERE course_id = ? AND lecturer_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $course_id, $lecturer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) == 0) {
        mysqli_stmt_close($stmt);
        return null;
    }
    mysqli_stmt_close($stmt);
    
    // Get enrolled students with progress
    $stmt = mysqli_prepare($conn, "
        SELECT u.user_id, u.name, u.email,
               COALESCE(p.completed_percentage, 0) as completed_percentage,
               p.last_updated
        FROM enrollments e
        INNER JOIN users u ON e.student_id = u.user_id
        LEFT JOIN progress p ON p.student_id = e.student_id AND p.course_id = e.course_id
        WHERE e.course_id = ?
        ORDER BY p.completed_percentage DESC, u.name ASC
    ");
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    return $students;
}
?>
