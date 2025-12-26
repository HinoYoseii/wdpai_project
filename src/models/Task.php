<?php

class Task {
    private $taskID;
    private $userID;
    private $categoryID;
    private $deadlineDate;
    private $taskDescription;
    private $fun;
    private $difficulty;
    private $importance;
    private $time;
    private $isFinished;

    public function __construct(
        int $taskID,
        int $userID,
        ?int $categoryID,
        ?string $deadlineDate,
        string $taskDescription,
        int $fun,
        int $difficulty,
        int $importance,
        int $time,
        bool $isFinished = false
    ) {
        $this->taskID = $taskID;
        $this->userID = $userID;
        $this->categoryID = $categoryID;
        $this->deadlineDate = $deadlineDate;
        $this->taskDescription = $taskDescription;
        $this->fun = $fun;
        $this->difficulty = $difficulty;
        $this->importance = $importance;
        $this->time = $time;
        $this->isFinished = $isFinished;
    }

    public function getTaskID(): int
    {
        return $this->taskID;
    }

    public function getUserID(): int
    {
        return $this->userID;
    }

    public function getCategoryID(): ?int
    {
        return $this->categoryID;
    }

    public function getDeadlineDate(): ?string
    {
        return $this->deadlineDate;
    }

    public function getTaskDescription(): string
    {
        return $this->taskDescription;
    }

    public function getFun(): int
    {
        return $this->fun;
    }

    public function getDifficulty(): int
    {
        return $this->difficulty;
    }

    public function getImportance(): int
    {
        return $this->importance;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function isFinished(): bool
    {
        return $this->isFinished;
    }

    public function setCategoryID(?int $categoryID): void
    {
        $this->categoryID = $categoryID;
    }

    public function setDeadlineDate(?string $deadlineDate): void
    {
        $this->deadlineDate = $deadlineDate;
    }

    public function setTaskDescription(string $taskDescription): void
    {
        $this->taskDescription = $taskDescription;
    }

    public function setFun(int $fun): void
    {
        $this->fun = $fun;
    }

    public function setDifficulty(int $difficulty): void
    {
        $this->difficulty = $difficulty;
    }

    public function setImportance(int $importance): void
    {
        $this->importance = $importance;
    }

    public function setTime(int $time): void
    {
        $this->time = $time;
    }

    public function setFinished(bool $isFinished): void
    {
        $this->isFinished = $isFinished;
    }
}