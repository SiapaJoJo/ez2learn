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

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ez2learn';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'student';
        $status = $_POST['status'] ?? 'active';
        
        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['error'] = 'Name, email, and password are required.';
        } elseif (strlen($password) < 6) {
            $_SESSION['error'] = 'Password must be at least 6 characters.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email address.';
        } else {
            $check_stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($check_stmt, "s", $email);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error = 'Email already exists.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO users (name, email, password, role, status)
                    VALUES (?, ?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hashed_password, $role, $status);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success'] = 'User created successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to create user.';
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_stmt_close($check_stmt);
        }
    } elseif ($action === 'update') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'student';
        $status = $_POST['status'] ?? 'active';
        
        if ($user_id > 0 && !empty($name) && !empty($email)) {
            $check_stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $_SESSION['error'] = 'Email already exists.';
            } else {
                $stmt = mysqli_prepare($conn, "
                    UPDATE users 
                    SET name = ?, email = ?, role = ?, status = ?
                    WHERE user_id = ?
                ");
                mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $role, $status, $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success'] = 'User updated successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to update user.';
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_stmt_close($check_stmt);
        }
    } elseif ($action === 'reset_password') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';
        
        if ($user_id > 0 && !empty($new_password) && strlen($new_password) >= 6) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = 'Password reset successfully!';
            } else {
                $_SESSION['error'] = 'Failed to reset password.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['error'] = 'Invalid password. Password must be at least 6 characters.';
        }
    } elseif ($action === 'toggle_status') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? 'active';
        
        if ($user_id > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $new_status, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = 'User status updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update user status.';
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    if (isset($_SESSION['success']) || isset($_SESSION['error'])) {
        header('Location: index.php');
        exit();
    }
}

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role_filter'] ?? 'all';
$status_filter = $_GET['status_filter'] ?? 'all';

$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= 'ss';
}

if ($role_filter !== 'all') {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$query = "SELECT user_id, name, email, role, status, created_at FROM users $where_clause ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
mysqli_close($conn);

$page_title = 'User Management';
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

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-admin {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-lecturer {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-student {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-inactive {
            background: #f3f4f6;
            color: #6b7280;
        }

        .btn-action {
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-action:hover {
            transform: translateY(-1px);
        }

        .btn-edit {
            background: #fbbf24;
            color: #111827;
        }

        .btn-edit:hover {
            background: #f59e0b;
        }

        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-reset:hover {
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }

        .btn-toggle {
            background: #10b981;
            color: white;
        }

        .btn-toggle:hover {
            background: #059669;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f8fafc;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
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
                <h1 class="page-title">User Management</h1>
                <button class="btn-add" onclick="openCreateModal()">+ Create User</button>
            </div>


            <div class="filters">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                    <div class="filter-group">
                        <label>Search:</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email...">
                    </div>
                    <div class="filter-group">
                        <label>Role:</label>
                        <select name="role_filter">
                            <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="lecturer" <?php echo $role_filter === 'lecturer' ? 'selected' : ''; ?>>Lecturer</option>
                            <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Student</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Status:</label>
                        <select name="status_filter">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn-action btn-edit">Filter</button>
                        <a href="index.php" class="btn-action btn-toggle">Clear</a>
                    </div>
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
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #6b7280;">No users found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                        <td><span class="badge badge-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <button class="btn-action btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">Edit</button>
                                            <button class="btn-action btn-reset" onclick="openResetModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">Reset Password</button>
                                            <button class="btn-action btn-toggle" onclick="toggleStatus(<?php echo $user['user_id']; ?>, '<?php echo $user['status']; ?>')">
                                                <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
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

    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Create User</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="userForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="user_id" id="userId">
                <div class="form-group">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
                <div class="form-group" id="passwordGroup">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Role *</label>
                    <select name="role" id="role" class="form-control" required>
                        <option value="admin">Admin</option>
                        <option value="lecturer">Lecturer</option>
                        <option value="student" selected>Student</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" class="btn-action btn-toggle" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-action btn-reset">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="resetModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Reset Password</h2>
                <button class="close-btn" onclick="closeResetModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="resetUserId">
                <div class="form-group">
                    <label class="form-label">User Name</label>
                    <input type="text" id="resetUsername" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password *</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn-action btn-toggle" onclick="closeResetModal()">Cancel</button>
                    <button type="submit" class="btn-action btn-reset">Reset Password</button>
                </div>
            </form>
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

        function openCreateModal() {
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Create User';
            document.getElementById('userId').value = '';
            document.getElementById('userForm').reset();
            document.getElementById('passwordGroup').style.display = 'block';
            document.getElementById('password').required = true;
            document.getElementById('userModal').classList.add('active');
        }

        function openEditModal(user) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = user.user_id;
            document.getElementById('name').value = user.name;
            document.getElementById('email').value = user.email;
            document.getElementById('role').value = user.role;
            document.getElementById('status').value = user.status;
            document.getElementById('passwordGroup').style.display = 'none';
            document.getElementById('password').required = false;
            document.getElementById('userModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }

        function openResetModal(userId, name) {
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetUsername').value = name;
            document.getElementById('resetModal').classList.add('active');
        }

        function closeResetModal() {
            document.getElementById('resetModal').classList.remove('active');
        }

        function toggleStatus(userId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            if (confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this user?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="new_status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

    </script>
<?php require_once '../../includes/footer.php'; ?>

