<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/CategoriesRepository.php';

class CategoriesController extends AppController {

    private $categoriesRepository;
    private $userRepository;

    public function __construct() {
        $this->categoriesRepository = CategoriesRepository::getInstance();
        $this->userRepository = UserRepository::getInstance();
    }

    public function categories() {
        if(!isset($_SESSION['username'])){
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        return $this->render("categories");
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
        if (!$this->isPost()) {
            http_response_code(405);
            echo json_encode([
                'status' => 'error',
                'message' => 'Method not allowed'
            ]);
            return null;
        }

        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        
        if ($contentType !== "application/json") {
            http_response_code(415);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid content type'
            ]);
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
    public function getCategories() {
        header('Content-Type: application/json');
        
        try {
            $user = $this->checkAuth();
            if (!$user) return;

            $categories = $this->categoriesRepository->getCategoriesByUserId($user['userid']);

            $this->jsonResponse('success', ['categories' => $categories ?? []]);
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function createCategory() {
        header('Content-Type: application/json');
        
        try {
            $user = $this->checkAuth();
            if (!$user) return;

            $data = $this->getJsonInput();
            if (!$data) return;

            if (!isset($data['categoryName']) || empty(trim($data['categoryName']))) {
                $this->jsonResponse('error', null, 'Category name is required', 400);
                return;
            }

            $categoryName = trim($data['categoryName']);
            $this->categoriesRepository->createCategory($user['userid'], $categoryName);

            $this->jsonResponse('success', null, 'Category created successfully', 201);
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function updateCategory() {
        header('Content-Type: application/json');
        
        try {
            $user = $this->checkAuth();
            if (!$user) return;

            $data = $this->getJsonInput();
            if (!$data) return;

            if (!isset($data['categoryId']) || !isset($data['categoryName'])) {
                $this->jsonResponse('error', null, 'Category ID and name are required', 400);
                return;
            }

            $categoryId = (int)$data['categoryId'];
            $categoryName = trim($data['categoryName']);

            // Verify the category belongs to the user
            if (!$this->categoriesRepository->categoryExists($categoryId, $user['userid'])) {
                $this->jsonResponse('error', null, 'Forbidden: Category does not belong to user', 403);
                return;
            }

            $this->categoriesRepository->updateCategory($categoryId, $categoryName);

            $this->jsonResponse('success', null, 'Category updated successfully');
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function deleteCategory() {
        header('Content-Type: application/json');
        
        try {
            $user = $this->checkAuth();
            if (!$user) return;

            $data = $this->getJsonInput();
            if (!$data) return;

            if (!isset($data['categoryId'])) {
                $this->jsonResponse('error', null, 'Category ID is required', 400);
                return;
            }

            $categoryId = (int)$data['categoryId'];

            // Verify the category belongs to the user
            if (!$this->categoriesRepository->categoryExists($categoryId, $user['userid'])) {
                $this->jsonResponse('error', null, 'Forbidden: Category does not belong to user', 403);
                return;
            }

            $this->categoriesRepository->deleteCategory($categoryId);

            $this->jsonResponse('success', null, 'Category deleted successfully');
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }
}