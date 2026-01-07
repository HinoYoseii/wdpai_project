CREATE TABLE Users (
    userID SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    hashedPassword VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE Categories (
    categoryID SERIAL PRIMARY KEY,
    userID INTEGER NOT NULL,
    categoryName VARCHAR(255) NOT NULL,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);

CREATE TABLE Tasks (
    taskID SERIAL PRIMARY KEY,
    userID INTEGER NOT NULL,
    categoryID INTEGER,
    title VARCHAR(255) NOT NULL DEFAULT '',
    taskDescription TEXT,
    deadlineDate TIMESTAMP,
    fun VARCHAR(20) CHECK (fun IN ('low', 'medium', 'high')),
    difficulty VARCHAR(20) CHECK (difficulty IN ('low', 'medium', 'high')),
    importance VARCHAR(20) CHECK (importance IN ('low', 'medium', 'high')),
    time VARCHAR(20) CHECK (time IN ('short', 'medium', 'long')),
    isFinished BOOLEAN DEFAULT FALSE,
    isPinned BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE,
    FOREIGN KEY (categoryID) REFERENCES Categories(categoryID) ON DELETE SET NULL
);

CREATE TABLE UserPreferences (
    userID INTEGER PRIMARY KEY,
    bio TEXT,
    funInfluence DECIMAL(3,2) DEFAULT 1.0,
    difficultyInfluence DECIMAL(3,2) DEFAULT 1.0,
    importanceInfluence DECIMAL(3,2) DEFAULT 1.0,
    timeInfluence DECIMAL(3,2) DEFAULT 1.0,
    deadlineInfluence DECIMAL(3,2) DEFAULT 1.0,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);

CREATE INDEX idx_tasks_user_id ON Tasks(userID);
CREATE INDEX idx_tasks_category_id ON Tasks(categoryID);
CREATE INDEX idx_tasks_deadline ON Tasks(deadlineDate);
CREATE INDEX idx_tasks_finished ON Tasks(isFinished);
CREATE INDEX idx_tasks_pinned ON Tasks(isPinned);

INSERT INTO Users (email, hashedPassword, username) VALUES
('john@example.com', '$2y$10$RgMy.4kAcKqSOTusL.fq9OJGHlH85mdRXeEKixz462T9WEboHEmU6', 'john_doe'),
('jane@example.com', '$2y$10$RgMy.4kAcKqSOTusL.fq9OJGHlH85mdRXeEKixz462T9WEboHEmU6', 'jane_smith');

INSERT INTO UserPreferences (userID, bio) VALUES
(1, 'Software developer who loves productivity apps'),
(2, 'Project manager focused on task optimization');

INSERT INTO Categories (userID, categoryName) VALUES
(1,'Work'), -- 1
(1,'Personal'), -- 2
(2,'Work'), -- 3
(2,'Health'); -- 4

INSERT INTO Tasks (userID, categoryID, title, taskDescription, deadlineDate, fun, difficulty, importance, time, isFinished, isPinned) VALUES
(1, 1, 'Implement user authentication system', 'Implement user authentication system', '2024-12-31 18:00:00', 'high', 'high', 'high', 'long', false, false),
(1, 2, 'Buy groceries for the week', 'Buy groceries for the week', '2024-12-20 20:00:00', 'low', 'low', 'medium', 'long', false, false),
(2, 3, 'Weekly team meeting', 'Weekly team meeting', '2024-12-18 10:00:00', 'medium', 'low', 'high', 'long', true, false),
(2, 4, 'Morning workout session', 'Morning workout session', '2024-12-19 07:00:00', 'high', 'medium', 'high', 'medium', false, true);