<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';

class CategoriesController extends AppController {

    public function categories() {
        if(!isset($_SESSION['username'])){
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }
        // tutaj logika logowania(sprawdzanie uzytkownika, zabezpieczenie inputu itd.)

        $userRepository = UserRepository::getInstance();
        //$users = $userRepository->getUsers();

        return $this->render("categories");
    }

}