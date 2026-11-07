<?php
session_start();

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
    "debug" => false
]);

// load models
$optionsModel = new \App\Models\OptionsModel();
$authModel = new \App\Models\AuthModel();

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

if (empty($_SESSION['csrf']) ) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$twig->addGlobal('csrf', $_SESSION['csrf']);

$twig->addFilter(new TwigFilter('timeAgo', [$helperService, 'timeAgo']));
$twig->addFilter(new TwigFilter('linkify', [$helperService, 'linkify'], ['is_safe' => ['html']]));

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

$router->get('/page/(\d+)', function($page) use ($pageController) {
    $pageController->index($page);
});

// explore

$router->get('/explore/(\d+)', function($page) use ($pageController) {
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

$router->get('/status/{status}/(\d+)', function ($status, $page) use ($postController) {
    $postController->status($status, $page);
});

$router->get('/status/{status}', function ($status) use ($postController) {
    $postController->status($status, 1);
});

// user

$router->get('/user/{username}/(\d+)', function ($username, $page) use ($userController) {
    $userController->user($username, $page);
});

$router->get('/user/{username}', function ($username) use ($userController) {
    $userController->user($username, 1);
});

// profile customization

$router->get('/profile', function () use ($userController) {
    $userController->profile();
});

$router->post('/profile', function () use ($userController, $authModel) {
    $authModel->verifyCsrf();
    $userController->updateProfile();
});

// post actions

$router->post('/like/{id}', function($id) use ($postController, $authModel) {
    $authModel->verifyCsrf();
    $postController->like($id);
});

$router->post('/delete/{id}', function($id) use ($postController, $authModel) {
    $authModel->verifyCsrf();
    $postController->delete($id);
});

// follow user 
$router->post('/follow/{username}', function($username) use ($userController, $authModel) {
    $authModel->verifyCsrf();
    $userController->followUser($username);
});

// following/followers pages

$router->get('/followers/{username}/(\d+)', function ($username, $page) use ($userController) {
    $userController->followers($username, $page);
});

$router->get('/followers/{username}', function($username) use ($userController) {
    $userController->followers($username, 1);
});

$router->get('/following/{username}/(\d+)', function ($username, $page) use ($userController) {
    $userController->following($username, $page);
});

$router->get('/following/{username}', function($username) use ($userController) {
    $userController->following($username, 1);
});

// post reporting

$router->get('/report/{post}', function($post) use ($postController) {
    $postController->reportForm($post);
});

$router->post('/report/{post}', function($post) use ($postController, $authModel) {
    $authModel->verifyCsrf();
    $postController->report($post);
});

// acc registration

$router->get('/register', function() use ($authController) {
    $authController->registerForm();
});

$router->post('/register', function() use ($authController, $authModel) {
    $authModel->verifyCsrf();
    $authController->register();
});

// acc login

$router->get('/login', function() use ($authController) {
    $authController->loginForm();
});

$router->post('/login', function() use ($authController, $authModel) {
    $authModel->verifyCsrf();
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

$router->post('/contact', function() use ($pageController, $authModel) {
    $authModel->verifyCsrf();
    $pageController->contact();
});

// about 

$router->get('/about', function() use ($pageController) {
    $pageController->about();
});

// admin

$router->get('/admin/(\d+)', function($page) use ($adminController) {
    $adminController->statistics($page);
});

$router->get('/admin', function() use ($adminController) {
    $adminController->statistics(1);
});


$router->get('/admin/site', function() use ($adminController) {
    $adminController->site(1);
});

$router->post('/admin/site', function() use ($adminController, $authModel) {
    $authModel->verifyCsrf();
    $adminController->updateSite();
});


$router->get('/admin/reports/(\d+)', function($page) use ($adminController) {
    $adminController->reports($page);
});

$router->get('/admin/reports', function() use ($adminController) {
    $adminController->reports(1);
});

$router->post('/admin/reports/dismiss/(\d+)', function($id) use ($adminController, $authModel) {
    $authModel->verifyCsrf();
    $adminController->dismissReport($id);
});


$router->get('/admin/users/(\d+)', function($page) use ($adminController) {
    $adminController->users($page, $_GET['query'] ?? '');
});

$router->get('/admin/users', function() use ($adminController) {
    $adminController->users($_GET['page'] ?? 1, $_GET['query'] ?? '');
});

$router->get('/admin/users/search', function() use ($adminController) {
    $adminController->users($_GET['page'] ?? 1, $_GET['query'] ?? '');
});

$router->get('/admin/users/edit/(\w+)', function($username) use ($adminController) {
    $adminController->editUser($username);
});

$router->post('/admin/users/edit/(\w+)', function($username) use ($adminController, $authModel) {
    $authModel->verifyCsrf();
    $adminController->updateUser($username);
});

$router->get('/admin/users/ip/(\w+)', function($username) use ($adminController) {
    $adminController->userIpHistory($username);
});


$router->get('/admin/contact/(\d+)', function($page) use ($adminController) {
    $adminController->contact($page);
});

$router->get('/admin/contact', function() use ($adminController) {
    $adminController->contact(1);
});

$router->get('/admin/news', function() use ($adminController) {
    $adminController->news();
});

$router->post('/admin/news', function() use ($adminController, $authModel) {
    $authModel->verifyCsrf();
    $adminController->createNews();
});

$router->post('/admin/news/delete/(\d+)', function($id) use ($adminController, $authModel) {
    $authModel->verifyCsrf();
    $adminController->deleteNews($id);
});


$router->post('/admin/site/update', function() use ($adminController, $authModel) {
    $authModel->verifyCsrf();
    $adminController->pullUpdates();
});


$router->get('/admin/login/(\w+)', function($username) use ($adminController) {
    $adminController->loginAs($username);
});


$router->get('/admin/users/alts/(\w+)', function($username) use ($adminController) {
    $adminController->altAccounts($username);
});

// post

$router->post('/post', function() use ($postController, $authModel) {
    $authModel->verifyCsrf();
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