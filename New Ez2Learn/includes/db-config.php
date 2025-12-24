<?php
/**
 * Database Configuration
 * Reads from environment variables for production (Render)
 * Falls back to local development values if not set
 */

// Try $_ENV first (more reliable in some PHP configurations), then getenv()
$getEnv = function($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
};

// Check if DATABASE_URL is provided (Render's standard format)
$database_url = $getEnv('DATABASE_URL');
if ($database_url) {
    $url = parse_url($database_url);
    $db_host = $url['host'] ?? 'localhost';
    $db_user = $url['user'] ?? 'root';
    $db_pass = $url['pass'] ?? '';
    $db_name = isset($url['path']) ? trim($url['path'], '/') : 'ez2learn';
    
    // Add port if specified
    if (isset($url['port'])) {
        $db_host .= ':' . $url['port'];
    }
} else {
    // Use individual environment variables or fall back to defaults
    $db_host = $getEnv('DB_HOST', 'localhost');
    $db_user = $getEnv('DB_USER', 'root');
    $db_pass = $getEnv('DB_PASSWORD', '');
    $db_name = $getEnv('DB_NAME', 'ez2learn');
    
    // If DB_HOST includes port, use it as is
    // Otherwise, check for DB_PORT
    $db_port = $getEnv('DB_PORT');
    if ($db_port && strpos($db_host, ':') === false) {
        $db_host .= ':' . $db_port;
    }
}

// Create database connection
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, 'utf8mb4');

// Debug mode: Uncomment to see what values are being used (remove in production!)
// error_log("DB Config - Host: $db_host, User: $db_user, DB: $db_name");

