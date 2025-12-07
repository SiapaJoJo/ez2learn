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
    $stmt = mysqli_prepare($conn, "SELECT id, username, email, first_name, last_name, phone, role FROM users WHERE id = ?");
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
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($email)) {
        $error = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
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
                
                $update_fields[] = "first_name = ?";
                $update_values[] = $first_name;
                $update_types .= 's';
                
                $update_fields[] = "last_name = ?";
                $update_values[] = $last_name;
                $update_types .= 's';
                
                $update_fields[] = "email = ?";
                $update_values[] = $email;
                $update_types .= 's';
                
                $update_fields[] = "phone = ?";
                $update_values[] = $phone;
                $update_types .= 's';
                
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $error = 'Please enter your current password to change it.';
                    } elseif (strlen($new_password) < 6) {
                        $error = 'New password must be at least 6 characters long.';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'New passwords do not match.';
                    } else {
                        $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
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
                    
                    $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, $update_types, ...$update_values);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $success = 'Profile updated successfully!';
                            $stmt2 = mysqli_prepare($conn, "SELECT id, username, email, first_name, last_name, phone, role FROM users WHERE id = ?");
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
    $dashboard_url = '../dashboard-admin.php';
} elseif ($user_role == 'staff') {
    $dashboard_url = '../dashboard-staff.php';
} else {
    $dashboard_url = '../dashboard-student.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Ez2Learn</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .header {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            padding: 15px 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-text {
            font-size: 24px;
            font-weight: bold;
        }

        .back-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: white;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(49, 152, 248, 0.3);
        }

        .profile-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .profile-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3198F8;
            background: white;
            box-shadow: 0 0 0 4px rgba(49, 152, 248, 0.1);
        }

        .form-group input:disabled {
            background: #e9ecef;
            cursor: not-allowed;
        }

        .form-group input::placeholder {
            color: #999;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
        }

        .toggle-password:hover {
            color: #3198F8;
        }

        .password-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }

        .password-section h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
        }

        .btn-update {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(49, 152, 248, 0.4);
            margin-top: 20px;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(49, 152, 248, 0.5);
        }

        .btn-update:active {
            transform: translateY(0);
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
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

        .role-badge.staff {
            background: #e3f2fd;
            color: #1976d2;
        }

        .role-badge.student {
            background: #e8f5e9;
            color: #388e3c;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .profile-card {
                padding: 30px 20px;
            }

            .container {
                margin: 20px auto;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-text">Ez2Learn</div>
            <a href="<?php echo $dashboard_url; ?>" class="back-link">← Back to Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php 
                    $initial = !empty($user_data['first_name']) 
                        ? strtoupper(substr($user_data['first_name'], 0, 1)) 
                        : strtoupper(substr($user_data['username'] ?? 'U', 0, 1)); 
                    echo $initial; 
                    ?>
                </div>
                <h1>Edit Profile</h1>
                <p>
                    <?php echo htmlspecialchars($user_data['username'] ?? ''); ?>
                    <span class="role-badge <?php echo strtolower($user_data['role'] ?? 'student'); ?>">
                        <?php echo htmlspecialchars($user_data['role'] ?? 'Student'); ?>
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
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input 
                            type="text" 
                            id="first_name" 
                            name="first_name" 
                            placeholder="Enter your first name"
                            value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input 
                            type="text" 
                            id="last_name" 
                            name="last_name" 
                            placeholder="Enter your last name"
                            value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>"
                        disabled
                    >
                    <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">Username cannot be changed</small>
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

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        placeholder="Enter your phone number"
                        value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>"
                    >
                </div>

                <div class="password-section">
                    <h3>Change Password (Optional)</h3>
                    <p style="color: #666; font-size: 14px; margin-bottom: 20px;">Leave blank if you don't want to change your password</p>

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
            const email = document.getElementById('email').value.trim();
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;

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

