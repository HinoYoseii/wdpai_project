<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';

class SecurityController extends AppController
{
    private $userRepository;
    public function __construct()
    {
        $this->userRepository = UserRepository::getInstance();
        if(isset($_SESSION['username'])){
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
            return $this->render('login', ['messages' => 'Fill all fields']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
            return $this->render('login', ['messages' => 'Invalid email format']); 
        } 

        $userRow = $this->userRepository->getUserByEmail($email);

        if (!$userRow) {
            return $this->render('login', ['messages' => 'Wrong password or email']);
        }

        if (!password_verify($password, $userRow['hashedpassword'])) {
            return $this->render('login', ['messages' => 'Wrong password or email']);
        }

        session_regenerate_id(true);

        $_SESSION['username'] = $userRow['username'];

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
    }

    public function register(){
        if(!$this->isPost()){
            return $this->render('register');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password1'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $username = $_POST['username'] ?? '';

        if(empty($email) || empty($password) || empty($password2)  || empty($username)){
            return $this->render('register', ['messages' => 'Fill all fields']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
            return $this->render('login', ['messages' => 'Invalid email format']); 
        } 

        if($password !== $password2){
            return $this->render('register', ['messages' => 'Passwords do not match']);
        }
        if($this->userRepository->getUserByEmail($email)){
            return $this->render('register', ['messages' => 'User with this email already exists']);
        }
        if($this->userRepository->getUserByUsername($username)){
            return $this->render('register', ['messages' => 'Username already taken']);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $this->userRepository->createUser($email, $hashedPassword, $username);

        return $this->render('login', ['messages' => 'Account created successfully. Please log in.']);
    }

    public function logout(){
        if(!$this->isPost()){
            return $this->render('account');
        }
        setcookie("username", "", time() - 3600, "/");
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        
        session_unset(); 
        session_destroy();
    }
}
