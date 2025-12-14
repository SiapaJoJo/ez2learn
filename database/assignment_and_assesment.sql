
-- ASSIGNMENTS TABLE

CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    due_date DATETIME NOT NULL,
    total_marks INT NOT NULL,
    created_by INT NOT NULL, -- staff user id
    status ENUM('draft', 'published', 'closed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_assignments_staff
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_due_date (due_date),
    INDEX idx_created_by (created_by),
    INDEX idx_status (status)
) ENGINE=InnoDB;


-- ASSIGNMENT SUBMISSIONS

CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    marks_awarded INT NULL,
    feedback TEXT NULL,
    submission_status ENUM('submitted', 'graded') DEFAULT 'submitted',

    CONSTRAINT fk_submission_assignment
        FOREIGN KEY (assignment_id) REFERENCES assignments(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_submission_student
        FOREIGN KEY (student_id) REFERENCES users(id)
        ON DELETE CASCADE,

    UNIQUE KEY uniq_assignment_student (assignment_id, student_id),
    INDEX idx_student (student_id)
) ENGINE=InnoDB;
