-- Uzytkownicy
CREATE TABLE Users (
    userID SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    hashedPassword VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    userRole VARCHAR(20) CHECK (userRole IN ('user', 'admin')) DEFAULT 'user' NOT NULL
);

-- Kategorie
CREATE TABLE Categories (
    categoryID SERIAL PRIMARY KEY,
    userID INTEGER NOT NULL,
    categoryName VARCHAR(255) NOT NULL,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);

-- Zadania (1=low, 2=medium, 3=high)
CREATE TABLE Tasks (
    taskID SERIAL PRIMARY KEY,
    userID INTEGER NOT NULL,
    categoryID INTEGER,
    title VARCHAR(255) NOT NULL DEFAULT '',
    taskDescription TEXT,
    deadlineDate TIMESTAMP,
    fun SMALLINT CHECK (fun IN (1, 2, 3)),
    difficulty SMALLINT CHECK (difficulty IN (1, 2, 3)),
    importance SMALLINT CHECK (importance IN (1, 2, 3)),
    time SMALLINT CHECK (time IN (1, 2, 3)),
    isFinished BOOLEAN DEFAULT FALSE,
    isPinned BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE,
    FOREIGN KEY (categoryID) REFERENCES Categories(categoryID) ON DELETE SET NULL
);

-- Preferencje uzytkownika
CREATE TABLE UserPreferences (
    userID INTEGER PRIMARY KEY,
    deleteFinishedTasks BOOLEAN DEFAULT FALSE,
    funInfluence DECIMAL(3,2) DEFAULT 1.0 CHECK (funInfluence BETWEEN 0 AND 2),
    difficultyInfluence DECIMAL(3,2) DEFAULT 1.0 CHECK (difficultyInfluence BETWEEN 0 AND 2),
    importanceInfluence DECIMAL(3,2) DEFAULT 1.0 CHECK (importanceInfluence BETWEEN 0 AND 2),
    timeInfluence DECIMAL(3,2) DEFAULT 1.0 CHECK (timeInfluence BETWEEN 0 AND 2),
    deadlineInfluence DECIMAL(3,2) DEFAULT 1.0 CHECK (deadlineInfluence BETWEEN 0 AND 2),
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);

CREATE INDEX idx_tasks_user_id ON Tasks(userID);
CREATE INDEX idx_tasks_category_id ON Tasks(categoryID);
CREATE INDEX idx_tasks_deadline ON Tasks(deadlineDate);
CREATE INDEX idx_tasks_finished ON Tasks(isFinished);
CREATE INDEX idx_tasks_pinned ON Tasks(isPinned);

-- Automatycznie tworzy domyślne preferencje użytkownika
CREATE OR REPLACE FUNCTION create_user_preferences()
RETURNS TRIGGER AS $$
BEGIN
    -- Tworzy preferencje tylko dla uzytkowników, nie dla adminów
    IF NEW.userRole = 'user' THEN
        INSERT INTO UserPreferences (userID)
        VALUES (NEW.userID);
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Usuwa ukończone zadania jeżeli takie preferencje ma użytkownik
CREATE OR REPLACE FUNCTION auto_delete_finished_tasks()
RETURNS TRIGGER AS $$
DECLARE
    should_delete BOOLEAN;
BEGIN
    SELECT deleteFinishedTasks INTO should_delete
    FROM UserPreferences
    WHERE userID = NEW.userID;
    
    IF should_delete AND NEW.isFinished = TRUE AND OLD.isFinished = FALSE THEN
        DELETE FROM Tasks WHERE taskID = NEW.taskID;
        RETURN NULL; -- Powstrzymuje przed aktualizacją zadania
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Przy tworzeniu użytkownika automatycznie tworzy jego preferencje
CREATE TRIGGER trigger_create_user_preferences
    AFTER INSERT ON Users
    FOR EACH ROW
    EXECUTE FUNCTION create_user_preferences();

-- Wywołuje usuwanie ukończonego zadania w zależności od preferencji użytkownika
CREATE TRIGGER trigger_auto_delete_finished_tasks
    BEFORE UPDATE ON Tasks
    FOR EACH ROW
    WHEN (NEW.isFinished IS DISTINCT FROM OLD.isFinished)
    EXECUTE FUNCTION auto_delete_finished_tasks();

-- Widok wyświetla tylko aktywne zadania
CREATE OR REPLACE VIEW active_tasks AS
SELECT * FROM Tasks
WHERE isFinished = FALSE;

-- Widok wyświetla tylko zakończone zadania
CREATE OR REPLACE VIEW finished_tasks AS
SELECT * FROM Tasks
WHERE isFinished = TRUE;

