-- Edit column to assignments table
USE ez2learn;

ALTER TABLE assignments ADD COLUMN status ENUM('open', 'closed') DEFAULT 'open' AFTER total_marks;