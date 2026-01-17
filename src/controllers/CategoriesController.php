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
        $this->requireUser();
        
        $user = $this->getUserCookie();
        $userId = $user['id'];

        $categories = $this->categoriesRepository->getCategoriesByUserId($userId);
        
        return $this->render("categories", [
            'categories' => $categories ?? []
        ]);
    }

    public function createCategory() {
        header('Content-Type: application/json');
        
        try {
            $this->requireUser();

            $user = $this->getUserCookie();
            $userId = $user['id'];

            $data = $this->getJsonInput();
            if (!$data) {
                $this->jsonResponse('error', null, 'Invalid JSON input', 400);
                return;
            }

            if (!isset($data['categoryName']) || empty(trim($data['categoryName']))) {
                $this->jsonResponse('error', null, 'Category name is required', 400);
                return;
            }

            $categoryName = trim($data['categoryName']);
            $this->categoriesRepository->createCategory($userId, $categoryName);

            $this->jsonResponse('success', null, 'Category created successfully', 201);
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    public function updateCategory() {
        header('Content-Type: application/json');
        
        try {
            $this->requireUser();

            $user = $this->getUserCookie();
            $userId = (int)$user['id'];

            $data = $this->getJsonInput();
            if (!$data) {
                $this->jsonResponse('error', null, 'Invalid JSON input', 400);
                return;
            }

            if (!isset($data['categoryId']) || !isset($data['categoryName'])) {
                $this->jsonResponse('error', null, 'Category ID and name are required', 400);
                return;
            }

            $categoryId = (int)$data['categoryId'];
            $categoryName = trim($data['categoryName']);

            if (!$this->categoriesRepository->categoryExists($categoryId, $userId)) {
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
            $this->requireUser();

            $user = $this->getUserCookie();
            $userId = (int)$user['id'];

            $data = $this->getJsonInput();
            if (!$data) {
                $this->jsonResponse('error', null, 'Invalid JSON input', 400);
                return;
            }

            if (!isset($data['categoryId'])) {
                $this->jsonResponse('error', null, 'Category ID is required', 400);
                return;
            }

            $categoryId = (int)$data['categoryId'];

            if (!$this->categoriesRepository->categoryExists($categoryId, $userId)) {
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