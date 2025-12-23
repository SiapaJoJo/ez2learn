<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

require_once '../../includes/db-config.php';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$search = $_GET['search'] ?? '';
$course_filter = isset($_GET['course_filter']) ? (int)$_GET['course_filter'] : 0;

$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "a.title LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if ($course_filter > 0) {
    $where_conditions[] = "a.course_id = ?";
    $params[] = $course_filter;
    $types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$query = "
    SELECT 
        a.assignment_id, a.title, a.description, a.due_date, a.total_marks,
        c.course_code, c.course_name,
        u.name as lecturer_name,
        COUNT(DISTINCT s.student_id) as submission_count,
        COUNT(DISTINCT CASE WHEN s.marks IS NOT NULL THEN s.student_id END) as graded_count
    FROM assignments a
    LEFT JOIN courses c ON a.course_id = c.course_id
    LEFT JOIN users u ON a.lecturer_id = u.user_id
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id
    $where_clause
    GROUP BY a.assignment_id
    ORDER BY a.assignment_id DESC
";

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assignments = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$courses_query = "SELECT course_id, course_code, course_name FROM courses ORDER BY course_code";
$courses_result = mysqli_query($conn, $courses_query);
$courses = mysqli_fetch_all($courses_result, MYSQLI_ASSOC);
mysqli_close($conn);

$page_title = 'Manage Assignments';
require_once '../../includes/header-admin.php';
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
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }

        .filters {
            padding: 1.25rem 2rem;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-group label {
            font-size: 14px;
            color: #6b7280;
        }

        .filter-group input, .filter-group select {
            padding: 0.5rem 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
        }

        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
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

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f8fafc;
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

        .btn-submissions {
            background: #10b981;
            color: white;
        }

        .btn-submissions:hover {
            background: #059669;
        }

    </style>

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <h1 class="page-title">Manage Assignments</h1>
            </div>

            <div class="filters">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                    <div class="filter-group">
                        <label>Search:</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Assignment title...">
                    </div>
                    <div class="filter-group">
                        <label>Course:</label>
                        <select name="course_filter">
                            <option value="0">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>" <?php echo $course_filter == $course['course_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn-action btn-view">Filter</button>
                        <a href="index.php" class="btn-action btn-submissions" style="background: #6b7280;">Clear</a>
                    </div>
                </form>
            </div>

            <div class="content">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Course</th>
                                <th>Lecturer</th>
                                <th>Due Date</th>
                                <th>Total Marks</th>
                                <th>Submissions</th>
                                <th>Graded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assignments)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px; color: #6b7280;">No assignments found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo $assignment['assignment_id']; ?></td>
                                        <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['lecturer_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo $assignment['due_date'] ? date('M d, Y', strtotime($assignment['due_date'])) : 'N/A'; ?></td>
                                        <td><?php echo $assignment['total_marks'] ?? 'N/A'; ?></td>
                                        <td><?php echo $assignment['submission_count']; ?></td>
                                        <td><?php echo $assignment['graded_count']; ?> / <?php echo $assignment['submission_count']; ?></td>
                                        <td>
                                            <a href="view-assignment.php?assignment_id=<?php echo $assignment['assignment_id']; ?>" class="btn-action btn-view">View Details</a>
                                            <a href="view-submissions.php?assignment_id=<?php echo $assignment['assignment_id']; ?>" class="btn-action btn-submissions">View Submissions</a>
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

<?php require_once '../../includes/footer.php'; ?>

