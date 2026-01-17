<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';

class AdminController extends AppController {

    private $userRepository;

    public function __construct() {
        $this->userRepository = UserRepository::getInstance();
    }

    public function admin() {
        $this->requireAdmin();

        $users= $this->userRepository->getUsers();

        return $this->render("admin", [
            'users' => $users
        ]);
    }

    public function deleteUserByEmail() {
        try {
            $this->requireAdmin();
            
            $data = json_decode(file_get_contents('php://input'), true);
            $email = $data['email'] ?? null;

            if (!$email) {
                $this->jsonResponse('error', null, 'Email is required', 400);
                return;
            }

            $result = $this->userRepository->deleteUserByEmail($email);

            if ($result) {
                $this->jsonResponse('success', null, 'User deleted successfully', 200);
            } else {
                $this->jsonResponse('error', null, 'Failed to delete user', 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }
}