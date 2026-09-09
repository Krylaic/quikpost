<?php
session_start();

//debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use Bramus\Router\Router;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Dotenv\Dotenv;
use App\Models\UserModel;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$optionsModel = new \App\Models\OptionsModel();

$loader = new FilesystemLoader(__DIR__ . '/../src/Views');
$twig = new Environment($loader, [
    "cache" => false,
    "debug" => true
]);

$twig->addGlobal('site_name', $optionsModel->getSiteName());
$twig->addGlobal('slogan', $optionsModel->getSlogan());
$twig->addGlobal('domain', $optionsModel->getDomain());

// set notification 
if (!empty($_SESSION['notification'])) {
    $twig->addGlobal($_SESSION['notification'][0], $_SESSION['notification'][1]);
    unset($_SESSION['notification']);
}

// user stuff
if (!empty($_SESSION['user_id'])) {
    $userModel = new UserModel();
    $userModel->updateIp($_SESSION['user_id'], $_SERVER['REMOTE_ADDR']);

    $current_user = $userModel->fetchAllUserData($_SESSION['user_id']);
    $twig->addGlobal('current_user', $current_user);
}


$router = new Router();

// routes
$router->get('/', function() use ($twig) {
    $controller = new \App\Controllers\PageController($twig);
    $controller->index();
});

$router->get('/register', function() use ($twig) {
    $controller = new \App\Controllers\AuthController($twig);
    $controller->registerForm();
});

$router->post('/register', function() use ($twig) {
    $controller = new \App\Controllers\AuthController($twig);
    $controller->register();
});

$router->get('/login', function() use ($twig) {
    $controller = new \App\Controllers\AuthController($twig);
    $controller->loginForm();
});

$router->post('/login', function() use ($twig) {
    $controller = new \App\Controllers\AuthController($twig);
    $controller->login();
});

$router->get('/captcha', function() use ($twig) {
    header('Content-Type: image/png');
    $controller = new \App\Controllers\AuthController($twig);
    $controller->returnCaptcha();
});

$router->get('/logout', function() use ($twig) {
    session_unset();
    session_destroy();
    $_SESSION['notification'] = ['success', 'You have successfully logged out'];
    header('Location: /');
    die();
});

$router->set404(function() use ($twig) {
    header('HTTP/1.1 404 Not Found');
    echo $twig->render('404.twig');
});

// party time
$router->run();