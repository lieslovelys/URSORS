ALTER TABLE users ADD COLUMN avatar TEXT NOT NULL DEFAULT 'default'
  CHECK(avatar IN ('default','blue','teal','purple','rose','amber','slate'));
