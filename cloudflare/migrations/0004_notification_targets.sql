ALTER TABLE notifications ADD COLUMN target_user_id INTEGER REFERENCES users(id);
-- Backfill old registration notices only when the display name is unambiguous.
-- Otherwise the UI safely links to the general pending-account list.
UPDATE notifications SET target_user_id = (
  SELECT CASE WHEN COUNT(*)=1 THEN MIN(users.id) ELSE NULL END
  FROM users WHERE notifications.message = 'New account waiting for approval: ' || users.name
) WHERE post_id IS NULL AND message LIKE 'New account waiting for approval:%';
