<?php

class Preferences {
    private $userID;
    private $bio;
    private $deleteFinishedTasks;
    private $funInfluence;
    private $difficultyInfluence;
    private $importanceInfluence;
    private $timeInfluence;
    private $deadlineInfluence;

    public function __construct(
        int $userID,
        ?string $bio = null,
        bool $deleteFinishedTasks = false,
        float $funInfluence = 1.0,
        float $difficultyInfluence = 1.0,
        float $importanceInfluence = 1.0,
        float $timeInfluence = 1.0,
        float $deadlineInfluence = 1.0
    ) {
        $this->userID = $userID;
        $this->bio = $bio;
        $this->deleteFinishedTasks = $deleteFinishedTasks;
        $this->funInfluence = $funInfluence;
        $this->difficultyInfluence = $difficultyInfluence;
        $this->importanceInfluence = $importanceInfluence;
        $this->timeInfluence = $timeInfluence;
        $this->deadlineInfluence = $deadlineInfluence;
    }

    public function getUserID(): int
    {
        return $this->userID;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getDeleteFinishedTasks(): bool
    {
        return $this->deleteFinishedTasks;
    }

    public function getFunInfluence(): float
    {
        return $this->funInfluence;
    }

    public function getDifficultyInfluence(): float
    {
        return $this->difficultyInfluence;
    }

    public function getImportanceInfluence(): float
    {
        return $this->importanceInfluence;
    }

    public function getTimeInfluence(): float
    {
        return $this->timeInfluence;
    }

    public function getDeadlineInfluence(): float
    {
        return $this->deadlineInfluence;
    }

    public function setBio(?string $bio): void
    {
        $this->bio = $bio;
    }

    public function setDeleteFinishedTasks(bool $deleteFinishedTasks): void
    {
        $this->deleteFinishedTasks = $deleteFinishedTasks;
    }

    public function setFunInfluence(float $funInfluence): void
    {
        $this->funInfluence = $funInfluence;
    }

    public function setDifficultyInfluence(float $difficultyInfluence): void
    {
        $this->difficultyInfluence = $difficultyInfluence;
    }

    public function setImportanceInfluence(float $importanceInfluence): void
    {
        $this->importanceInfluence = $importanceInfluence;
    }

    public function setTimeInfluence(float $timeInfluence): void
    {
        $this->timeInfluence = $timeInfluence;
    }

    public function setDeadlineInfluence(float $deadlineInfluence): void
    {
        $this->deadlineInfluence = $deadlineInfluence;
    }

    public function getInfluences(): array
    {
        return [
            'funInfluence' => $this->funInfluence,
            'difficultyInfluence' => $this->difficultyInfluence,
            'importanceInfluence' => $this->importanceInfluence,
            'timeInfluence' => $this->timeInfluence,
            'deadlineInfluence' => $this->deadlineInfluence
        ];
    }
}