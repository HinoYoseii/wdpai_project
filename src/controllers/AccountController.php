<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';

class AccountController extends AppController {

    public function account() {
        $this->requireLogin();

        $userRepository = UserRepository::getInstance();
        return $this->render("account");
    }

}