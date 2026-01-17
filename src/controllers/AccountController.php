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

        $finished = !empty($preferences) ? array_shift($preferences) : null;

        // Structure influences with proper names
        $influences = [
            ['name' => 'jest mnożona przez', 'key' => 'funInfluence', 'value' => $preferences['funinfluence'] ?? 1.0],
            ['name' => 'Trudność jest mnożona przez', 'key' => 'difficultyInfluence', 'value' => $preferences['difficultyinfluence'] ?? 1.0],
            ['name' => 'Istotnośc jest mnożona przez', 'key' => 'importanceInfluence', 'value' => $preferences['importanceinfluence'] ?? 1.0],
            ['name' => 'Długość zadania jest mnożona przez', 'key' => 'timeInfluence', 'value' => $preferences['timeinfluence'] ?? 1.0],
            ['name' => 'Dni pozostałe do terminu są mnożone przez', 'key' => 'deadlineInfluence', 'value' => $preferences['deadlineinfluence'] ?? 1.0],
        ];

        return $this->render("account", [
            'finished' => $finished,
            'influences' => $influences
        ]);
    }

    public function updatePrefs(){
        try {
            $this->requireLogin();

            $user = $this->getUserCookie();
            $userId = $user['id'];

            // Get form data
            $finished = isset($_POST['finished']) ? true : false;
            $funInfluence = (float)($_POST['funInfluence'] ?? 1.0);
            $difficultyInfluence = (float)($_POST['difficultyInfluence'] ?? 1.0);
            $importanceInfluence = (float)($_POST['importanceInfluence'] ?? 1.0);
            $timeInfluence = (float)($_POST['timeInfluence'] ?? 1.0);
            $deadlineInfluence = (float)($_POST['deadlineInfluence'] ?? 1.0);

            // Validate ranges (0 to 2)
            $influences = [
                $funInfluence, 
                $difficultyInfluence, 
                $importanceInfluence, 
                $timeInfluence, 
                $deadlineInfluence
            ];
            
            foreach ($influences as $influence) {
                if ($influence < 0 || $influence > 2) {
                    $this->jsonResponse('error', null, 'Invalid values', 400);
                    return;
                }
            }

            // Update preferences
            $this->preferencesRepository->updatePreferences(
                $userId,
                $finished,
                $funInfluence,
                $difficultyInfluence,
                $importanceInfluence,
                $timeInfluence,
                $deadlineInfluence
            );

            header('Location: /account');
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }
}