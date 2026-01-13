<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/TaskRepository.php';
require_once __DIR__.'/../repository/PreferencesRepository.php';
require_once __DIR__.'/../repository/CategoriesRepository.php';

class DashboardController extends AppController {

    private $taskRepository;
    private $preferencesRepository;
    private $categoryRepository;

    public function __construct() {
        $this->taskRepository = TaskRepository::getInstance();
        $this->preferencesRepository = PreferencesRepository::getInstance();
        $this->categoryRepository = CategoriesRepository::getInstance();
    }

    public function dashboard() {
        $this->requireLogin();

        return $this->render("dashboard");
    }

    // API endpoints
    public function getTasks() {
        header('Content-Type: application/json');
        
        try {
            $this->requireLogin();

            $user = $this->getUserCookie();
            $userId = $user['id'];

            $tasks = $this->taskRepository->getUnfinishedTasks($userId);
            $tasksWithScore = $this->calculateTaskPriorities($tasks ?: [], $userId);

            $this->jsonResponse('success', ['tasks' => $tasksWithScore]);
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function getFinishedTasks() {
        header('Content-Type: application/json');
        
        try {
            $this->requireLogin();

            $user = $this->getUserCookie();
            $userId = $user['id'];

            $tasks = $this->taskRepository->getFinishedTasks($userId);

            $this->jsonResponse('success', ['tasks' => $tasks ?: []]);
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function getTask() {
        header('Content-Type: application/json');
        
        try {
            $this->requireLogin();

            $taskId = $_GET['taskId'] ?? null;
            
            if (!$taskId) {
                $this->jsonResponse('error', null, 'Task ID is required', 400);
                return;
            }

            $task = $this->taskRepository->getTask((int)$taskId);

            if (!$task) {
                $this->jsonResponse('error', null, 'Task not found', 404);
                return;
            }

            $this->jsonResponse('success', ['task' => $task]);
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function createTask() {
        header('Content-Type: application/json');
        
        try {
            $this->requireLogin();

            $user = $this->getUserCookie();
            $userId = $user['id'];

            $data = $this->getJsonInput();
            if (!$data) {
                $this->jsonResponse('error', null, 'Invalid request', 400);
                return;
            }

            $title = $data['title'] ?? '';

            if (empty($title)) {
                $this->jsonResponse('error', null, 'Title is required', 400);
                return;
            }

            $this->taskRepository->createTask(
                $userId,
                $data['categoryId'] ?? null,
                $data['deadlineDate'] ?? null,
                $title,
                $data['taskDescription'] ?? null,
                $data['fun'] ?? 'medium',
                $data['difficulty'] ?? 'medium',
                $data['importance'] ?? 'medium',
                $data['time'] ?? 'medium'
            );

            $this->jsonResponse('success', null, 'Task created successfully');
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function updateTask() {
        header('Content-Type: application/json');
        
        try {
            $this->requireLogin();

            $data = $this->getJsonInput();
            if (!$data) {
                $this->jsonResponse('error', null, 'Invalid request', 400);
                return;
            }

            $taskId = $data['taskId'] ?? null;
            $title = $data['title'] ?? '';

            if (!$taskId || empty($title)) {
                $this->jsonResponse('error', null, 'Task ID and title are required', 400);
                return;
            }

            $this->taskRepository->updateTask(
                (int)$taskId,
                $data['categoryId'] ?? null,
                $data['deadlineDate'] ?? null,
                $title,
                $data['taskDescription'] ?? null,
                $data['fun'] ?? 'medium',
                $data['difficulty'] ?? 'medium',
                $data['importance'] ?? 'medium',
                $data['time'] ?? 'medium'
            );

            $this->jsonResponse('success', null, 'Task updated successfully');
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function deleteTask() {
        header('Content-Type: application/json');
        
        try {
            $this->requireLogin();

            $data = $this->getJsonInput();
            if (!$data) {
                $this->jsonResponse('error', null, 'Invalid request', 400);
                return;
            }

            $taskId = $data['taskId'] ?? null;

            if (!$taskId) {
                $this->jsonResponse('error', null, 'Task ID is required', 400);
                return;
            }

            $this->taskRepository->deleteTask((int)$taskId);

            $this->jsonResponse('success', null, 'Task deleted successfully');
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function finishTask() {
        header('Content-Type: application/json');
        
        try {
            $this->requireLogin();

            $user = $this->getUserCookie();
            $userId = $user['id'];

            $data = $this->getJsonInput();
            if (!$data) {
                $this->jsonResponse('error', null, 'Invalid request', 400);
                return;
            }

            $taskId = $data['taskId'] ?? null;

            if (!$taskId) {
                $this->jsonResponse('error', null, 'Task ID is required', 400);
                return;
            }

            $preferences = $this->preferencesRepository->getPreferences($userId);
            $deleteFinished = $preferences['deletefinishedtasks'] ?? false;

            if ($deleteFinished) {
                $this->taskRepository->deleteTask((int)$taskId);
                $message = 'Task deleted';
            } else {
                $this->taskRepository->markTaskAsFinished((int)$taskId);
                $message = 'Task marked as finished';
            }

            $this->jsonResponse('success', null, $message);
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function unfinishTask() {
        header('Content-Type: application/json');
        
        try {
            if (!$this->checkAuth()) return;

            $data = $this->getJsonInput();
            if (!$data) {
                $this->jsonResponse('error', null, 'Invalid request', 400);
                return;
            }

            $taskId = $data['taskId'] ?? null;

            if (!$taskId) {
                $this->jsonResponse('error', null, 'Task ID is required', 400);
                return;
            }

            $this->taskRepository->markTaskAsUnfinished((int)$taskId);

            $this->jsonResponse('success', null, 'Task restored');
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function pinTask() {
        header('Content-Type: application/json');
        
        try {
            if (!$this->checkAuth()) return;

            $data = $this->getJsonInput();
            if (!$data) {
                $this->jsonResponse('error', null, 'Invalid request', 400);
                return;
            }

            $taskId = $data['taskId'] ?? null;
            $isPinned = $data['isPinned'] ?? false;

            if (!$taskId) {
                $this->jsonResponse('error', null, 'Task ID is required', 400);
                return;
            }

            $this->taskRepository->updateTaskPinStatus((int)$taskId, (bool)$isPinned);

            $this->jsonResponse('success', null, $isPinned ? 'Task pinned' : 'Task unpinned');
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    private function calculateTaskPriorities(array $tasks, int $userId): array {
        if (empty($tasks)) {
            return [];
        }

        $preferencesData = $this->preferencesRepository->getPreferences($userId);

        $preferences = [
            'funInfluence' => (float)($preferencesData['funinfluence'] ?? 1.0),
            'difficultyInfluence' => (float)($preferencesData['difficultyinfluence'] ?? 1.0),
            'importanceInfluence' => (float)($preferencesData['importanceinfluence'] ?? 1.0),
            'timeInfluence' => (float)($preferencesData['timeinfluence'] ?? 1.0),
            'deadlineInfluence' => (float)($preferencesData['deadlineinfluence'] ?? 1.0)
        ];
        
        $currentTime = time();
        $tasksWithScore = [];

        foreach ($tasks as $task) {
            $score = 0;

            $score += $task['fun'] * $preferences['funInfluence'];

            $score += (10 - $task['difficulty']) * $preferences['difficultyInfluence'];

            $score += $task['importance'] * $preferences['importanceInfluence'];

            $score += (10 - $task['time']) * $preferences['timeInfluence'];

            if ($task['deadlinedate']) {
                $deadlineTimestamp = strtotime($task['deadlinedate']);
                $daysUntilDeadline = ($deadlineTimestamp - $currentTime) / (60 * 60 * 24);
                
                if ($daysUntilDeadline < 0) {
                    $urgencyScore = 10;
                } elseif ($daysUntilDeadline < 1) {
                    $urgencyScore = 5;
                } elseif ($daysUntilDeadline < 3) {
                    $urgencyScore = 4;
                } elseif ($daysUntilDeadline < 7) {
                    $urgencyScore = 3;
                } elseif ($daysUntilDeadline < 14) {
                    $urgencyScore = 2;
                } else {
                    $urgencyScore = 11;
                }
                
                $score += $urgencyScore * $preferences['deadlineInfluence'];
            }

            $task['priorityScore'] = round($score, 2);
            $tasksWithScore[] = $task;
        }

        usort($tasksWithScore, function($a, $b) {
            if ($a['ispinned'] && !$b['ispinned']) return -1;
            if (!$a['ispinned'] && $b['ispinned']) return 1;
            return $b['priorityScore'] <=> $a['priorityScore'];
        });

        return $tasksWithScore;
    }
}