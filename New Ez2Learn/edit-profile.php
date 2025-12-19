<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ez2learn';

$error = '';
$success = '';
$user_data = array();
$user_id = $_SESSION['user_id'] ?? 0;
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if ($conn && $user_id) {
    $stmt = mysqli_prepare($conn, "SELECT user_id, name, email, role FROM users WHERE user_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $user_data = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($email)) {
        $error = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (empty($name)) {
        $error = 'Name is required.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $error = 'Email already exists. Please use a different email.';
                mysqli_stmt_close($stmt);
            } else {
                mysqli_stmt_close($stmt);
                
                $update_fields = array();
                $update_values = array();
                $update_types = '';
                
                $update_fields[] = "name = ?";
                $update_values[] = $name;
                $update_types .= 's';
                
                $update_fields[] = "email = ?";
                $update_values[] = $email;
                $update_types .= 's';
                
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $error = 'Please enter your current password to change it.';
                    } elseif (strlen($new_password) < 6) {
                        $error = 'New password must be at least 6 characters long.';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'New passwords do not match.';
                    } else {
                        $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ?");
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            if ($row = mysqli_fetch_assoc($result)) {
                                if (password_verify($current_password, $row['password'])) {
                                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                                    $update_fields[] = "password = ?";
                                    $update_values[] = $hashed_password;
                                    $update_types .= 's';
                                } else {
                                    $error = 'Current password is incorrect.';
                                }
                            }
                            mysqli_stmt_close($stmt);
                        }
                    }
                }
                
                if (empty($error)) {
                    $update_values[] = $user_id;
                    $update_types .= 'i';
                    
                    $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE user_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, $update_types, ...$update_values);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $success = 'Profile updated successfully!';
                            $_SESSION['name'] = $name;
                            $_SESSION['email'] = $email;
                            
                            $stmt2 = mysqli_prepare($conn, "SELECT user_id, name, email, role FROM users WHERE user_id = ?");
                            if ($stmt2) {
                                mysqli_stmt_bind_param($stmt2, "i", $user_id);
                                mysqli_stmt_execute($stmt2);
                                $result = mysqli_stmt_get_result($stmt2);
                                if ($row = mysqli_fetch_assoc($result)) {
                                    $user_data = $row;
                                }
                                mysqli_stmt_close($stmt2);
                            }
                        } else {
                            $error = 'Failed to update profile. Please try again.';
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $error = 'Database query failed.';
                    }
                }
            }
        } else {
            $error = 'Database query failed.';
        }
    }
}

if ($conn) {
    mysqli_close($conn);
}

$user_role = strtolower($_SESSION['role'] ?? 'student');
if ($user_role == 'admin') {
    $dashboard_url = 'admin/index.php';
} elseif ($user_role == 'lecturer') {
    $dashboard_url = 'lecturer/index.php';
} else {
    $dashboard_url = 'student/index.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Ez2Learn</title>
    <link rel="icon" type="image/x-icon" href="image/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="image/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -250px;
            right: -250px;
            filter: blur(80px);
        }

        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            bottom: -200px;
            left: -200px;
            filter: blur(80px);
        }

        .container {
            max-width: 800px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5);
            padding: 3rem;
            animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            margin: 2rem auto;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: white;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .profile-header h1 {
            color: #1e293b;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .profile-header p {
            color: #64748b;
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #1e293b;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f8fafc;
            color: #1e293b;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-group input::placeholder {
            color: #999;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 1.125rem;
            padding: 0.25rem;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #667eea;
        }

        .password-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e2e8f0;
        }

        .password-section h3 {
            color: #1e293b;
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
        }

        .btn-update {
            width: 100%;
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 14px 0 rgba(102, 126, 234, 0.39);
            margin-top: 0.5rem;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .btn-update:active {
            transform: translateY(0);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #667eea;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #764ba2;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            animation: slideUp 0.3s;
        }

        .alert-error {
            background: #fef2f2;
            border-color: #ef4444;
            color: #991b1b;
        }

        .alert-success {
            background: #ecfdf5;
            border-color: #10b981;
            color: #065f46;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 10px;
        }

        .role-badge.admin {
            background: #fee;
            color: #c33;
        }

        .role-badge.lecturer {
            background: #e3f2fd;
            color: #1976d2;
        }

        .role-badge.student {
            background: #e8f5e9;
            color: #388e3c;
        }

        @media (max-width: 640px) {
            .profile-card {
                padding: 2rem 1.5rem;
            }

            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 40px;
            }

            .profile-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-card">
            <a href="<?php echo $dashboard_url; ?>" class="back-link">← Back to Dashboard</a>
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php 
                    $initial = !empty($user_data['name']) 
                        ? strtoupper(substr($user_data['name'], 0, 1)) 
                        : 'U'; 
                    echo $initial; 
                    ?>
                </div>
                <h1>Edit Profile</h1>
                <p>
                    <?php echo htmlspecialchars($user_data['name'] ?? ''); ?>
                    <span class="role-badge <?php echo strtolower($user_data['role'] ?? 'student'); ?>">
                        <?php echo htmlspecialchars(ucfirst($user_data['role'] ?? 'Student')); ?>
                    </span>
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="profileForm">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        placeholder="Enter your full name"
                        required
                        value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Enter your email"
                        required
                        value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>"
                    >
                </div>

                <div class="password-section">
                    <h3>Change Password (Optional)</h3>
                    <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1.25rem;">Leave blank if you don't want to change your password</p>

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <div class="password-container">
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                placeholder="Enter current password"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('current_password')">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-container">
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                placeholder="Enter new password"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('new_password')">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-container">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="Confirm new password"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">👁️</button>
                        </div>
                    </div>
                </div>

                <button type="submit" name="update_profile" class="btn-update">Update Profile</button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleBtn = passwordInput.nextElementSibling;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;

            if (!name) {
                e.preventDefault();
                alert('Name is required.');
                return false;
            }

            if (!email) {
                e.preventDefault();
                alert('Email is required.');
                return false;
            }

            if (!email.includes('@') || !email.includes('.')) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }

            if (newPassword || confirmPassword || currentPassword) {
                if (!currentPassword) {
                    e.preventDefault();
                    alert('Please enter your current password to change it.');
                    return false;
                }

                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('New password must be at least 6 characters long.');
                    return false;
                }

                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match.');
                    return false;
                }
            }
        });
    </script>
</body>
</html>

