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
$filter = $_GET['filter'] ?? 'all';

$error = '';
$success = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $assignment_id = (int)$_GET['id'];
    
    $stmt = mysqli_prepare($conn, "SELECT assignment_id FROM assignments WHERE assignment_id = ? AND lecturer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $lecturer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // First, get all submission files to delete them from disk
        $files_stmt = mysqli_prepare($conn, "SELECT file_path FROM submissions WHERE assignment_id = ?");
        mysqli_stmt_bind_param($files_stmt, "i", $assignment_id);
        mysqli_stmt_execute($files_stmt);
        $files_result = mysqli_stmt_get_result($files_stmt);
        
        while ($file_row = mysqli_fetch_assoc($files_result)) {
            if (!empty($file_row['file_path']) && file_exists('../../' . $file_row['file_path'])) {
                unlink('../../' . $file_row['file_path']);
            }
        }
        mysqli_stmt_close($files_stmt);
        
        // Delete submissions first (foreign key constraint)
        $delete_submissions_stmt = mysqli_prepare($conn, "DELETE FROM submissions WHERE assignment_id = ?");
        mysqli_stmt_bind_param($delete_submissions_stmt, "i", $assignment_id);
        mysqli_stmt_execute($delete_submissions_stmt);
        mysqli_stmt_close($delete_submissions_stmt);
        
        // Now delete the assignment
        $delete_stmt = mysqli_prepare($conn, "DELETE FROM assignments WHERE assignment_id = ?");
        mysqli_stmt_bind_param($delete_stmt, "i", $assignment_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $success = 'Assignment and all submissions deleted successfully!';
        } else {
            $error = 'Failed to delete assignment.';
        }
        mysqli_stmt_close($delete_stmt);
    } else {
        $error = 'Assignment not found or unauthorized.';
    }
    mysqli_stmt_close($stmt);
}

if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $assignment_id = (int)$_GET['id'];
    
    $stmt = mysqli_prepare($conn, "SELECT assignment_id, status FROM assignments WHERE assignment_id = ? AND lecturer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $lecturer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $assignment = mysqli_fetch_assoc($result);
        $new_status = ($assignment['status'] === 'open') ? 'closed' : 'open';
        
        $update_stmt = mysqli_prepare($conn, "UPDATE assignments SET status = ? WHERE assignment_id = ?");
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $assignment_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success = 'Assignment status updated to ' . strtoupper($new_status) . ' successfully!';
        } else {
            $error = 'Failed to update assignment status.';
        }
        mysqli_stmt_close($update_stmt);
    } else {
        $error = 'Assignment not found or unauthorized.';
    }
    mysqli_stmt_close($stmt);
}

$where_clause = "WHERE a.lecturer_id = $lecturer_id";
if ($filter === 'pending') {
    $where_clause .= " AND EXISTS (SELECT 1 FROM submissions s WHERE s.assignment_id = a.assignment_id AND s.marks IS NULL)";
}

$assignments_query = "
    SELECT 
        a.assignment_id, a.title, a.description, a.due_date, a.total_marks, a.status,
        c.course_code, c.course_name,
        COUNT(DISTINCT s.student_id) as submission_count,
        COUNT(DISTINCT CASE WHEN s.marks IS NOT NULL THEN s.student_id END) as graded_count
    FROM assignments a
    LEFT JOIN courses c ON a.course_id = c.course_id
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id
    $where_clause
    GROUP BY a.assignment_id
    ORDER BY a.assignment_id DESC
";
$result = mysqli_query($conn, $assignments_query);
$assignments = mysqli_fetch_all($result, MYSQLI_ASSOC);

mysqli_close($conn);

$page_title = 'My Assignments';
require_once '../../includes/header-lecturer.php';
?>
    <style>
        .page-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .page-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
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
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
        }

        .filters {
            padding: 1.25rem 2rem;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 1rem;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            color: #64748b;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .content {
            padding: 2rem;
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

        .btn-action {
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-action:hover {
            transform: translateY(-1px);
        }

        .btn-view {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-view:hover {
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-grade {
            background: #10b981;
            color: white;
        }

        .btn-grade:hover {
            background: #059669;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .btn-edit {
            background: #f59e0b;
            color: white;
        }

        .btn-edit:hover {
            background: #d97706;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin: 1.25rem 2rem;
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

    </style>

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <h1 class="page-title">My Assignments & Quizzes</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="create-assignment.php" class="btn-add">+ Create Assignment</a>
                    <a href="create-quiz.php" class="btn-add" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">+ Create Quiz</a>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="filters">
                <a href="index.php" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                <a href="index.php?filter=pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending Grading</a>
            </div>

            <div class="content">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Course</th>
                                <th>Due Date</th>
                                <th>Total Marks</th>
                                <th>Status</th>
                                <th>Submissions</th>
                                <th>Graded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assignments)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px; color: #6b7280;">No assignments found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo $assignment['assignment_id']; ?></td>
                                        <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?></td>
                                        <td><?php echo $assignment['due_date'] ? date('M d, Y', strtotime($assignment['due_date'])) : 'Not set'; ?></td>
                                        <td><?php echo $assignment['total_marks'] ?? 'N/A'; ?></td>
                                        <td>
                                            <span style="padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; <?php echo $assignment['status'] === 'open' ? 'background: #d1fae5; color: #065f46;' : 'background: #fee2e2; color: #991b1b;'; ?>">
                                                <?php echo strtoupper($assignment['status'] ?? 'open'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $assignment['submission_count']; ?></td>
                                        <td><?php echo $assignment['graded_count']; ?> / <?php echo $assignment['submission_count']; ?></td>
                                        <td>
                                            <button class="btn-action" style="background: <?php echo $assignment['status'] === 'open' ? '#f59e0b' : '#10b981'; ?>; color: white;" onclick="toggleStatus(<?php echo $assignment['assignment_id']; ?>, '<?php echo $assignment['status']; ?>')"><?php echo $assignment['status'] === 'open' ? 'Close' : 'Open'; ?></button>
                                            <a href="edit-assignment.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn-action btn-edit">Edit</a>
                                            <a href="view-submissions.php?assignment_id=<?php echo $assignment['assignment_id']; ?>" class="btn-action btn-grade">View & Grade</a>
                                            <button class="btn-action btn-delete" onclick="deleteAssignment(<?php echo $assignment['assignment_id']; ?>, '<?php echo htmlspecialchars($assignment['title']); ?>')">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteAssignment(assignmentId, title) {
            if (confirm(`Are you sure you want to delete assignment "${title}"? This will also delete all submissions.`)) {
                window.location.href = `index.php?action=delete&id=${assignmentId}`;
            }
        }

        function toggleStatus(assignmentId, currentStatus) {
            const newStatus = currentStatus === 'open' ? 'CLOSED' : 'OPEN';
            const action = currentStatus === 'open' ? 'close' : 'open';
            if (confirm(`Are you sure you want to ${action} this assignment? ${currentStatus === 'open' ? 'Students will not be able to submit.' : 'Students will be able to submit again.'}`)) {
                window.location.href = `index.php?action=toggle_status&id=${assignmentId}`;
            }
        }
    </script>
<?php require_once '../../includes/footer.php'; ?>

