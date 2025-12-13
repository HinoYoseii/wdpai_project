<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/CardsRepository.php';

class DashboardController extends AppController {

    private $cardsRepository;

    public function __construct() {
        $this->cardsRepository = new CardsRepository();
    }

    public function dashboard() {
        if(!isset($_SESSION['username'])){
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }
        // tutaj logika logowania(sprawdzanie uzytkownika, zabezpieczenie inputu itd.)

        $userRepository = UserRepository::getInstance();
        return $this->render("dashboard");
    }

    public function search()
    {
        header('Content-Type: application/json');
        if(!$this->isPost()){
            http_response_code(405);
            echo json_encode([
                'status' => 'ummm nie'
            ]);
            return;
        }

        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

        if ($contentType !== "application/json") {
            http_response_code(415);
            var_dump($contentType);
            echo json_encode([
                'status' => 'invalid content type'
            ]);
        }

        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);      
        $searchTag = $decoded['search'];

        http_response_code(200);
        echo json_encode([
            'status' => 'ok',
            'cards' => $this->cardsRepository->getCardsByTitle($searchTag)
        ]);

        return; 
    }
}