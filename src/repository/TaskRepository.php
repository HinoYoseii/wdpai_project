<?php
require_once 'Repository.php';

class TaskRepository extends Repository
{
    private static $instance; 
    
    public static function getInstance() { 
        return self::$instance ??= new TaskRepository(); 
    } 

    private function getBaseQuery(): string {
        return '
            SELECT t.*, c.categoryname 
            FROM tasks t
            LEFT JOIN categories c ON t.categoryid = c.categoryid
        ';
    }

    public function getTasks(): ?array
    {
        $stmt = $this->database->connect()->prepare($this->getBaseQuery());
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: null;
    }

    public function getTasksByUserId(int $userId, ?bool $isFinished = null): ?array
    {
        $query = $this->getBaseQuery() . ' WHERE t.userid = :userId';
        
        if ($isFinished !== null) {
            $query .= ' AND t.isfinished = :isFinished';
        }
        
        $query .= ' ORDER BY t.ispinned DESC, t.deadlinedate ASC';
        
        $stmt = $this->database->connect()->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        
        if ($isFinished !== null) {
            $stmt->bindParam(':isFinished', $isFinished, PDO::PARAM_BOOL);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: null;
    }

    public function getUnfinishedTasks(int $userId): ?array
    {
        return $this->getTasksByUserId($userId, false);
    }

    public function getFinishedTasks(int $userId): ?array
    {
        $query = $this->getBaseQuery() . '
            WHERE t.userid = :userId AND t.isfinished = TRUE 
            ORDER BY t.deadlinedate DESC
        ';
        
        $stmt = $this->database->connect()->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: null;
    }

    public function getTask(int $taskId): ?array
    {
        $query = $this->getBaseQuery() . ' WHERE t.taskid = :taskId';
        
        $stmt = $this->database->connect()->prepare($query);
        $stmt->bindParam(':taskId', $taskId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getTasksByCategory(int $categoryId): ?array
    {
        $query = $this->getBaseQuery() . '
            WHERE t.categoryid = :categoryId 
            ORDER BY t.ispinned DESC, t.deadlinedate ASC
        ';
        
        $stmt = $this->database->connect()->prepare($query);
        $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: null;
    }

    public function createTask(
        int $userId,
        ?int $categoryId,
        ?string $deadlineDate,
        string $title,
        ?string $taskDescription,
        string $fun,
        string $difficulty,
        string $importance,
        string $time
    ): void {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO tasks (userid, categoryid, deadlinedate, title, taskdescription, fun, difficulty, importance, time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $userId,
            $categoryId,
            $deadlineDate,
            $title,
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
        string $title,
        ?string $taskDescription,
        string $fun,
        string $difficulty,
        string $importance,
        string $time
    ): void {
        $stmt = $this->database->connect()->prepare('
            UPDATE tasks 
            SET categoryid = ?, deadlinedate = ?, title = ?, taskdescription = ?, 
                fun = ?, difficulty = ?, importance = ?, time = ?
            WHERE taskid = ?
        ');
        $stmt->execute([
            $categoryId,
            $deadlineDate,
            $title,
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
        $this->updateTaskFinishStatus($taskId, true);
    }

    public function markTaskAsUnfinished(int $taskId): void
    {
        $this->updateTaskFinishStatus($taskId, false);
    }

    private function updateTaskFinishStatus(int $taskId, bool $isFinished): void
    {
        $stmt = $this->database->connect()->prepare('
            UPDATE tasks SET isfinished = :isFinished WHERE taskid = :taskId
        ');
        $stmt->bindParam(':isFinished', $isFinished, PDO::PARAM_BOOL);
        $stmt->bindParam(':taskId', $taskId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function updateTaskPinStatus(int $taskId, bool $isPinned): void
    {
        $stmt = $this->database->connect()->prepare('
            UPDATE tasks SET ispinned = :isPinned WHERE taskid = :taskId
        ');
        $stmt->bindParam(':isPinned', $isPinned, PDO::PARAM_BOOL);
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
}