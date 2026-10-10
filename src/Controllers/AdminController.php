<?php
namespace App\Controllers;

use Twig\Environment;
use App\Models\PostModel;
use App\Models\OptionsModel;
use App\Models\UserModel;
use App\Models\AuthModel;
use App\Models\NewsModel;
use App\Models\AdminModel;
use App\Services\HelperService;

class AdminController {
    private $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function statistics($page = 1) {
        $userModel = new UserModel;
        $postModel = new PostModel;
        $adminModel = new AdminModel;

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
            'audit' => $adminModel->fetchAuditLog(15, ($page - 1)*15),
            'page' => $page
        ]);
    }

    public function site() {
        $userModel = new UserModel;
        $optionsModel = new OptionsModel;

        if(empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in'];
            header('Location: /login');
            exit;
        }

        $admin_level = $userModel->fetchAdminStatus($_SESSION['user_id']);

        if($admin_level < 3) {
            if($admin_level > 0) {
                $_SESSION['notification'] = ['error', 'You do not have access to this tool'];
                header('Location: /admin');
                exit;
            }
            $_SESSION['notification'] = ['error', 'Unauthorized'];
            header('Location: /');
            exit;
        }

        echo $this->twig->render('core/admin/site.twig', [
            'tool_name' => 'site',
            'tool_title' => 'Site Configuration',
            'tool_desc' => 'Manage the site and update info/branding',
            'site_name' => $optionsModel->getSiteName(),
            'domain' => $optionsModel->getDomain(),
            'slogan' => $optionsModel->getSlogan(),
            'about' => $optionsModel->getAbout(),
            'tos' => $optionsModel->getTos(),
            'privacy_policy' => $optionsModel->getPrivacyPolicy(),
            'community_guidelines' => $optionsModel->getCommunityGuidelines(),
            'ip_check' => $optionsModel->getIpCheck()
        ]);
    }

}