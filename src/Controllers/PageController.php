<?php
namespace App\Controllers;

use Twig\Environment;
use App\Models\OptionsModel;
use App\Models\NewsModel;
use App\Models\PostModel;
use App\Models\UserModel;
use App\Models\SiteModel;
use App\Models\AuthModel;


class PageController {
    private $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function index($page = 1) {
        $optionsModel = new OptionsModel();
        $newsModel = new NewsModel();
        $postModel = new PostModel();
        $userModel = new UserModel();
        
        if(!empty($_SESSION['user_id'])) {
            echo $this->twig->render('core/feeds/home.twig', [
                'active_page' => "feed",
                'about' => $optionsModel->getAbout(),
                'news' => $newsModel->getLatestNews(),
                'posts' => $postModel->getFeedPosts(15, ($page - 1)*15, $_SESSION['user_id']),
                'users' => $userModel->fetchRecentUsers(10, 0),
                'page' => $page
            ]);
        } else {
            echo $this->twig->render('core/landing.twig', [
                'active_page' => "feed",
                'about' => $optionsModel->getAbout()
            ]);
        }
    }

    public function explore($page = 1) {
        $optionsModel = new OptionsModel();
        $newsModel = new NewsModel();
        $postModel = new PostModel();
        $userModel = new UserModel();
        
        echo $this->twig->render('core/feeds/explore.twig', [
            'active_page' => "explore",
            'about' => $optionsModel->getAbout(),
            'news' => $newsModel->getLatestNews(),
            'posts' => $postModel->getAllPosts(15, ($page - 1)*15),
            'users' => $userModel->fetchRecentUsers(10, 0),
            'page' => $page
        ]);
    }

    public function profile() {
        $optionsModel = new OptionsModel();
        $newsModel = new NewsModel();
        $postModel = new PostModel();
        
        echo $this->twig->render('core/feeds/explore.twig', [
            'active_page' => "explore",
            'about' => $optionsModel->getAbout(),
            'news' => $newsModel->getLatestNews(),
            'posts' => $postModel->getAllPosts(15, 0)
        ]);
    }

    public function news() {
        $optionsModel = new OptionsModel();
        $newsModel = new NewsModel();
        $postModel = new PostModel();
        
        echo $this->twig->render('core/news.twig', [
            'news' => $newsModel->getNews()
        ]);
    }

    public function tos() {
        $optionsModel = new OptionsModel();
        
        echo $this->twig->render('core/tos.twig', [
            'tos' => $optionsModel->getTos()
        ]);
    }

    public function privacy() {
        $optionsModel = new OptionsModel();
        
        echo $this->twig->render('core/privacy.twig', [
            'privacy' => $optionsModel->getPrivacyPolicy()
        ]);
    }

    public function guidelines() {
        $optionsModel = new OptionsModel();
        
        echo $this->twig->render('core/community_guidelines.twig', [
            'guidelines' => $optionsModel->getCommunityGuidelines()
        ]);
    }

    public function about() {
        $optionsModel = new OptionsModel();
        
        echo $this->twig->render('core/about.twig', [
            'about' => $optionsModel->getAbout()
        ]);
    }

    public function contactForm() {
        $optionsModel = new OptionsModel();
        
        echo $this->twig->render('core/contact.twig');
    }

    public function contact() {
        $authModel = new AuthModel();
        $siteModel = new SiteModel();

        $captcha = $_POST['captcha'] ?? '';
        $content = trim($_POST['content']) ?? '';
        $email = trim($_POST['email']) ?? '';

        // captcha validation
        if (!$authModel->verifyCaptcha($captcha)) {
            $_SESSION['notification'] = ['error', 'Incorrect captcha'];
            header('Location: /contact');
            exit;
        }

        // content validation
        if(empty($content)) {
            $_SESSION['notification'] = ['error', 'Please state a reason for contacting!'];
            header('Location: /contact');
            exit;
        }

        if(empty($email)) {
            $_SESSION['notification'] = ['error', 'Please enter your email!'];
            header('Location: /contact');
            exit;
        }

        $contacted = $siteModel->contact($content, $email);

        if($contacted['success']) {
            $_SESSION['notification'] = ['success', 'Message sent succesfully!'];
            header('Location: /');
            exit;
        } else {
            $_SESSION['notification'] = ['error', $contacted['error'] ?? 'Something went wrong'];
            header('Location: /');
            exit;
        }
    }
}
