<?php
namespace App\Controllers;

use Twig\Environment;
use App\Models\OptionsModel;
use App\Models\NewsModel;
use App\Models\PostModel;
use App\Models\UserModel;


class UserController {
    private $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function user($username, $page = 1) {
        $optionsModel = new OptionsModel();
        $newsModel = new NewsModel();
        $postModel = new PostModel();
        $userModel = new UserModel();

        if($userModel->fetchBanStatusByUsername($username) == true) {
            $_SESSION['notification'] = ['error', 'This user does not exist or has been suspended!'];
            header('Location: /');
            exit;
        }
        
        echo $this->twig->render('core/user.twig', [
            'user' => $userModel->fetchAllUserDataByUsername($username),
            'posts' => $postModel->getPostsByUsername($username),
            'page' => $page
        ]);
    }

}