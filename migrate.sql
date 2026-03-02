-- Run this SQL to update existing database with new columns
USE lapor_db;

-- Add category column
ALTER TABLE reports ADD COLUMN category VARCHAR(50) DEFAULT 'general' AFTER file_size;

-- Add priority column
ALTER TABLE reports ADD COLUMN priority VARCHAR(20) DEFAULT 'medium' AFTER category;

-- Add notes column
ALTER TABLE reports ADD COLUMN notes TEXT AFTER priority;

-- Add reviewed_by column
ALTER TABLE reports ADD COLUMN reviewed_by INT AFTER notes;

-- Add resolved_by column
ALTER TABLE reports ADD COLUMN resolved_by INT AFTER reviewed_by;

-- Add foreign keys (ignore errors if they already exist)
ALTER TABLE reports ADD CONSTRAINT fk_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE reports ADD CONSTRAINT fk_resolved_by FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL;
