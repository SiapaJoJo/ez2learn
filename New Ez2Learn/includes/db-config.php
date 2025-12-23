<?php
/**
 * Database Configuration
 * Reads from environment variables for production (Render)
 * Falls back to local development values if not set
 */

// Check if DATABASE_URL is provided (Render's standard format)
if (getenv('DATABASE_URL')) {
    $url = parse_url(getenv('DATABASE_URL'));
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
    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_user = getenv('DB_USER') ?: 'root';
    $db_pass = getenv('DB_PASSWORD') ?: '';
    $db_name = getenv('DB_NAME') ?: 'ez2learn';
    
    // If DB_HOST includes port, use it as is
    // Otherwise, check for DB_PORT
    if (getenv('DB_PORT') && strpos($db_host, ':') === false) {
        $db_host .= ':' . getenv('DB_PORT');
    }
}

