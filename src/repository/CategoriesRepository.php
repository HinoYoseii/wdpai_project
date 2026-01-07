<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Category.php';

class CategoriesRepository extends Repository
{
    private static $instance; 
    
    public static function getInstance() { 
        return self::$instance ??= new CategoriesRepository(); 
    }

    public function getCategoriesByUserId(int $userId): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM categories 
            WHERE userid = :userId 
            ORDER BY categoryname ASC
        ');
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: null;
    }

    public function getCategory(int $categoryId): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM categories WHERE categoryid = :categoryId
        ');
        $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createCategory(int $userId, string $categoryName): void
    {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO categories (userid, categoryname)
            VALUES (?, ?)
        ');
        $stmt->execute([$userId, $categoryName]);
    }

    public function updateCategory(int $categoryId, string $categoryName): void
    {
        $stmt = $this->database->connect()->prepare('
            UPDATE categories
            SET categoryname = ?
            WHERE categoryid = ?
        ');
        $stmt->execute([$categoryName, $categoryId]);
    }

    public function deleteCategory(int $categoryId): void
    {
        $stmt = $this->database->connect()->prepare('
            DELETE FROM categories WHERE categoryid = :categoryId
        ');
        $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function categoryExists(int $categoryId, int $userId): bool
    {
        $stmt = $this->database->connect()->prepare('
            SELECT COUNT(*) as count 
            FROM categories 
            WHERE categoryid = :categoryId AND userid = :userId
        ');
        $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}