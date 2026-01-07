<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/TaskRepository.php';
require_once __DIR__.'/../repository/PreferencesRepository.php';
require_once __DIR__.'/../repository/CategoriesRepository.php';

class DashboardController extends AppController {

    private $taskRepository;
    private $userRepository;
    private $preferencesRepository;
    private $categoryRepository;

    public function __construct() {
        $this->taskRepository = TaskRepository::getInstance();
        $this->userRepository = UserRepository::getInstance();
        $this->preferencesRepository = PreferencesRepository::getInstance();
        $this->categoryRepository = CategoriesRepository::getInstance();
    }

    public function dashboard() {
        if(!isset($_SESSION['username'])){
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }
        
        return $this->render("dashboard");
    }

    // Helper methods
    private function checkAuth(): ?array {
        if(!isset($_SESSION['username'])){
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
            return null;
        }

        $user = $this->userRepository->getUserByUsername($_SESSION['username']);
        if (!$user) {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'User not found'
            ]);
            return null;
        }

        return $user;
    }

    private function getJsonInput(): ?array {
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        
        if ($contentType !== "application/json") {
            return null;
        }

        $content = trim(file_get_contents("php://input"));
        return json_decode($content, true);
    }

    private function jsonResponse(string $status, $data = null, string $message = '', int $httpCode = 200): void {
        http_response_code($httpCode);
        $response = ['status' => $status];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response = array_merge($response, $data);
        }
        
        echo json_encode($response);
    }

    // API endpoints
    public function getTasks() {
        header('Content-Type: application/json');
        
        try {
            $user = $this->checkAuth();
            if (!$user) return;

            $userId = $user['userid'];
            $this->preferencesRepository->ensurePreferencesExist($userId);

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
            $user = $this->checkAuth();
            if (!$user) return;

            $tasks = $this->taskRepository->getFinishedTasks($user['userid']);

            $this->jsonResponse('success', ['tasks' => $tasks ?: []]);
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function getTask() {
        header('Content-Type: application/json');
        
        try {
            if (!$this->checkAuth()) return;

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
            $user = $this->checkAuth();
            if (!$user) return;

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
                $user['userid'],
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
            if (!$this->checkAuth()) return;

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

            $this->taskRepository->deleteTask((int)$taskId);

            $this->jsonResponse('success', null, 'Task deleted successfully');
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function finishTask() {
        header('Content-Type: application/json');
        
        try {
            $user = $this->checkAuth();
            if (!$user) return;

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

            $preferences = $this->preferencesRepository->getPreferences($user['userid']);
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
        
        $valueMap = [
            'low' => 33,
            'medium' => 66,
            'high' => 100,
            'short' => 33,
            'long' => 100
        ];

        $currentTime = time();
        $tasksWithScore = [];

        foreach ($tasks as $task) {
            $score = 0;

            $funValue = $valueMap[$task['fun']] ?? 66;
            $score += $funValue * $preferences['funInfluence'];

            $difficultyValue = $valueMap[$task['difficulty']] ?? 66;
            $score += (100 - $difficultyValue) * $preferences['difficultyInfluence'];

            $importanceValue = $valueMap[$task['importance']] ?? 66;
            $score += $importanceValue * $preferences['importanceInfluence'];

            $timeValue = $valueMap[$task['time']] ?? 66;
            $score += (100 - $timeValue) * $preferences['timeInfluence'];

            if ($task['deadlinedate']) {
                $deadlineTimestamp = strtotime($task['deadlinedate']);
                $daysUntilDeadline = ($deadlineTimestamp - $currentTime) / (60 * 60 * 24);
                
                if ($daysUntilDeadline < 0) {
                    $urgencyScore = 100;
                } elseif ($daysUntilDeadline < 1) {
                    $urgencyScore = 90;
                } elseif ($daysUntilDeadline < 3) {
                    $urgencyScore = 70;
                } elseif ($daysUntilDeadline < 7) {
                    $urgencyScore = 50;
                } elseif ($daysUntilDeadline < 14) {
                    $urgencyScore = 30;
                } else {
                    $urgencyScore = 10;
                }
                
                $score += $urgencyScore * $preferences['deadlineInfluence'];
            }

            $task['priorityScore'] = round($score, 2);
            $tasksWithScore[] = $task;
        }

        // Sort: pinned first, then by priority score
        usort($tasksWithScore, function($a, $b) {
            if ($a['ispinned'] && !$b['ispinned']) return -1;
            if (!$a['ispinned'] && $b['ispinned']) return 1;
            return $b['priorityScore'] <=> $a['priorityScore'];
        });

        return $tasksWithScore;
    }
}