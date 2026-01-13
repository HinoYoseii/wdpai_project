<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/TaskRepository.php';
require_once __DIR__.'/../repository/PreferencesRepository.php';
require_once __DIR__.'/../repository/CategoriesRepository.php';

class ArchiveController extends AppController {

    private $taskRepository;
    private $preferencesRepository;
    private $categoryRepository;

    public function __construct() {
        $this->taskRepository = TaskRepository::getInstance();
        $this->preferencesRepository = PreferencesRepository::getInstance();
        $this->categoryRepository = CategoriesRepository::getInstance();
    }

    public function archive() {
        $this->requireLogin();

        return $this->render("archive");
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


    public function unfinishTask() {
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

            $this->taskRepository->markTaskAsUnfinished((int)$taskId);

            $this->jsonResponse('success', null, 'Task restored');
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }
}