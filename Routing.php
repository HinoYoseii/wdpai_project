<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/CategoriesController.php';
require_once 'src/controllers/AccountController.php';

class Routing {

    public static $routes = [
        'login' => [
            'controller' => "SecurityController",
            'action' => 'login'
        ],
        'register' => [
            'controller' => 'SecurityController',
            'action' => 'register'
        ],
        'logout' => [
            'controller' => 'SecurityController',
            'action' => 'logout'
        ],
        'dashboard' => [
            'controller' => "DashboardController",
            'action' => 'dashboard'
        ],
        'categories' => [
            'controller' => "CategoriesController",
            'action' => 'categories'
        ],
        'account' => [
            'controller' => "AccountController",
            'action' => 'account'
        ],
        'search-cards' => [
            'controller' => "DashboardController",
            'action' => 'search'
        ]
    ];

    public static function run(string $path) {
        switch ($path) {
            case in_array($path, array_keys(Routing::$routes)):
                $controller = Routing::$routes[$path]['controller'];
                $action = Routing::$routes[$path]['action'];

                $controllerObj = new $controller;
                $controllerObj->$action();
                break;
            default:
                include 'public/views/404.html';
                break;
        } 
    }
}