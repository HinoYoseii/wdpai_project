<?php
require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/CategoriesController.php';
require_once 'src/controllers/AccountController.php';

class Routing {
    private static ?Routing $instance = null;

    public static function getInstance(): Routing
    {
        if (self::$instance === null) {
            self::$instance = new Routing();
        }
        return self::$instance;
    }


    private static array $routes = [
        'login' => [
            'controller' => 'SecurityController',
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
            'controller' => 'DashboardController',
            'action' => 'dashboard'
        ],
        'categories' => [
            'controller' => 'CategoriesController',
            'action' => 'categories'
        ],
        'account' => [
            'controller' => 'AccountController',
            'action' => 'account'
        ],
        'getTasks' => [
            'controller' => 'DashboardController',
            'action' => 'getTasks'
        ],
        'getCategories' => [
            'controller' => 'CategoriesController',
            'action' => 'getCategories'
        ],
        'createCategory' => [
            'controller' => 'CategoriesController',
            'action' => 'createCategory'
        ],
        'updateCategory' => [
            'controller' => 'CategoriesController',
            'action' => 'updateCategory'
        ],
        'deleteCategory' => [
            'controller' => 'CategoriesController',
            'action' => 'deleteCategory'
        ],
        'getFinishedTasks' => [
            'controller' => 'DashboardController',
            'action' => 'getFinishedTasks'
        ],
        'getTask' => [
            'controller' => 'DashboardController',
            'action' => 'getTask'
        ],
        'createTask' => [
            'controller' => 'DashboardController',
            'action' => 'createTask'
        ],
        'updateTask' => [
            'controller' => 'DashboardController',
            'action' => 'updateTask'
        ],
        'deleteTask' => [
            'controller' => 'DashboardController',
            'action' => 'deleteTask'
        ],
        'finishTask' => [
            'controller' => 'DashboardController',
            'action' => 'finishTask'
        ],
        'unfinishTask' => [
            'controller' => 'DashboardController',
            'action' => 'unfinishTask'
        ],
        'pinTask' => [
            'controller' => 'DashboardController',
            'action' => 'pinTask'
        ]
    ];

    public function run(string $path) {
        if (array_key_exists($path, self::$routes) && !isset(self::$routes[$path]['pattern'])) {
            $controllerName = self::$routes[$path]['controller'];
            $action         = self::$routes[$path]['action'];

            $controller = new $controllerName();
            $controller->$action();

            return;
        }

        foreach (self::$routes as $route) {
            if (!isset($route['pattern'])) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                $controllerName = $route['controller'];
                $action         = $route['action'];

                $controller = new $controllerName();

                $params = array_slice($matches, 1);

                $controller->$action(...$params);
                return;
            }
        }

        include 'public/views/404.html';
    }
}