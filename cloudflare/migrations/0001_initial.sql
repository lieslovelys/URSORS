PRAGMA foreign_keys = ON;
CREATE TABLE users (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 username TEXT NOT NULL COLLATE NOCASE UNIQUE,
 email TEXT NOT NULL COLLATE NOCASE UNIQUE,
 name TEXT NOT NULL,
 password TEXT NOT NULL,
 role TEXT NOT NULL CHECK(role IN ('student','teacher','admin')),
 approved INTEGER NOT NULL DEFAULT 0 CHECK(approved IN (0,1)),
 can_post INTEGER NOT NULL DEFAULT 1 CHECK(can_post IN (0,1)),
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE sessions (
 token_hash TEXT PRIMARY KEY,
 user_id INTEGER NOT NULL REFERENCES users(id),
 expires INTEGER NOT NULL
);
CREATE INDEX sessions_expiry ON sessions(expires);
CREATE TABLE attempts (
 bucket TEXT PRIMARY KEY,
 count INTEGER NOT NULL,
 expires INTEGER NOT NULL
);
CREATE TABLE posts (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 user_id INTEGER NOT NULL REFERENCES users(id),
 content TEXT NOT NULL,
 resource_url TEXT,
 category TEXT NOT NULL,
 subject TEXT NOT NULL,
 audience TEXT NOT NULL CHECK(audience IN ('general','professor')),
 archived INTEGER NOT NULL DEFAULT 0 CHECK(archived IN (0,1)),
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX posts_feed ON posts(archived,category,subject,audience,id);
CREATE TABLE comments (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 post_id INTEGER NOT NULL REFERENCES posts(id),
 user_id INTEGER NOT NULL REFERENCES users(id),
 content TEXT NOT NULL,
 deleted INTEGER NOT NULL DEFAULT 0,
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX comments_post ON comments(post_id,id);
CREATE TABLE likes (
 post_id INTEGER NOT NULL REFERENCES posts(id),
 user_id INTEGER NOT NULL REFERENCES users(id),
 PRIMARY KEY(post_id,user_id)
);
