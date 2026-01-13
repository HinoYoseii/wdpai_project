<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';

class SecurityController extends AppController
{
    private $userRepository;
    public function __construct()
    {
        $this->userRepository = UserRepository::getInstance();
        if($this->isAuthenticated()){
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/dashboard");
        }
    }

    public function login()
    {
        if (!$this->isPost()) {
            $_SESSION['csrf'] = md5(uniqid(mt_rand(), true));
            return $this->render('login');
        }

        if ($_POST['csrf'] !== $_SESSION['csrf']) die("CSRF detected"); 

        $email = trim($_POST["email"] ?? '');
        $password = $_POST["password"] ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('login', ['messages' => 'Wypełnij wszystkie pola.']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
            return $this->render('login', ['messages' => 'Niepoprawny format adresu e-mail.']); 
        } 

        $userRow = $this->userRepository->getUserByEmail($email);

        if (!$userRow) {
            return $this->render('login', ['messages' => 'Błędny adres e-mail lub hasło.']);
        }

        if (!password_verify($password, $userRow['hashedpassword'])) {
            return $this->render('login', ['messages' => 'Błędny adres e-mail lub hasło.']);
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $userRow['userid'] ?? null,
            'email' => $userRow['email'] ?? null,
            'username' => $userRow['username'] ?? null,
            'role' => $userRow['userrole'] ?? null
        ];

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
    }

    public function register(){
        if(!$this->isPost()){
            $_SESSION['csrf'] = md5(uniqid(mt_rand(), true));
            return $this->render('register');
        }

        if ($_POST['csrf'] !== $_SESSION['csrf']) die("CSRF detected"); 

        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/';
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password1'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $username = $_POST['username'] ?? '';

        if(strlen($email) > 100 or strlen($password) > 100 or strlen($password) > 100 or strlen($username) > 100){
            return $this->render('register', ['messages' => 'Niepoprawna długość.']);
        } 

        if(empty($email) || empty($password) || empty($password2)  || empty($username)){
            return $this->render('register', ['messages' => 'Wypełnij wszystkie pola.']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
            return $this->render('login', ['messages' => 'Niepoprawny format adresu e-mail.']); 
        } 
        if(!preg_match($pattern, $password)){
            return $this->render('register', ['messages' => 
            'Hasło musi mieć przynajmniej 8 znaków i zawierać: przynajmniej jedną wielką literę, przynajmniej jedną małą literę, przynajmniej jedną cyfrę']);
        }

        if($password !== $password2){
            return $this->render('register', ['messages' => 'Hasła się nie zgadzają']);
        }
        if($this->userRepository->getUserByEmail($email)){
            return $this->render('register', ['messages' => 'Użytkownik z tym adresem e-mail już istnieje. Spróbuj się zalogować.']);
        }
        if($this->userRepository->getUserByUsername($username)){
            return $this->render('register', ['messages' => 'Nazwa użytkownika jest już zajęta.']);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $this->userRepository->createUser($email, $hashedPassword, $username);

        return $this->render('login', ['confirmation' => 'Konto utworzone pomyślnie. Prosze się zalogować.']);
    }

    public function logout(){
        if(!$this->isPost()){
            return $this->render('account');
        }
        setcookie("user", "", time() - 3600, "/");
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_unset(); 
        session_destroy();
    }
}
