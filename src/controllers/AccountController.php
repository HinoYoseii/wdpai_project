<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/PreferencesRepository.php';

class AccountController extends AppController {

    private $preferencesRepository;

    public function __construct() {
        $this->preferencesRepository = PreferencesRepository::getInstance();
    }

    public function account() {
        $this->requireLogin();

        $user = $this->getUserCookie();
        $userId = $user['id'];

        $preferences = $this->preferencesRepository->getPreferences($userId);

        // Render view
        return $this->render("account", [
            'influences' => $preferences
        ]);
    }
}