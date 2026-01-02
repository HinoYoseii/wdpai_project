<?php

class Category{
    private $categoryID;
    private $userID;
    private $categoryName;

    public function __construct(
        int $taskID,
        int $userID,
        string $categoryName
    ){
        $this->taskID = $categoryID;
        $this->userID = $userID;
        $this->categoryName = $categoryName;
    }

    public function getCategoryID(): int
    {
        return $this->categoryID;
    }

    public function getUserID(): int
    {
        return $this->userID;
    }

    public function getCategoryName(): string
    {
        return $this->categoryName;
    }

    public function setCategoryID($categoryID): int
    {
        $this->categoryID = $categoryID;
    }

    public function setUserID($userID): int
    {
        $this->userID = $userID;
    }

    public function setCategoryName($categoryName): string
    {
        $this->categoryName = $categoryName;
    }  
}