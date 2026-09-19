<?php
namespace App\Controllers;

use Twig\Environment;
use App\Models\PostModel;
use App\Models\UserModel;

class UserController {
    private $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function user($username, $page = 1) {
        $postModel = new PostModel();
        $userModel = new UserModel();

        $user = $userModel->fetchAllUserDataByUsername($username);

        if (!$user || $userModel->fetchBanStatus($user['id']) == true) {
            $_SESSION['notification'] = ['error', 'This user does not exist or has been suspended!'];
            header('Location: /');
            exit;
        }

        $user_id = $user['id'];

        echo $this->twig->render('core/user.twig', [
            'user' => $user,
            'posts' => $postModel->getPostsByUserId($user_id, 15, ($page - 1)*15),
            'following' => $userModel->fetchFollowing($user_id, 10, 0),
            'followers' => $userModel->fetchFollowers($user_id, 10, 0),
            'is_following' => $userModel->isFollowing($_SESSION['user_id'] ?? null, $user_id),
            'page' => $page
        ]);
    }

    public function followUser($username) {
        $userModel = new UserModel();

        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        $user = $userModel->fetchAllUserDataByUsername($username);

        if (!$user || $userModel->fetchBanStatus($user['id']) == true) {
            $_SESSION['notification'] = ['error', 'This user does not exist or has been suspended!'];
            header('Location: /');
            exit;
        }

        $target_id = $user['id'];
        $follower_id = $_SESSION['user_id'];

        $result = $userModel->followUser($target_id, $follower_id);

        if (isset($result['error'])) {
            $_SESSION['notification'] = ['error', $result['error']];
        }

        header('Location: /user/' . $username);
        exit;
    }
}