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

    public function getCategories() {
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
            $categories = $this->categoriesRepository->getCategoriesByUserId($userId);

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'categories' => $categories ?? []
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }

    public function createCategory() {
        header('Content-Type: application/json');
        
        try {
            if(!$this->isPost()){
                http_response_code(405);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Method not allowed'
                ]);
                return;
            }

            if(!isset($_SESSION['username'])){
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ]);
                return;
            }

            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
            if ($contentType !== "application/json") {
                http_response_code(415);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid content type'
                ]);
                return;
            }

            $content = trim(file_get_contents("php://input"));
            $decoded = json_decode($content, true);

            if (!isset($decoded['categoryName']) || empty(trim($decoded['categoryName']))) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Category name is required'
                ]);
                return;
            }

            $user = $this->userRepository->getUserByUsername($_SESSION['username']);
            $userId = $user['userid'];

            $categoryName = trim($decoded['categoryName']);

            $this->categoriesRepository->createCategory($userId, $categoryName);

            http_response_code(201);
            echo json_encode([
                'status' => 'success',
                'message' => 'Category created successfully'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }

    public function updateCategory() {
        header('Content-Type: application/json');
        
        try {
            if(!$this->isPost()){
                http_response_code(405);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Method not allowed'
                ]);
                return;
            }

            if(!isset($_SESSION['username'])){
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ]);
                return;
            }

            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
            if ($contentType !== "application/json") {
                http_response_code(415);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid content type'
                ]);
                return;
            }

            $content = trim(file_get_contents("php://input"));
            $decoded = json_decode($content, true);

            if (!isset($decoded['categoryId']) || !isset($decoded['categoryName'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Category ID and name are required'
                ]);
                return;
            }

            $user = $this->userRepository->getUserByUsername($_SESSION['username']);
            $userId = $user['userid'];

            $categoryId = (int)$decoded['categoryId'];
            $categoryName = trim($decoded['categoryName']);

            // Verify the category belongs to the user
            if (!$this->categoriesRepository->categoryExists($categoryId, $userId)) {
                http_response_code(403);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Forbidden: Category does not belong to user'
                ]);
                return;
            }

            $this->categoriesRepository->updateCategory($categoryId, $categoryName);

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Category updated successfully'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteCategory() {
        header('Content-Type: application/json');
        
        try {
            if(!$this->isPost()){
                http_response_code(405);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Method not allowed'
                ]);
                return;
            }

            if(!isset($_SESSION['username'])){
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ]);
                return;
            }

            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
            if ($contentType !== "application/json") {
                http_response_code(415);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid content type'
                ]);
                return;
            }

            $content = trim(file_get_contents("php://input"));
            $decoded = json_decode($content, true);

            if (!isset($decoded['categoryId'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Category ID is required'
                ]);
                return;
            }

            $user = $this->userRepository->getUserByUsername($_SESSION['username']);
            $userId = $user['userid'];

            $categoryId = (int)$decoded['categoryId'];

            // Verify the category belongs to the user
            if (!$this->categoriesRepository->categoryExists($categoryId, $userId)) {
                http_response_code(403);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Forbidden: Category does not belong to user'
                ]);
                return;
            }

            $this->categoriesRepository->deleteCategory($categoryId);

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Category deleted successfully'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }
}