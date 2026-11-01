CREATE TABLE notifications (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 user_id INTEGER NOT NULL REFERENCES users(id),
 message TEXT NOT NULL,
 post_id INTEGER REFERENCES posts(id),
 is_read INTEGER NOT NULL DEFAULT 0 CHECK(is_read IN (0,1)),
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX notifications_user ON notifications(user_id,is_read,id);
