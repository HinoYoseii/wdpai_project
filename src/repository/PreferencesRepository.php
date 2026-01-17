<?php

require_once 'Repository.php';

class PreferencesRepository extends Repository
{
    private static $instance; 
    
    public static function getInstance() { 
        return self::$instance ??= new PreferencesRepository(); 
    }

    public function getPreferences(int $userId): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM userpreferences WHERE userid = :userId
        ');
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $preferences = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($preferences == false) {
            return null;
        }

        return $preferences;
    }

    public function updatePreferences(
        int $userId,
        bool $deleteFinishedTasks,
        float $funInfluence,
        float $difficultyInfluence,
        float $importanceInfluence,
        float $timeInfluence,
        float $deadlineInfluence
    ): void {
        $stmt = $this->database->connect()->prepare('
            UPDATE userpreferences
            SET deletefinishedtasks = ?,
                funinfluence = ?, 
                difficultyinfluence = ?, 
                importanceinfluence = ?, 
                timeinfluence = ?, 
                deadlineinfluence = ?
            WHERE userid = ?
        ');
        $stmt->execute([
            $deleteFinishedTasks,
            $funInfluence,
            $difficultyInfluence,
            $importanceInfluence,
            $timeInfluence,
            $deadlineInfluence,
            $userId
        ]);
    }
}