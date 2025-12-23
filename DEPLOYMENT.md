# Ez2Learn Deployment Guide for Render

## Prerequisites
- GitHub repository with your code
- Render account

## Deployment Steps

### 1. Database Setup on Render

1. In your Render dashboard, create a new **PostgreSQL** or **MySQL** database
2. Note down the following connection details:
   - Database Host
   - Database Name
   - Database User
   - Database Password
   - Database Port

### 2. Update Database Configuration

**Important:** Your application currently uses hardcoded database credentials. You'll need to update the database connection in your PHP files to use environment variables or the Render database connection string.

The database connection is currently in files like:
- `login.php`
- `register.php`
- And other PHP files that connect to the database

**Option A: Quick Fix (Update each file)**
Replace the database connection code:
```php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ez2learn';
```

With:
```php
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME') ?: 'ez2learn';
```

**Option B: Create a config file (Recommended)**
Create `includes/config.php`:
```php
<?php
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME') ?: 'ez2learn';
?>
```

Then include it in your files: `require_once 'includes/config.php';`

### 3. Deploy Web Service on Render

1. Go to Render Dashboard → New → Web Service
2. Connect your GitHub repository
3. Configure:
   - **Name:** ez2learn (or your preferred name)
   - **Environment:** Docker
   - **Dockerfile Path:** `./Dockerfile`
   - **Docker Context:** `.` (root directory)

### 4. Set Environment Variables

In your Render web service settings, add these environment variables:

```
DB_HOST=<your-database-host>
DB_USER=<your-database-user>
DB_PASSWORD=<your-database-password>
DB_NAME=<your-database-name>
```

### 5. Initialize Database

After deployment, you need to run the SQL scripts to create tables:

1. Connect to your Render database
2. Run the SQL from `New Ez2Learn/database/database.sql` to create tables
3. Optionally run `New Ez2Learn/database/insert.sql` to insert initial data

### 6. File Uploads

The `uploads` directory is created in the Dockerfile, but for persistent storage on Render, consider:
- Using Render Disk for persistent storage, OR
- Using a cloud storage service (AWS S3, Cloudinary, etc.)

## Alternative: Use Render PHP Buildpack (No Dockerfile)

If you prefer not to use Docker:

1. In Render, select **Web Service**
2. Choose **PHP** as the environment (not Docker)
3. Set **Root Directory** to: `New Ez2Learn`
4. Set **Build Command:** (leave empty or use `composer install` if you have composer.json)
5. Set **Start Command:** `php -S 0.0.0.0:$PORT -t .`

Note: You'll still need to update database connections and set environment variables.

## Troubleshooting

- **Database Connection Errors:** Verify environment variables are set correctly
- **File Upload Issues:** Check directory permissions (should be 755)
- **404 Errors:** Ensure Apache mod_rewrite is enabled (already in Dockerfile)

