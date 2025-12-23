<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
}

if (strtolower($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../../login.php');
    exit();
}

require_once '../../includes/db-config.php';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$student_id = $_SESSION['user_id'] ?? 0;
$filter = $_GET['filter'] ?? 'all';

$where_clause = "WHERE e.student_id = $student_id";
if ($filter === 'pending') {
    $where_clause .= " AND s.submission_id IS NULL AND (a.due_date IS NULL OR a.due_date >= CURDATE())";
} elseif ($filter === 'submitted') {
    $where_clause .= " AND s.submission_id IS NOT NULL";
} elseif ($filter === 'graded') {
    $where_clause .= " AND s.marks IS NOT NULL";
}

$assignments_query = "
    SELECT 
        a.assignment_id, a.title, a.description, a.due_date, a.total_marks, a.status as assignment_status,
        c.course_code, c.course_name,
        s.submission_id, s.marks, s.feedback, s.submitted_at,
        CASE 
            WHEN s.submission_id IS NOT NULL THEN 'submitted'
            WHEN a.due_date IS NOT NULL AND a.due_date < CURDATE() THEN 'overdue'
            ELSE 'pending'
        END as status
    FROM assignments a
    INNER JOIN enrollments e ON a.course_id = e.course_id
    LEFT JOIN courses c ON a.course_id = c.course_id
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = $student_id
    $where_clause
    ORDER BY 
        CASE 
            WHEN a.due_date IS NOT NULL AND a.due_date < CURDATE() THEN 0
            WHEN a.due_date IS NOT NULL THEN 1
            ELSE 2
        END,
        a.due_date ASC
";
$result = mysqli_query($conn, $assignments_query);
$assignments = mysqli_fetch_all($result, MYSQLI_ASSOC);

mysqli_close($conn);

$page_title = 'My Assignments';
require_once '../../includes/header-student.php';
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

        .assignments-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .assignment-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .assignment-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }

        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .assignment-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .assignment-course {
            color: #666;
            font-size: 14px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-submitted {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-graded {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        .assignment-details {
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            gap: 20px;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            min-width: 100px;
        }

        .detail-value {
            color: #333;
        }

        .assignment-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-action {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .btn-submit {
            background: #10b981;
            color: white;
        }

        .btn-submit:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-view {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-view:hover {
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
            transform: translateY(-1px);
        }

        .btn-disabled {
            background: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .marks-display {
            font-size: 18px;
            font-weight: bold;
            color: #3198F8;
            margin-top: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .assignment-header {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <h1 class="page-title">My Assignments</h1>
            </div>

            <div class="filters">
                <a href="index.php" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                <a href="index.php?filter=pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="index.php?filter=submitted" class="filter-btn <?php echo $filter === 'submitted' ? 'active' : ''; ?>">Submitted</a>
                <a href="index.php?filter=graded" class="filter-btn <?php echo $filter === 'graded' ? 'active' : ''; ?>">Graded</a>
            </div>

            <div class="content">
                <?php if (empty($assignments)): ?>
                    <div class="empty-state">
                        <p>No assignments found.</p>
                    </div>
                <?php else: ?>
                    <div class="assignments-list">
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="assignment-card">
                                <div class="assignment-header">
                                    <div>
                                        <div class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                        <div class="assignment-course"><?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?></div>
                                    </div>
                                    <span class="badge badge-<?php echo $assignment['status']; ?>">
                                        <?php echo ucfirst($assignment['status']); ?>
                                    </span>
                                </div>

                                <div class="assignment-details">
                                    <?php if ($assignment['description']): ?>
                                        <div class="detail-row">
                                            <span class="detail-label">Description:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars(substr($assignment['description'], 0, 150)); ?><?php echo strlen($assignment['description']) > 150 ? '...' : ''; ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Due Date:</span>
                                        <span class="detail-value">
                                            <?php 
                                            if ($assignment['due_date']) {
                                                $due_date = strtotime($assignment['due_date']);
                                                $now = time();
                                                if ($due_date < $now) {
                                                    echo '<span style="color: #ef4444;">' . date('M d, Y', $due_date) . ' (Overdue)</span>';
                                                } else {
                                                    echo date('M d, Y', $due_date);
                                                }
                                            } else {
                                                echo 'No due date';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Total Marks:</span>
                                        <span class="detail-value"><?php echo $assignment['total_marks'] ?? 'N/A'; ?></span>
                                    </div>
                                    <?php if ($assignment['submitted_at']): ?>
                                        <div class="detail-row">
                                            <span class="detail-label">Submitted:</span>
                                            <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($assignment['submitted_at'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($assignment['marks'] !== null): ?>
                                        <div class="marks-display">
                                            Marks: <?php echo $assignment['marks']; ?> / <?php echo $assignment['total_marks']; ?>
                                            <?php 
                                            $percentage = ($assignment['marks'] / $assignment['total_marks']) * 100;
                                            echo '(' . round($percentage, 1) . '%)';
                                            ?>
                                        </div>
                                        <?php if ($assignment['feedback']): ?>
                                            <div class="detail-row" style="margin-top: 10px;">
                                                <span class="detail-label">Feedback:</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($assignment['feedback']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="assignment-actions">
                                    <?php 
                                    $is_overdue = false;
                                    if (!empty($assignment['due_date'])) {
                                        $due_datetime = strtotime($assignment['due_date'] . ' 23:59:59');
                                        $is_overdue = time() > $due_datetime;
                                    }
                                    $is_closed = isset($assignment['assignment_status']) && $assignment['assignment_status'] === 'closed';
                                    ?>
                                    
                                    <?php if ($assignment['status'] === 'pending' && !$is_overdue && !$is_closed): ?>
                                        <a href="submit-assignment.php?assignment_id=<?php echo $assignment['assignment_id']; ?>" class="btn-action btn-submit">
                                            Submit Assignment
                                        </a>
                                    <?php elseif ($assignment['status'] === 'submitted' && !$is_overdue && !$is_closed && $assignment['marks'] === null): ?>
                                        <a href="submit-assignment.php?assignment_id=<?php echo $assignment['assignment_id']; ?>" class="btn-action btn-submit">
                                            Resubmit Assignment
                                        </a>
                                    <?php elseif ($is_overdue || $is_closed): ?>
                                        <button class="btn-action btn-disabled" disabled>
                                            <?php echo $is_closed ? 'Assignment Closed' : 'Submission Closed'; ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="view-assignment.php?assignment_id=<?php echo $assignment['assignment_id']; ?>" class="btn-action btn-view">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php require_once '../../includes/footer.php'; ?>

