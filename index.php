<?php

require 'Routing.php';

ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);

session_set_cookie_params([
    'lifetime' => 1800,
    'path' => '/',
    'domain' => 'localhost',
    'secure' => 'true',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

$routing = Routing::getInstance();
$routing->run($path);

?>