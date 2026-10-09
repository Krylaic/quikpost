<?php
session_start();

//debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

// external dependencies
use Bramus\Router\Router;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\TwigFilter;
use Dotenv\Dotenv;

// the site
use App\Models\UserModel;
use App\Controllers\PageController;
use App\Controllers\PostController;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$loader = new FilesystemLoader(__DIR__ . '/../src/Views');
$twig = new Environment($loader, [
    "cache" => false,
    "debug" => true
]);

// load models
$optionsModel = new \App\Models\OptionsModel();

// load controllers
$pageController = new \App\Controllers\PageController($twig);
$authController = new \App\Controllers\AuthController($twig);
$postController = new \App\Controllers\PostController($twig);
$userController = new \App\Controllers\UserController($twig);
$adminController = new \App\Controllers\AdminController($twig);

// load services
$helperService = new \App\Services\HelperService($twig);

$twig->addGlobal('site_name', $optionsModel->getSiteName());
$twig->addGlobal('slogan', $optionsModel->getSlogan());
$twig->addGlobal('domain', $optionsModel->getDomain());
$twig->addFilter(new TwigFilter('timeAgo', [$helperService, 'timeAgo']));

// set notification 
if (!empty($_SESSION['notification'])) {
    $twig->addGlobal($_SESSION['notification'][0], $_SESSION['notification'][1]);
    unset($_SESSION['notification']);
}

// user stuff
if (!empty($_SESSION['user_id'])) {
    $userModel = new UserModel();

    if($userModel->fetchBanStatus($_SESSION['user_id']) === false) {
        $userModel->updateIp($_SESSION['user_id'], $_SERVER['REMOTE_ADDR']);

        $current_user = $userModel->fetchAllUserData($_SESSION['user_id']);
        $twig->addGlobal('current_user', $current_user);
    } else {
        unset($_SESSION['user_id']);
        $_SESSION['notification'] = ['error', 'Your account has been suspended.'];
        header('Location: /');
        exit;
    }
    
}


$router = new Router();

// routes

// homepage
$router->get('/', function() use ($pageController) {
    $pageController->index();
});

$router->get('/page/{page}', function($page) use ($pageController) {
    $pageController->index($page);
});

// explore

$router->get('/explore/{page}', function($page) use ($pageController) {
    $pageController->explore($page);
});

$router->get('/explore', function() use ($pageController) {
    $pageController->explore();
});

// search

$router->get('/search', function() use ($postController) {
    $postController->search($_GET['query'] ?? '', $_GET['page'] ?? 1);
});

// news

$router->get('/news', function() use ($pageController) {
    $pageController->news();
});

// status 

$router->get('/status/{status}/{page}', function ($status, $page) use ($postController) {
    $postController->status($status, $page);
});

$router->get('/status/{status}', function ($status) use ($postController) {
    $postController->status($status, 1);
});

// user

$router->get('/user/{username}/{page}', function ($username, $page) use ($userController) {
    $userController->user($username, $page);
});

$router->get('/user/{username}', function ($username) use ($userController) {
    $userController->user($username, 1);
});

// profile customization

$router->get('/profile', function () use ($userController) {
    $userController->profile();
});

$router->post('/profile', function () use ($userController) {
    $userController->updateProfile();
});

// post actions

$router->get('/like/{id}', function($id) use ($postController) {
    $postController->like($id);
});

$router->get('/delete/{id}', function($id) use ($postController) {
    $postController->delete($id);
});

// follow user 
$router->get('/follow/{username}', function($username) use ($userController) {
    $userController->followUser($username);
});

// following/followers pages

$router->get('/followers/{username}/{page}', function ($username, $page) use ($userController) {
    $userController->followers($username, $page);
});

$router->get('/followers/{username}', function($username) use ($userController) {
    $userController->followers($username, 1);
});

$router->get('/following/{username}/{page}', function ($username, $page) use ($userController) {
    $userController->following($username, $page);
});

$router->get('/following/{username}', function($username) use ($userController) {
    $userController->following($username, 1);
});

// post reporting

$router->get('/report/{post}', function($post) use ($postController) {
    $postController->reportForm($post);
});

$router->post('/report/{post}', function($post) use ($postController) {
    $postController->report($post);
});

// acc registration

$router->get('/register', function() use ($authController) {
    $authController->registerForm();
});

$router->post('/register', function() use ($authController) {
    $authController->register();
});

// acc login

$router->get('/login', function() use ($authController) {
    $authController->loginForm();
});

$router->post('/login', function() use ($authController) {
    $authController->login();
});

// documentation pages

$router->get('/tos', function() use ($pageController) {
    $pageController->tos();
});

$router->get('/privacy', function() use ($pageController) {
    $pageController->privacy();
});

$router->get('/community_guidelines', function() use ($pageController) {
    $pageController->guidelines();
});

// contact

$router->get('/contact', function() use ($pageController) {
    $pageController->contactForm();
});

$router->post('/contact', function() use ($pageController) {
    $pageController->contact();
});

// admin

$router->get('/admin', function() use ($adminController) {
    $adminController->statistics();
});


// post

$router->post('/post', function() use ($postController) {
    $postController->createPost();
});

$router->get('/captcha', function() use ($authController) {
    header('Content-Type: image/png');
    $authController->returnCaptcha();
});

$router->get('/logout', function() use ($authController) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['notification'] = ['success', 'You have successfully logged out'];
    header('Location: /');
    die();
});

$router->get('/verify/{verify_token}', function($verify_token) use ($authController) {
    $authController->verify($verify_token);
});

$router->set404(function() use ($twig) {
    header('HTTP/1.1 404 Not Found');
    echo $twig->render('404.twig');
});

// party time
$router->run();