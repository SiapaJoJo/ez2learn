<?php
/**
 * Database Connection File
 * Centralized database connection for Ez2Learn
 */

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ez2learn';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, 'utf8mb4');
?>
