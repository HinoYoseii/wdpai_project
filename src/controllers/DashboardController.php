<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/TaskRepository.php';
require_once __DIR__.'/../repository/PreferencesRepository.php';
require_once __DIR__.'/../models/Preferences.php';

class DashboardController extends AppController {

    private $taskRepository;
    private $userRepository;
    private $preferencesRepository;

    public function __construct() {
        $this->taskRepository = TaskRepository::getInstance();
        $this->userRepository = UserRepository::getInstance();
        $this->preferencesRepository = PreferencesRepository::getInstance();
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
        // Set header first to ensure JSON response
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

            // Get user ID from session
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

            // Ensure user has preferences (create default if not)
            $this->preferencesRepository->ensurePreferencesExist($userId);

            // Get unfinished tasks
            $tasks = $this->taskRepository->getUnfinishedTasks($userId);

            if (!$tasks) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'tasks' => []
                ]);
                return;
            }

            // Calculate priority score for each task
            $tasksWithScore = $this->calculateTaskPriorities($tasks, $userId);

            // Sort tasks by priority score (highest first)
            usort($tasksWithScore, function($a, $b) {
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

    private function calculateTaskPriorities(array $tasks, int $userId): array {
        // Get user preferences using the repository
        $preferencesData = $this->preferencesRepository->getPreferences($userId);

        if ($preferencesData) {
            $preferences = [
                'funInfluence' => (float)($preferencesData['funinfluence'] ?? 1.0),
                'difficultyInfluence' => (float)($preferencesData['difficultyinfluence'] ?? 1.0),
                'importanceInfluence' => (float)($preferencesData['importanceinfluence'] ?? 1.0),
                'timeInfluence' => (float)($preferencesData['timeinfluence'] ?? 1.0),
                'deadlineInfluence' => (float)($preferencesData['deadlineinfluence'] ?? 1.0)
            ];
        }
        
        $tasksWithScore = [];
        $currentTime = time();

        foreach ($tasks as $task) {
            $score = 0;

            // Fun factor (0-100, higher = more fun = higher priority)
            $score += ($task['fun'] ?? 50) * $preferences['funInfluence'];

            // Difficulty (0-100, lower difficulty = higher priority for easier wins)
            $score += (100 - ($task['difficulty'] ?? 50)) * $preferences['difficultyInfluence'];

            // Importance (0-100, higher = higher priority)
            $score += ($task['importance'] ?? 50) * $preferences['importanceInfluence'];

            // Time required (0-100, lower time = higher priority for quick wins)
            $score += (100 - ($task['time'] ?? 50)) * $preferences['timeInfluence'];

            // Deadline urgency
            if ($task['deadlinedate']) {
                $deadlineTimestamp = strtotime($task['deadlinedate']);
                $daysUntilDeadline = ($deadlineTimestamp - $currentTime) / (60 * 60 * 24);
                
                // Urgency score: closer deadline = higher priority
                if ($daysUntilDeadline < 0) {
                    // Overdue tasks get maximum urgency
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