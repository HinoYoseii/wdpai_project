<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/TaskRepository.php';
require_once __DIR__.'/../repository/PreferencesRepository.php';
require_once __DIR__.'/../repository/CategoriesRepository.php';
require_once __DIR__.'/../models/Preferences.php';

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

    public function getTasks() {
        header('Content-Type: application/json');
        
        try {
            if(!isset($_SESSION['username'])){
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ]);
                return;
            }

            $user = $this->userRepository->getUserByUsername($_SESSION['username']);
            if (!$user) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found'
                ]);
                return;
            }

            $userId = $user['userid'];
            $this->preferencesRepository->ensurePreferencesExist($userId);

            $tasks = $this->taskRepository->getUnfinishedTasks($userId);

            if (!$tasks) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'tasks' => []
                ]);
                return;
            }

            $tasksWithScore = $this->calculateTaskPriorities($tasks, $userId);

            usort($tasksWithScore, function($a, $b) {
                // Pinned tasks always come first
                if ($a['ispinned'] && !$b['ispinned']) return -1;
                if (!$a['ispinned'] && $b['ispinned']) return 1;
                
                // Then sort by priority score
                return $b['priorityScore'] <=> $a['priorityScore'];
            });

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'tasks' => $tasksWithScore
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }

    public function getFinishedTasks() {
        header('Content-Type: application/json');
        
        try {
            if(!isset($_SESSION['username'])){
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ]);
                return;
            }

            $user = $this->userRepository->getUserByUsername($_SESSION['username']);
            if (!$user) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found'
                ]);
                return;
            }

            $userId = $user['userid'];
            $tasks = $this->taskRepository->getFinishedTasks($userId);

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'tasks' => $tasks ?: []
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }

    public function getTask() {
        header('Content-Type: application/json');
        
        try {
            if(!isset($_SESSION['username'])){
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ]);
                return;
            }

            $taskId = $_GET['taskId'] ?? null;
            
            if (!$taskId) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Task ID is required'
                ]);
                return;
            }

            $task = $this->taskRepository->getTask((int)$taskId);

            if (!$task) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Task not found'
                ]);
                return;
            }

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'task' => $task
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }

    public function createTask() {
        header('Content-Type: application/json');
        
        try {
            if(!isset($_SESSION['username'])){
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ]);
                return;
            }

            $user = $this->userRepository->getUserByUsername($_SESSION['username']);
            if (!$user) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found'
                ]);
                return;
            }

            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
            if ($contentType === "application/json") {
                $content = trim(file_get_contents("php://input"));
                $decoded = json_decode($content, true);

                $title = $decoded['title'] ?? '';
                $taskDescription = $decoded['taskDescription'] ?? null;
                $categoryId = $decoded['categoryId'] ?? null;
                $deadlineDate = $decoded['deadlineDate'] ?? null;
                $fun = $decoded['fun'] ?? 'medium';
                $difficulty = $decoded['difficulty'] ?? 'medium';
                $importance = $decoded['importance'] ?? 'medium';
                $time = $decoded['time'] ?? 'medium';

                if (empty($title)) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Title is required'
                    ]);
                    return;
                }

                $this->taskRepository->createTask(
                    $user['userid'],
                    $categoryId,
                    $deadlineDate,
                    $title,
                    $taskDescription,
                    $fun,
                    $difficulty,
                    $importance,
                    $time
                );

                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Task created successfully'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }

    public function updateTask() {
        header('Content-Type: application/json');
        
        try {
            if(!isset($_SESSION['username'])){
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ]);
                return;
            }

            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
            if ($contentType === "application/json") {
                $content = trim(file_get_contents("php://input"));
                $decoded = json_decode($content, true);

                $taskId = $decoded['taskId'] ?? null;
                $title = $decoded['title'] ?? '';
                $taskDescription = $decoded['taskDescription'] ?? null;
                $categoryId = $decoded['categoryId'] ?? null;
                $deadlineDate = $decoded['deadlineDate'] ?? null;
                $fun = $decoded['fun'] ?? 'medium';
                $difficulty = $decoded['difficulty'] ?? 'medium';
                $importance = $decoded['importance'] ?? 'medium';
                $time = $decoded['time'] ?? 'medium';

                if (!$taskId || empty($title)) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Task ID and title are required'
                    ]);
                    return;
                }

                $this->taskRepository->updateTask(
                    (int)$taskId,
                    $categoryId,
                    $deadlineDate,
                    $title,
                    $taskDescription,
                    $fun,
                    $difficulty,
                    $importance,
                    $time
                );

                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Task updated successfully'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteTask() {
        header('Content-Type: application/json');
        
        try {
            if(!isset($_SESSION['username'])){
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ]);
                return;
            }

            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
            if ($contentType === "application/json") {
                $content = trim(file_get_contents("php://input"));
                $decoded = json_decode($content, true);

                $taskId = $decoded['taskId'] ?? null;

                if (!$taskId) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Task ID is required'
                    ]);
                    return;
                }

                $this->taskRepository->deleteTask((int)$taskId);

                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Task deleted successfully'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }

    public function finishTask() {
        header('Content-Type: application/json');
        
        try {
            if(!isset($_SESSION['username'])){
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ]);
                return;
            }

            $user = $this->userRepository->getUserByUsername($_SESSION['username']);
            if (!$user) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found'
                ]);
                return;
            }

            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
            if ($contentType === "application/json") {
                $content = trim(file_get_contents("php://input"));
                $decoded = json_decode($content, true);

                $taskId = $decoded['taskId'] ?? null;

                if (!$taskId) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Task ID is required'
                    ]);
                    return;
                }

                // Get user preferences to check if we should delete finished tasks
                $preferences = $this->preferencesRepository->getPreferences($user['userid']);
                $deleteFinished = $preferences['deletefinishedtasks'] ?? false;

                if ($deleteFinished) {
                    $this->taskRepository->deleteTask((int)$taskId);
                    $message = 'Task deleted';
                } else {
                    $this->taskRepository->markTaskAsFinished((int)$taskId);
                    $message = 'Task marked as finished';
                }

                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => $message
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }

    public function unfinishTask() {
        header('Content-Type: application/json');
        
        try {
            if(!isset($_SESSION['username'])){
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ]);
                return;
            }

            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
            if ($contentType === "application/json") {
                $content = trim(file_get_contents("php://input"));
                $decoded = json_decode($content, true);

                $taskId = $decoded['taskId'] ?? null;

                if (!$taskId) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Task ID is required'
                    ]);
                    return;
                }

                $this->taskRepository->markTaskAsUnfinished((int)$taskId);

                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Task restored'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }

    public function pinTask() {
        header('Content-Type: application/json');
        
        try {
            if(!isset($_SESSION['username'])){
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ]);
                return;
            }

            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
            if ($contentType === "application/json") {
                $content = trim(file_get_contents("php://input"));
                $decoded = json_decode($content, true);

                $taskId = $decoded['taskId'] ?? null;
                $isPinned = $decoded['isPinned'] ?? false;

                if (!$taskId) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Task ID is required'
                    ]);
                    return;
                }

                $this->taskRepository->updateTaskPinStatus((int)$taskId, (bool)$isPinned);

                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => $isPinned ? 'Task pinned' : 'Task unpinned'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }

    private function calculateTaskPriorities(array $tasks, int $userId): array {
        $preferencesData = $this->preferencesRepository->getPreferences($userId);

        if ($preferencesData) {
            $preferences = [
                'funInfluence' => (float)($preferencesData['funinfluence'] ?? 1.0),
                'difficultyInfluence' => (float)($preferencesData['difficultyinfluence'] ?? 1.0),
                'importanceInfluence' => (float)($preferencesData['importanceinfluence'] ?? 1.0),
                'timeInfluence' => (float)($preferencesData['timeinfluence'] ?? 1.0),
                'deadlineInfluence' => (float)($preferencesData['deadlineinfluence'] ?? 1.0)
            ];
        } else {
            $preferences = [
                'funInfluence' => 1.0,
                'difficultyInfluence' => 1.0,
                'importanceInfluence' => 1.0,
                'timeInfluence' => 1.0,
                'deadlineInfluence' => 1.0
            ];
        }
        
        $tasksWithScore = [];
        $currentTime = time();

        // Convert text values to numeric scores
        $valueMap = [
            'low' => 33,
            'medium' => 66,
            'high' => 100,
            'short' => 33,
            'long' => 100
        ];

        foreach ($tasks as $task) {
            $score = 0;

            // Fun factor (higher = more fun = higher priority)
            $funValue = $valueMap[$task['fun']] ?? 66;
            $score += $funValue * $preferences['funInfluence'];

            // Difficulty (lower difficulty = higher priority)
            $difficultyValue = $valueMap[$task['difficulty']] ?? 66;
            $score += (100 - $difficultyValue) * $preferences['difficultyInfluence'];

            // Importance (higher = higher priority)
            $importanceValue = $valueMap[$task['importance']] ?? 66;
            $score += $importanceValue * $preferences['importanceInfluence'];

            // Time required (shorter = higher priority)
            $timeValue = $valueMap[$task['time']] ?? 66;
            $score += (100 - $timeValue) * $preferences['timeInfluence'];

            // Deadline urgency
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

        return $tasksWithScore;
    }
}