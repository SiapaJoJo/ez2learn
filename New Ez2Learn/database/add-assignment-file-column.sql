-- Add instruction_file column to assignments table
USE ez2learn;

ALTER TABLE assignments 
ADD COLUMN instruction_file VARCHAR(255) NULL AFTER total_marks;
