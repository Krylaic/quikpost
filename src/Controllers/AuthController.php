<?php
namespace App\Controllers;

use Twig\Environment;
use App\Models\OptionsModel;
use App\Services\MailService;
use App\Models\AuthModel;

class AuthController {
    private $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function registerForm() {
        $optionsModel = new OptionsModel();

        echo $this->twig->render('core/register.twig');
    }

    public function loginForm() {
        $optionsModel = new OptionsModel();

        echo $this->twig->render('core/login.twig');
    }

    public function returnCaptcha() {
        $authModel = new AuthModel();

        echo $authModel->genCaptcha();
    }

    public function register() {
        $optionsModel = new OptionsModel();
        $authModel = new AuthModel();
        $mailService = new MailService($this->twig);

        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password1'] ?? '';
        $confirm_password = $_POST['password2'] ?? '';
        $tos = isset($_POST['tos']);
        $captcha = $_POST['captcha'] ?? '';

        $site_name = $optionsModel->getSiteName();
        $domain = $optionsModel->getDomain();

        // captcha validation
        if (!$authModel->verifyCaptcha($captcha)) {
            $_SESSION['notification'] = ['error', 'Incorrect captcha'];
            header('Location: /register');
            exit;
        }

        // username validation
        if(empty($username) || strlen($username) < 3 || strlen($username) > 20) {
            $_SESSION['notification'] = ['error', 'Your username must be greater than 3 and less than 20 characters!'];
            header('Location: /register');
            exit;
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $_SESSION['notification'] = ['error', 'Your username can only contain letters, numbers, and underscores!'];
            header('Location: /register');  
            exit;
        } elseif($authModel->usernameExists($username)) {
            $_SESSION['notification'] = ['error', 'That username is taken!'];
            header('Location: /register');
            exit;
        } 
        
        // email validation
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['notification'] = ['error', 'Please enter a valid email!'];
            header('Location: /register');
            exit;
        }elseif($authModel->emailExists($email)) {
            $_SESSION['notification'] = ['error', 'This email has already been used!'];
            header('Location: /register');
            exit;
        } 
        
        // password validation
        if (strlen($password) < 8) {
            $_SESSION['notification'] = ['error', 'Password must be 8 characters!'];
            header('Location: /register');
            exit;
        } elseif($password !== $confirm_password){
            $_SESSION['notification'] = ['error', 'Password doesn\'t match!'];
            header('Location: /register');
            exit;
        } 

        // tos validation
        if (!$tos) {
            $_SESSION['notification'] = ['error', 'Please read and accept our terms of service!'];
            header('Location: /register');
            exit;
        }

        $password = password_hash($password, PASSWORD_DEFAULT);

        // here we go !
        $registered = $authModel->registerUser($username, $email, $password);

        if ($registered['success']) {
            $verify_token = $registered['token'];
            $email_html = '
                <p>Welcome to ' . $site_name . ', ' . $username . '!</p>
                <p>To complete registration, click <a href="https://' . $domain . '/verify/' . $verify_token . '">here</a> to verify your account!</p>
                <p>If this wasn\'t you, please ignore this email.</p>
            ';
            $mailService->sendEmail($email, 'Welcome to ' . $site_name . '!', $email_html);

            $_SESSION['notification'] = ['success', 'Your account has been successfully registered! Please check your inbox for an email.'];
            header('Location: /');
            exit;
        } else {
            $_SESSION['notification'] = ['error', $registered['error'] ?? 'Something went wrong on our end during registration.'];
            header('Location: /register');
            exit;
        }
    }

    public function login() {
        $authModel = new AuthModel();

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // username validation
        if(empty($username) || strlen($username) < 3 || strlen($username) > 20) {
            $_SESSION['notification'] = ['error', 'A username must be greater than 3 and less than 20 characters!'];
            header('Location: /login');
            exit;
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $_SESSION['notification'] = ['error', 'A username can only contain letters, numbers, and underscores!'];
            header('Location: /login');  
            exit;
        } elseif(!$authModel->usernameExists($username)) {
            $_SESSION['notification'] = ['error', 'That username doesn\'t exist!'];
            header('Location: /login');
            exit;
        }

        // password validation
        if (strlen($password) < 8) {
            $_SESSION['notification'] = ['error', 'Password must be 8 characters!'];
            header('Location: /login');
            exit;
        }

        // verify the password
        if($authModel->authenticatePassword($username, $password)) {
            $_SESSION['notification'] = ['success', 'Login successful!'];
            header('Location: /');
            exit;
        } else {
            $_SESSION['notification'] = ['error', 'Incorrect password!'];
            header('Location: /login');
            exit;
        }
    }
}