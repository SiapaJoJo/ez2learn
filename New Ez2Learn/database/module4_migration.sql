-- ==========================================
-- Module 4: Progress Tracking Migration
-- Run this script to add Module 4 tables and indexes
-- ==========================================

USE ez2learn;

-- Table: material_progress (tracks individual material completions)
CREATE TABLE IF NOT EXISTS material_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    material_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_material (student_id, material_id),
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(material_id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_material (material_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: progress_update_logs (audit trail for reliability)
CREATE TABLE IF NOT EXISTS progress_update_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    event_type ENUM('material_complete', 'quiz_attempt', 'assignment_submit', 'manual_reset', 'recalculate') NOT NULL,
    ref_id INT DEFAULT NULL COMMENT 'Material/Quiz/Assignment ID',
    payload_json TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'success', 'failed', 'retried') DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    INDEX idx_student_course (student_id, course_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: certificates (tracks certificate issuance)
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    certificate_code VARCHAR(50) UNIQUE NOT NULL,
    UNIQUE KEY unique_student_course_cert (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes to existing tables for performance
ALTER TABLE progress ADD INDEX IF NOT EXISTS idx_student_course (student_id, course_id);
ALTER TABLE quiz_attempts ADD INDEX IF NOT EXISTS idx_student_quiz (student_id, quiz_id);
ALTER TABLE submissions ADD INDEX IF NOT EXISTS idx_student_assignment (student_id, assignment_id);

-- Ensure progress table has proper structure
ALTER TABLE progress MODIFY completed_percentage INT DEFAULT 0;
ALTER TABLE progress MODIFY last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
