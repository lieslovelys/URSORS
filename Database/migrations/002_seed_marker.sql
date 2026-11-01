-- Non-destructive: existing posts keep NULL and remain ordinary contributions.
ALTER TABLE posts ADD COLUMN IF NOT EXISTS seed_marker VARCHAR(191) NULL DEFAULT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS posts_seed_marker_unique ON posts (seed_marker);
