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

### 2. Database Configuration

**✅ Already Done!** The application now uses a shared database configuration file (`includes/db-config.php`) that automatically reads from environment variables.

The configuration supports:
- **DATABASE_URL** (Render's standard connection string format)
- Individual environment variables: **DB_HOST**, **DB_USER**, **DB_PASSWORD**, **DB_NAME**, **DB_PORT**
- Falls back to local development defaults if environment variables are not set

All PHP files have been updated to use this shared configuration.

### 3. Deploy Web Service on Render

1. Go to Render Dashboard → New → Web Service
2. Connect your GitHub repository
3. Configure:
   - **Name:** ez2learn (or your preferred name)
   - **Environment:** Docker
   - **Dockerfile Path:** `./Dockerfile`
   - **Docker Context:** `.` (root directory)

### 4. Set Environment Variables

**⚠️ IMPORTANT:** You MUST set environment variables in Render for the database connection to work!

In your Render web service settings, go to **Environment** and add these variables:

**Option A: Use DATABASE_URL (Recommended - Render's standard)**
1. In your Render dashboard, go to your **Database** service
2. Copy the **Internal Database URL** (format: `mysql://user:password@host:port/database`)
3. In your **Web Service** → **Environment**, add:
   ```
   DATABASE_URL=<paste-the-internal-database-url>
   ```

**Option B: Use Individual Variables**
Add these in your Render web service **Environment** settings:

```
DB_HOST=<your-database-host>
DB_USER=<your-database-user>
DB_PASSWORD=<your-database-password>
DB_NAME=<your-database-name>
DB_PORT=<your-database-port>  (optional, defaults to 3306 for MySQL)
```

**To find your database credentials:**
1. Go to your Database service in Render
2. Look for "Internal Database URL" or individual connection details
3. Use the **Internal** connection details (not External) - they work within Render's network

### 5. Test Database Connection

After setting environment variables, you can test the connection:
1. Visit `https://your-app.onrender.com/db-test.php`
2. This will show you what environment variables are detected
3. **Delete `db-test.php` after testing for security!**

**Note:** After adding/changing environment variables, you need to **redeploy** your web service for changes to take effect.

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

