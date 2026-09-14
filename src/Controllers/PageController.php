<?php
namespace App\Controllers;

use Twig\Environment;
use App\Models\OptionsModel;
use App\Models\NewsModel;
use App\Models\PostModel;

class PageController {
    private $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function index() {
        $optionsModel = new OptionsModel();
        $newsModel = new NewsModel();
        $postModel = new PostModel();
        
        if(!empty($_SESSION['user_id'])) {
            echo $this->twig->render('core/feeds/home.twig', [
                'active_page' => "feed",
                'about' => $optionsModel->getAbout(),
                'news' => $newsModel->getLatestNews(),
                'posts' => $postModel->getFeedPosts(15, 0, $_SESSION['user_id'])
            ]);
        } else {
            echo $this->twig->render('core/landing.twig', [
                'active_page' => "feed",
                'about' => $optionsModel->getAbout()
            ]);
        }
    }

    public function explore() {
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