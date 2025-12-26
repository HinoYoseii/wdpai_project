-- Users table
CREATE TABLE Users (
    userID SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    hashedPassword VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL
);

-- Categories table
CREATE TABLE Categories (
    categoryID SERIAL PRIMARY KEY,
    userID INTEGER NOT NULL,
    categoryName VARCHAR(255) NOT NULL,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);

-- Tasks table
CREATE TABLE Tasks (
    taskID SERIAL PRIMARY KEY,
    userID INTEGER NOT NULL,
    categoryID INTEGER,
    deadlineDate TIMESTAMP,
    taskDescription TEXT,
    fun INTEGER CHECK (fun >= 0 AND fun <= 100),
    difficulty INTEGER CHECK (difficulty >= 0 AND difficulty <= 100),
    importance INTEGER CHECK (importance >= 0 AND importance <= 100),
    time INTEGER CHECK (time >= 0 AND time <= 100),
    isFinished BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE,
    FOREIGN KEY (categoryID) REFERENCES Categories(categoryID) ON DELETE SET NULL
);

-- UserPreferences table
CREATE TABLE UserPreferences (
    userID INTEGER PRIMARY KEY,
    bio TEXT,
    deleteFinishedTasks BOOLEAN DEFAULT FALSE,
    funInfluence DECIMAL(3,2) DEFAULT 1.0,
    difficultyInfluence DECIMAL(3,2) DEFAULT 1.0,
    importanceInfluence DECIMAL(3,2) DEFAULT 1.0,
    timeInfluence DECIMAL(3,2) DEFAULT 1.0,
    deadlineInfluence DECIMAL(3,2) DEFAULT 1.0,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_tasks_user_id ON Tasks(userID);
CREATE INDEX idx_tasks_category_id ON Tasks(categoryID);
CREATE INDEX idx_tasks_deadline ON Tasks(deadlineDate);
CREATE INDEX idx_tasks_finished ON Tasks(isFinished);

-- Optional: Insert sample data
INSERT INTO Users (email, hashedPassword, username) VALUES
('john@example.com', '$2y$10$RgMy.4kAcKqSOTusL.fq9OJGHlH85mdRXeEKixz462T9WEboHEmU6', 'john_doe'),
('jane@example.com', '$2y$10$RgMy.4kAcKqSOTusL.fq9OJGHlH85mdRXeEKixz462T9WEboHEmU6', 'jane_smith');

INSERT INTO UserPreferences (userID, bio, deleteFinishedTasks) VALUES
(1, 'Software developer who loves productivity apps', false),
(2, 'Project manager focused on task optimization', true);

INSERT INTO Categories (userID, categoryName) VALUES
(1,'Work'), -- 1
(1,'Personal'), -- 2
(2,'Work'), -- 3
(2,'Health'); -- 4

INSERT INTO Tasks (userID, categoryID, taskDescription, deadlineDate, fun, difficulty, importance, time, isFinished) VALUES
(1, 1, 'Implement user authentication system', '2024-12-31 18:00:00', 70, 80, 90, 60, false),
(1, 2, 'Buy groceries for the week', '2024-12-20 20:00:00', 30, 20, 60, 60, false),
(2, 3, 'Weekly team meeting', '2024-12-18 10:00:00', 50, 30, 80, 60, true),
(2, 4, 'Morning workout session', '2024-12-19 07:00:00', 80, 60, 70, 45, false);