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

    public function getPreferencesObject(int $userId): ?Preferences
    {
        $prefs = $this->getPreferences($userId);

        if (!$prefs) {
            return null;
        }

        return new Preferences(
            $prefs['userid'],
            $prefs['bio'],
            (float)$prefs['funinfluence'],
            (float)$prefs['difficultyinfluence'],
            (float)$prefs['importanceinfluence'],
            (float)$prefs['timeinfluence'],
            (float)$prefs['deadlineinfluence']
        );
    }

    public function createPreferences(
        int $userId,
        ?string $bio = null,
        float $funInfluence = 1.0,
        float $difficultyInfluence = 1.0,
        float $importanceInfluence = 1.0,
        float $timeInfluence = 1.0,
        float $deadlineInfluence = 1.0
    ): void {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO userpreferences 
            (userid, bio, funinfluence, difficultyinfluence, importanceinfluence, timeinfluence, deadlineinfluence) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $userId,
            $bio,
            $funInfluence,
            $difficultyInfluence,
            $importanceInfluence,
            $timeInfluence,
            $deadlineInfluence
        ]);
    }

    public function updatePreferences(
        int $userId,
        ?string $bio,
        float $funInfluence,
        float $difficultyInfluence,
        float $importanceInfluence,
        float $timeInfluence,
        float $deadlineInfluence
    ): void {
        $stmt = $this->database->connect()->prepare('
            UPDATE userpreferences 
            SET bio = ?, 
                funinfluence = ?, 
                difficultyinfluence = ?, 
                importanceinfluence = ?, 
                timeinfluence = ?, 
                deadlineinfluence = ?
            WHERE userid = ?
        ');
        $stmt->execute([
            $bio,
            $funInfluence,
            $difficultyInfluence,
            $importanceInfluence,
            $timeInfluence,
            $deadlineInfluence,
            $userId
        ]);
    }

    public function updateInfluences(
        int $userId,
        float $funInfluence,
        float $difficultyInfluence,
        float $importanceInfluence,
        float $timeInfluence,
        float $deadlineInfluence
    ): void {
        $stmt = $this->database->connect()->prepare('
            UPDATE userpreferences 
            SET funinfluence = ?, 
                difficultyinfluence = ?, 
                importanceinfluence = ?, 
                timeinfluence = ?, 
                deadlineinfluence = ?
            WHERE userid = ?
        ');
        $stmt->execute([
            $funInfluence,
            $difficultyInfluence,
            $importanceInfluence,
            $timeInfluence,
            $deadlineInfluence,
            $userId
        ]);
    }

    public function updateBio(int $userId, ?string $bio): void
    {
        $stmt = $this->database->connect()->prepare('
            UPDATE userpreferences 
            SET bio = ?
            WHERE userid = ?
        ');
        $stmt->execute([$bio, $userId]);
    }

    public function deletePreferences(int $userId): void
    {
        $stmt = $this->database->connect()->prepare('
            DELETE FROM userpreferences WHERE userid = :userId
        ');
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function preferencesExist(int $userId): bool
    {
        $stmt = $this->database->connect()->prepare('
            SELECT COUNT(*) as count FROM userpreferences WHERE userid = :userId
        ');
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    public function ensurePreferencesExist(int $userId): void
    {
        if (!$this->preferencesExist($userId)) {
            $this->createPreferences($userId);
        }
    }
}