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
$course_filter = isset($_GET['course_filter']) ? (int)$_GET['course_filter'] : 0;

$courses_query = "
    SELECT c.course_id, c.course_code, c.course_name
    FROM courses c
    INNER JOIN course_lecturers cl ON c.course_id = cl.course_id
    WHERE cl.lecturer_id = ?
    ORDER BY c.course_code ASC
";
$stmt = mysqli_prepare($conn, $courses_query);
mysqli_stmt_bind_param($stmt, "i", $lecturer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$courses = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$where_clause = "WHERE cl.lecturer_id = $lecturer_id";
if ($course_filter > 0) {
    $where_clause .= " AND e.course_id = $course_filter";
}

$students_query = "
    SELECT DISTINCT
        u.user_id, u.name, u.email,
        COUNT(DISTINCT e.course_id) as enrolled_courses,
        COUNT(DISTINCT s.assignment_id) as assignments_submitted,
        COUNT(DISTINCT qa.quiz_id) as quizzes_attempted
    FROM users u
    INNER JOIN enrollments e ON u.user_id = e.student_id
    INNER JOIN course_lecturers cl ON e.course_id = cl.course_id
    LEFT JOIN submissions s ON u.user_id = s.student_id
    LEFT JOIN quiz_attempts qa ON u.user_id = qa.student_id
    $where_clause
    GROUP BY u.user_id
    ORDER BY u.name ASC
";
$result = mysqli_query($conn, $students_query);
$students = mysqli_fetch_all($result, MYSQLI_ASSOC);

mysqli_close($conn);

$page_title = 'View Students';
require_once '../../includes/header-lecturer.php';
?>
<style>

        .page-container {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .page-header {
            padding: 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e3a5f;
        }

        .filters {
            padding: 20px 30px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .filter-group {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .filter-group label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }

        .filter-group select {
            padding: 0.5rem 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
        }

        .filter-group select:focus {
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

        button[type="submit"], a[href="index.php"] {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        button[type="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.4);
        }

        a[href="index.php"] {
            background: #6b7280;
            color: white;
        }

        a[href="index.php"]:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }
    </style>

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <h1 class="page-title">Enrolled Students</h1>
            </div>

            <div class="filters">
                <form method="GET" style="display: flex; gap: 15px; align-items: center;">
                    <div class="filter-group">
                        <label>Filter by Course:</label>
                        <select name="course_filter">
                            <option value="0">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>" <?php echo $course_filter == $course['course_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" style="padding: 8px 16px; background: #3198F8; color: white; border: none; border-radius: 6px; cursor: pointer;">Filter</button>
                    <a href="index.php" style="padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 6px; text-decoration: none; display: inline-block;">Clear</a>
                </form>
            </div>

            <div class="content">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Enrolled Courses</th>
                                <th>Assignments Submitted</th>
                                <th>Quizzes Attempted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">No students found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo $student['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo $student['enrolled_courses']; ?></td>
                                        <td><?php echo $student['assignments_submitted']; ?></td>
                                        <td><?php echo $student['quizzes_attempted']; ?></td>
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
        function toggleDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('active');
        }
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
<?php require_once '../../includes/footer.php'; ?>