-- Przykładowe dane
INSERT INTO Users (email, hashedPassword, username, userRole) VALUES
('john@example.com', '$2y$10$RgMy.4kAcKqSOTusL.fq9OJGHlH85mdRXeEKixz462T9WEboHEmU6', 'john_doe', 'user'),
('jane@example.com', '$2y$10$RgMy.4kAcKqSOTusL.fq9OJGHlH85mdRXeEKixz462T9WEboHEmU6', 'jane_smith', 'user'),
('admin@admin.com', '$2y$10$RgMy.4kAcKqSOTusL.fq9OJGHlH85mdRXeEKixz462T9WEboHEmU6', 'admin_user', 'admin');

INSERT INTO Categories (userID, categoryName) VALUES
(1, 'Work'),
(1, 'Personal'),
(1, 'Learning'),
(1, 'Side Projects'),
(2, 'Work'),
(2, 'Health'),
(2, 'Family');

INSERT INTO Tasks (userID, categoryID, title, taskDescription, deadlineDate, fun, difficulty, importance, time, isFinished, isPinned) VALUES
(1, 1, 'Implement user authentication system', 'Build JWT-based authentication with refresh tokens', '2026-01-25 18:00:00', 3, 3, 3, 3, FALSE, TRUE),
(1, 1, 'Fix critical bug in payment module', 'Users reporting failed transactions', '2026-01-13 12:00:00', 1, 3, 3, 2, FALSE, TRUE),
(1, 1, 'Code review for new feature', 'Review pull request #234', '2026-01-14 16:00:00', 2, 2, 2, 2, FALSE, FALSE),
(1, 1, 'Update API documentation', 'Document new endpoints for v2.0', '2026-01-20 17:00:00', 1, 1, 2, 3, FALSE, FALSE),
(1, 1, 'Refactor database queries', 'Optimize slow queries in analytics module', '2026-01-30 18:00:00', 2, 3, 2, 3, FALSE, FALSE),
(1, 2, 'Buy groceries for the week', 'Milk, eggs, bread, vegetables', '2026-01-13 20:00:00', 1, 1, 2, 1, FALSE, FALSE),
(1, 2, 'Schedule dentist appointment', 'Regular checkup needed', '2026-01-15 12:00:00', 1, 1, 2, 1, FALSE, FALSE),
(1, 2, 'Plan weekend trip', 'Research hotels and activities', '2026-01-18 19:00:00', 3, 1, 1, 2, FALSE, FALSE),
(1, 3, 'Complete React advanced course', 'Finish modules 8-12', '2026-02-01 23:59:59', 3, 2, 2, 3, FALSE, FALSE),
(1, 3, 'Read "Clean Code" book', 'Currently on chapter 5', '2026-01-31 23:59:59', 2, 1, 2, 3, FALSE, FALSE),
(1, 4, 'Launch personal blog', 'Set up hosting and design', '2026-02-15 23:59:59', 3, 2, 2, 3, FALSE, FALSE),
(1, 4, 'Build portfolio website', 'Showcase recent projects', '2026-03-01 23:59:59', 3, 3, 2, 3, FALSE, FALSE),
(1, 1, 'Team standup meeting', 'Daily sync with the team', '2026-01-11 09:00:00', 2, 1, 2, 1, TRUE, FALSE),
(1, 2, 'Water plants', 'Weekly watering routine', '2026-01-10 18:00:00', 2, 1, 1, 1, TRUE, FALSE),

(2, 5, 'Weekly team meeting', 'Discuss Q1 objectives and KPIs', '2026-01-13 10:00:00', 2, 1, 3, 2, FALSE, FALSE),
(2, 5, 'Prepare quarterly report', 'Compile data from all departments', '2026-01-17 17:00:00', 1, 3, 3, 3, FALSE, TRUE),
(2, 5, 'Interview candidates', 'Three interviews scheduled', '2026-01-15 14:00:00', 2, 2, 3, 3, FALSE, FALSE),
(2, 5, 'Budget review meeting', 'Review spending for January', '2026-01-16 11:00:00', 1, 2, 3, 2, FALSE, FALSE),
(2, 5, 'Update project timeline', 'Adjust milestones based on progress', '2026-01-14 16:00:00', 2, 2, 2, 2, FALSE, FALSE),
(2, 6, 'Morning workout session', '30 min cardio + strength training', '2026-01-13 07:00:00', 3, 2, 3, 1, FALSE, TRUE),
(2, 6, 'Yoga class', 'Evening relaxation session', '2026-01-14 18:30:00', 3, 1, 2, 1, FALSE, FALSE),
(2, 6, 'Meal prep for the week', 'Prepare healthy lunches', '2026-01-12 15:00:00', 2, 2, 3, 2, TRUE, FALSE),
(2, 7, 'Kids soccer practice', 'Pick up from school and drive to field', '2026-01-13 16:00:00', 2, 1, 3, 1, FALSE, FALSE),
(2, 7, 'Family movie night', 'Choose and watch movie together', '2026-01-13 19:00:00', 3, 1, 2, 2, FALSE, FALSE),
(2, 7, 'Plan daughter birthday party', 'Book venue and send invitations', '2026-01-20 23:59:59', 2, 2, 3, 3, FALSE, FALSE);