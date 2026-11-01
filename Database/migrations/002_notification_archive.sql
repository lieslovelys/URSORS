-- Preserve notifications while allowing each owner to archive and restore them.
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS is_archived tinyint(1) NOT NULL DEFAULT 0;
