<?php

require_once 'Repository.php';
//require_once __DIR__.'/../models/User.php';

# po podłączeniu bazy se trzeba zrobić tabele związaną z aplikacją
# zrobić rejestracje a potem logowanie, żeby za każdym raze nie tworzyć user repository to zrobić w kontrolerze

class UserRepository extends Repository
{
    private static $instance; 
    
    public static function getInstance() { 
        return self::$instance ??= new UserRepository(); 
    } 

    public function getUsers(): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM users;
        ');
        $stmt->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $users;
    }

    public function getUser(string $email): ?User
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM users WHERE email = :email
        ');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user == false) {
            return null;
        }

        return new User(
            $user['email'],
            $user['password'],
            $user['username']
        );
    }

    public function getUserByEmail(string $email)
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM users WHERE email = :email
        ');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user == false) {
            return null;
        }
        // todo disconnect
        return $user;
    }

     public function getUserByUsername(string $username): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM users WHERE username = :username
        ');
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user == false) {
            return null;
        }
        // todo disconnect
        return $user;
    }


    // Add to UserRepository.php
    public function createUser(
        string $email,
        string $hashedPassword,
        string $username
    ){
        $stmt = $this->database->connect()->prepare(
            '
            INSERT INTO public.users (email, hashedpassword, username) VALUES (?,?,?)
            RETURNING userid
            '
        );
        $stmt->execute([
            $email,
            $hashedPassword,
            $username
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId = $result['userid'];
        
        $preferencesRepository = PreferencesRepository::getInstance();
        $preferencesRepository->createPreferences($userId);
        
        return $userId;
    }

}