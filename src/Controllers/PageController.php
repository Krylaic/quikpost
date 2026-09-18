<?php
namespace App\Controllers;

use Twig\Environment;
use App\Models\OptionsModel;
use App\Models\NewsModel;
use App\Models\PostModel;
use App\Models\UserModel;


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
}