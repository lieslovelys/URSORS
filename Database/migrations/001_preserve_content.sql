-- Non-destructive: widen hierarchy fields and support reversible comment removal.
ALTER TABLE posts
    MODIFY section varchar(255) DEFAULT 'General',
    MODIFY sub_section varchar(255) DEFAULT NULL,
    MODIFY nested_sub_section varchar(255) DEFAULT NULL;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS is_deleted tinyint(1) NOT NULL DEFAULT 0;
