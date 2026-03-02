-- Run this SQL to update existing database with new columns
USE lapor_db;

-- Add new columns if they don't exist
ALTER TABLE reports 
ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'general' AFTER file_size,
ADD COLUMN IF NOT EXISTS priority VARCHAR(20) DEFAULT 'medium' AFTER category,
ADD COLUMN IF NOT EXISTS notes TEXT AFTER priority,
ADD COLUMN IF NOT EXISTS reviewed_by INT AFTER notes,
ADD COLUMN IF NOT EXISTS resolved_by INT AFTER reviewed_by;

-- Add foreign keys if they don't exist
ALTER TABLE reports 
ADD CONSTRAINT FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
ADD CONSTRAINT FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL;
