<?php


class AppController {
    
    protected function isGet(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'GET';
    }

    protected function isPost(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'POST';
    }

    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user']);
    }

    protected function getUserCookie(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    protected function requireLogin(): void
    {
        if (!$this->isAuthenticated()) {
            $this->render('login');
            exit();
        }
    }

    protected function requireAdmin(): void
    {
        if (!$this->isAuthenticated()) {
            $this->render('login');
            exit();
        }

        if (($this->getUserCookie()['role'] ?? null) !== 'admin') {
            http_response_code(403);
            $this->render('404');
            exit();
        }
    }

    protected function render(string $template = null, array $variables = [])
    {
        $templatePath = 'public/views/'. $template.'.html';
        $templatePath404 = 'public/views/404.html';
        $output = "";
                 
        if(file_exists($templatePath)){
            extract($variables);
            ob_start();
            include $templatePath;
            $output = ob_get_clean();
        } else {
            ob_start();
            include $templatePath404;
            $output = ob_get_clean();
        }
        echo $output;
    }

    protected function getJsonInput(): ?array {
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        
        if ($contentType !== "application/json") {
            return null;
        }

        $content = trim(file_get_contents("php://input"));
        return json_decode($content, true);
    }

    protected function jsonResponse(string $status, $data = null, string $message = '', int $httpCode = 200): void {
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

}