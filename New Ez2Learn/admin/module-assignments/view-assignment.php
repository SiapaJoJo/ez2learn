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

$assignment_id = (int)($_GET['assignment_id'] ?? 0);

if ($assignment_id > 0) {
    $query = "
        SELECT 
            a.*,
            c.course_code, c.course_name,
            u.name as lecturer_name, u.email as lecturer_email
        FROM assignments a
        LEFT JOIN courses c ON a.course_id = c.course_id
        LEFT JOIN users u ON a.lecturer_id = u.user_id
        WHERE a.assignment_id = ?
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $assignment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $assignment = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} else {
    $assignment = null;
}

mysqli_close($conn);

$page_title = 'View Assignment';
require_once '../../includes/header-admin.php';
?>
<style>
        .page-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .btn-back {
            background: #6b7280;
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-back:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        .assignment-details {
            margin-top: 1.5rem;
        }

        .detail-row {
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            color: #1e293b;
            font-size: 1rem;
        }

        h1 {
            color: #1e293b;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
    </style>

    <div class="container">
        <div class="page-container">
            <a href="index.php" class="btn-back">← Back to Assignments</a>
            
            <?php if ($assignment): ?>
                <h1 style="margin-bottom: 1.5rem; color: #1e293b; font-size: 1.5rem; font-weight: 700;"><?php echo htmlspecialchars($assignment['title']); ?></h1>
                
                <div class="assignment-details">
                    <div class="detail-row">
                        <div class="detail-label">Course</div>
                        <div class="detail-value"><?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Lecturer</div>
                        <div class="detail-value"><?php echo htmlspecialchars($assignment['lecturer_name'] . ' (' . $assignment['lecturer_email'] . ')'); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Due Date</div>
                        <div class="detail-value"><?php echo $assignment['due_date'] ? date('F d, Y', strtotime($assignment['due_date'])) : 'Not set'; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Total Marks</div>
                        <div class="detail-value"><?php echo $assignment['total_marks'] ?? 'Not set'; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Description</div>
                        <div class="detail-value" style="white-space: pre-wrap;"><?php echo htmlspecialchars($assignment['description'] ?? 'No description provided.'); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 2.5rem; color: #64748b;">Assignment not found</p>
            <?php endif; ?>
        </div>
    </div>
<?php require_once '../../includes/footer.php'; ?>

