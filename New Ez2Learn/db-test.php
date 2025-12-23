<?php
/**
 * Database Connection Test Page
 * Use this to diagnose database connection issues on Render
 * DELETE THIS FILE AFTER TESTING FOR SECURITY
 */

// Load database config
require_once 'includes/db-config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Database Connection Test</h1>
    
    <h2>Environment Variables</h2>
    <div class="info">
        <strong>DATABASE_URL:</strong> <?php echo getenv('DATABASE_URL') ? 'SET' : '<span class="error">NOT SET</span>'; ?><br>
        <strong>DB_HOST:</strong> <?php echo getenv('DB_HOST') ?: '<span class="error">NOT SET (using default: localhost)</span>'; ?><br>
        <strong>DB_USER:</strong> <?php echo getenv('DB_USER') ?: '<span class="error">NOT SET (using default: root)</span>'; ?><br>
        <strong>DB_PASSWORD:</strong> <?php echo getenv('DB_PASSWORD') ? 'SET' : '<span class="error">NOT SET (using default: empty)</span>'; ?><br>
        <strong>DB_NAME:</strong> <?php echo getenv('DB_NAME') ?: '<span class="error">NOT SET (using default: ez2learn)</span>'; ?><br>
        <strong>DB_PORT:</strong> <?php echo getenv('DB_PORT') ?: 'NOT SET (using default)'; ?>
    </div>
    
    <h2>Parsed Configuration</h2>
    <div class="info">
        <pre>
DB Host: <?php echo htmlspecialchars($db_host); ?>
DB User: <?php echo htmlspecialchars($db_user); ?>
DB Password: <?php echo $db_pass ? '***SET***' : 'NOT SET'; ?>
DB Name: <?php echo htmlspecialchars($db_name); ?>
        </pre>
    </div>
    
    <h2>Connection Test</h2>
    <?php
    $conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn) {
        echo '<p class="success">✓ Database connection successful!</p>';
        echo '<p>MySQL Version: ' . mysqli_get_server_info($conn) . '</p>';
        mysqli_close($conn);
    } else {
        echo '<p class="error">✗ Database connection failed!</p>';
        echo '<p>Error: ' . mysqli_connect_error() . '</p>';
        echo '<p>Error Number: ' . mysqli_connect_errno() . '</p>';
    }
    ?>
    
    <h2>All Environment Variables</h2>
    <div class="info">
        <pre><?php
        $env_vars = getenv();
        foreach ($env_vars as $key => $value) {
            if (stripos($key, 'DB') !== false || stripos($key, 'DATABASE') !== false) {
                echo htmlspecialchars("$key = " . (strpos($key, 'PASSWORD') !== false ? '***HIDDEN***' : $value)) . "\n";
            }
        }
        ?></pre>
    </div>
    
    <hr>
    <p><strong>Note:</strong> Delete this file after testing for security reasons.</p>
</body>
</html>

