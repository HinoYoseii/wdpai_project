<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/PreferencesRepository.php';

class AccountController extends AppController {

    private $preferencesRepository;

    public function __construct() {
        $this->preferencesRepository = PreferencesRepository::getInstance();
    }

    public function account() {
        $this->requireUser();

        $user = $this->getUserCookie();
        $userId = $user['id'];

        $preferences = $this->preferencesRepository->getPreferences($userId);

        $finished = !empty($preferences) ? array_shift($preferences) : null;

        $influences = [
            ['name' => 'Zabawa', 'key' => 'funInfluence', 'value' => $preferences['funinfluence'] ?? 1.0],
            ['name' => 'Trudność', 'key' => 'difficultyInfluence', 'value' => $preferences['difficultyinfluence'] ?? 1.0],
            ['name' => 'Istotność', 'key' => 'importanceInfluence', 'value' => $preferences['importanceinfluence'] ?? 1.0],
            ['name' => 'Długość', 'key' => 'timeInfluence', 'value' => $preferences['timeinfluence'] ?? 1.0],
            ['name' => 'Deadline', 'key' => 'deadlineInfluence', 'value' => $preferences['deadlineinfluence'] ?? 1.0],
        ];

        return $this->render("account", [
            'finished' => $finished,
            'influences' => $influences
        ]);
    }

    public function updatePrefs(){
        try {
            $this->requireUser();

            $user = $this->getUserCookie();
            $userId = $user['id'];

            $finished = isset($_POST['finished']) ? true : false;
            $funInfluence = (float)($_POST['funInfluence'] ?? 1.0);
            $difficultyInfluence = (float)($_POST['difficultyInfluence'] ?? 1.0);
            $importanceInfluence = (float)($_POST['importanceInfluence'] ?? 1.0);
            $timeInfluence = (float)($_POST['timeInfluence'] ?? 1.0);
            $deadlineInfluence = (float)($_POST['deadlineInfluence'] ?? 1.0);

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

            $this->preferencesRepository->updatePreferences(
                $userId,
                $finished,
                $funInfluence,
                $difficultyInfluence,
                $importanceInfluence,
                $timeInfluence,
                $deadlineInfluence
            );
            
            // Add success response
            $this->jsonResponse('success', null, 'Preferencje zostały zaktualizowane', 200);
            
        } catch (Exception $e) {
            $this->jsonResponse('error', null, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }
}