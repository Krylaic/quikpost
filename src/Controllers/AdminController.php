<?php
namespace App\Controllers;

use Twig\Environment;
use App\Models\PostModel;
use App\Models\OptionsModel;
use App\Models\UserModel;
use App\Models\AuthModel;
use App\Models\NewsModel;
use App\Services\HelperService;

class AdminController {
    private $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function statistics() {
        $userModel = new UserModel;
        $postModel = new PostModel;

        if(empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in'];
            header('Location: /login');
            exit;
        }

        if($userModel->fetchAdminStatus($_SESSION['user_id']) <= 0) {
            $_SESSION['notification'] = ['error', 'Unauthorized'];
            header('Location: /');
            exit;
        }

        echo $this->twig->render('core/admin/statistics.twig', [
            'tool_name' => 'statistics',
            'tool_title' => 'Statistics',
            'tool_desc' => 'View site statistics and audit log',
            'all_users' => $userModel->getCountAllUsers(),
            'banned_users' => $userModel->getCountBannedUsers(),
            'unbanned_users' => $userModel->getCountUnbannedUsers(),
            'verified_users' => $userModel->getCountVerifiedUsers(),
            'admin_users' => $userModel->getCountAdminUsers(),
            'follows' => $userModel->getCountFollows(),
            'posts' => $postModel->getCountAllPosts(),
        ]);
    }

}