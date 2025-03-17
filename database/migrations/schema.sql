-- Enable foreign key constraints
PRAGMA foreign_keys = ON;

-- Users table
CREATE TABLE IF NOT EXISTS users (
                                     id INTEGER PRIMARY KEY AUTOINCREMENT,
                                     username TEXT UNIQUE NOT NULL,
                                     api_token TEXT UNIQUE NOT NULL,
                                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Groups table
CREATE TABLE IF NOT EXISTS groups (
                                      id INTEGER PRIMARY KEY AUTOINCREMENT,
                                      name TEXT NOT NULL,
                                      description TEXT,
                                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                      created_by INTEGER,
                                      FOREIGN KEY (created_by) REFERENCES users (id)
    );

-- Group members table
CREATE TABLE IF NOT EXISTS group_members (
                                             group_id INTEGER,
                                             user_id INTEGER,
                                             joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                             PRIMARY KEY (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    );

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        group_id INTEGER NOT NULL,
                                        user_id INTEGER NOT NULL,
                                        content TEXT NOT NULL,
                                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    );

-- Indexes for faster lookups
CREATE INDEX IF NOT EXISTS idx_messages_group_id ON messages (group_id);
CREATE INDEX IF NOT EXISTS idx_messages_created_at ON messages (created_at);
CREATE INDEX IF NOT EXISTS idx_group_members_user_id ON group_members (user_id);