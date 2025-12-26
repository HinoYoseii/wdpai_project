<?php

require_once 'Repository.php';

class TaskRepository extends Repository
{
    private static $instance; 
    
    public static function getInstance() { 
        return self::$instance ??= new TaskRepository(); 
    } 

    public function getTasks(): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM tasks;
        ');
        $stmt->execute();

        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $tasks;
    }

    public function getTasksByUserId(int $userId): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM tasks WHERE userid = :userId ORDER BY deadlinedate ASC
        ');
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $tasks ?: null;
    }

    public function getTask(int $taskId): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM tasks WHERE taskid = :taskId
        ');
        $stmt->bindParam(':taskId', $taskId, PDO::PARAM_INT);
        $stmt->execute();

        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task == false) {
            return null;
        }

        return $task;
    }

    public function getTasksByCategory(int $categoryId): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM tasks WHERE categoryid = :categoryId ORDER BY deadlinedate ASC
        ');
        $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->execute();

        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $tasks ?: null;
    }

    public function getUnfinishedTasks(int $userId): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM tasks WHERE userid = :userId AND isfinished = FALSE ORDER BY deadlinedate ASC
        ');
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $tasks ?: null;
    }

    public function getFinishedTasks(int $userId): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM tasks WHERE userid = :userId AND isfinished = TRUE ORDER BY deadlinedate DESC
        ');
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $tasks ?: null;
    }

    public function createTask(
        int $userId,
        ?int $categoryId,
        ?string $deadlineDate,
        string $taskDescription,
        int $fun,
        int $difficulty,
        int $importance,
        int $time
    ): void {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO tasks (userid, categoryid, deadlinedate, taskdescription, fun, difficulty, importance, time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $userId,
            $categoryId,
            $deadlineDate,
            $taskDescription,
            $fun,
            $difficulty,
            $importance,
            $time
        ]);
    }

    public function updateTask(
        int $taskId,
        ?int $categoryId,
        ?string $deadlineDate,
        string $taskDescription,
        int $fun,
        int $difficulty,
        int $importance,
        int $time
    ): void {
        $stmt = $this->database->connect()->prepare('
            UPDATE tasks 
            SET categoryid = ?, deadlinedate = ?, taskdescription = ?, fun = ?, difficulty = ?, importance = ?, time = ?
            WHERE taskid = ?
        ');
        $stmt->execute([
            $categoryId,
            $deadlineDate,
            $taskDescription,
            $fun,
            $difficulty,
            $importance,
            $time,
            $taskId
        ]);
    }

    public function markTaskAsFinished(int $taskId): void
    {
        $stmt = $this->database->connect()->prepare('
            UPDATE tasks SET isfinished = TRUE WHERE taskid = :taskId
        ');
        $stmt->bindParam(':taskId', $taskId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function markTaskAsUnfinished(int $taskId): void
    {
        $stmt = $this->database->connect()->prepare('
            UPDATE tasks SET isfinished = FALSE WHERE taskid = :taskId
        ');
        $stmt->bindParam(':taskId', $taskId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function deleteTask(int $taskId): void
    {
        $stmt = $this->database->connect()->prepare('
            DELETE FROM tasks WHERE taskid = :taskId
        ');
        $stmt->bindParam(':taskId', $taskId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function deleteFinishedTasksByUserId(int $userId): void
    {
        $stmt = $this->database->connect()->prepare('
            DELETE FROM tasks WHERE userid = :userId AND isfinished = TRUE
        ');
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }
}