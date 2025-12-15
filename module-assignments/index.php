<?php
// Redirect based on user role
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../module-usermanagement/login.php');
    exit();
}

$role = strtolower($_SESSION['role'] ?? '');

if ($role === 'staff') {
    header('Location: staff-assignments.php');
} elseif ($role === 'student') {
    header('Location: student-assignments.php');
} else {
    header('Location: ../module-usermanagement/dashboard-admin.php');
}
exit();
