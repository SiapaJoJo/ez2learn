<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || strtolower($_SESSION['role'] ?? '') !== 'admin') {
    echo '<p style="text-align: center; padding: 20px; color: #ef4444;">Unauthorized access</p>';
    exit();
}

require_once '../../includes/db-config.php';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    echo '<p style="text-align: center; padding: 20px; color: #ef4444;">Database connection failed</p>';
    exit();
}

$course_id = (int)($_GET['course_id'] ?? 0);

if ($course_id > 0) {
    $query = "
        SELECT u.user_id, u.name, u.email, e.enrolled_at
        FROM enrollments e
        JOIN users u ON e.student_id = u.user_id
        WHERE e.course_id = ?
        ORDER BY e.enrolled_at DESC
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    
    if (empty($students)) {
        echo '<p style="text-align: center; padding: 20px; color: #6b7280;">No students enrolled in this course.</p>';
    } else {
        echo '<div style="max-height: 400px; overflow-y: auto;">';
        echo '<table style="width: 100%; border-collapse: collapse;">';
        echo '<thead style="background: #f3f4f6; position: sticky; top: 0;">';
        echo '<tr><th style="padding: 10px; text-align: left; font-size: 14px;">Name</th><th style="padding: 10px; text-align: left; font-size: 14px;">Email</th><th style="padding: 10px; text-align: left; font-size: 14px;">Enrolled Date</th></tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($students as $student) {
            echo '<tr style="border-top: 1px solid #e5e7eb;">';
            echo '<td style="padding: 10px; font-size: 14px;">' . htmlspecialchars($student['name']) . '</td>';
            echo '<td style="padding: 10px; font-size: 14px;">' . htmlspecialchars($student['email']) . '</td>';
            echo '<td style="padding: 10px; font-size: 14px;">' . date('M d, Y', strtotime($student['enrolled_at'])) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
} else {
    echo '<p style="text-align: center; padding: 20px; color: #ef4444;">Invalid course ID</p>';
}

mysqli_close($conn);
?>

