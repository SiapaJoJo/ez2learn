CREATE DATABASE IF NOT EXISTS ez2learn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ez2learn;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'staff', 'admin') NOT NULL DEFAULT 'student',
    first_name VARCHAR(50) NULL,
    last_name VARCHAR(50) NULL,
    phone VARCHAR(20) NULL,
    profile_image VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username, email, password, role, first_name, last_name, status) VALUES 
('admin', 'admin@ez2learn.com', '$2y$10$oF4CCiANw3XH9Ht5Uliv8OPXVjLfvw1d9rpeBT9j2iwqsg.i0CGIm', 'admin', 'System', 'Administrator', 'active')
ON DUPLICATE KEY UPDATE username=username;

