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
            'last_song' => json_decode($userModel->getLastSong($username), true),
            'page' => $page
        ]);
    }

    public function followers($username, $page = 1) {
        $postModel = new PostModel();
        $userModel = new UserModel();

        $user = $userModel->fetchAllUserDataByUsername($username);

        if (!$user || $userModel->fetchBanStatus($user['id']) == true) {
            $_SESSION['notification'] = ['error', 'This user does not exist or has been suspended!'];
            header('Location: /');
            exit;
        }

        $user_id = $user['id'];

        echo $this->twig->render('core/followers.twig', [
            'user' => $user,
            'followers' => $userModel->fetchFollowers($user_id, 15, ($page - 1)*15),
            'side_following' => $userModel->fetchFollowing($user_id, 10, 0),
            'side_followers' => $userModel->fetchFollowers($user_id, 10, 0),
            'is_following' => $userModel->isFollowing($_SESSION['user_id'] ?? null, $user_id),
            'last_song' => json_decode($userModel->getLastSong($username), true),
            'page' => $page
        ]);
    }

    public function following($username, $page = 1) {
        $postModel = new PostModel();
        $userModel = new UserModel();

        $user = $userModel->fetchAllUserDataByUsername($username);

        if (!$user || $userModel->fetchBanStatus($user['id']) == true) {
            $_SESSION['notification'] = ['error', 'This user does not exist or has been suspended!'];
            header('Location: /');
            exit;
        }

        $user_id = $user['id'];

        echo $this->twig->render('core/following.twig', [
            'user' => $user,
            'following' => $userModel->fetchFollowing($user_id, 15, ($page - 1)*15),
            'side_following' => $userModel->fetchFollowing($user_id, 10, 0),
            'side_followers' => $userModel->fetchFollowers($user_id, 10, 0),
            'is_following' => $userModel->isFollowing($_SESSION['user_id'] ?? null, $user_id),
            'last_song' => json_decode($userModel->getLastSong($username), true),
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

    public function profile() {
        if(empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        $userModel = new UserModel();
        echo $this->twig->render('core/profile.twig', [
            'profile' => $userModel->fetchAllUserData($_SESSION['user_id'])
        ]);
    }

    public function updateProfile() {
        // this function is like partially gpt srry
        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        $userModel = new UserModel();
        $optionsModel = new \App\Models\OptionsModel();
        $mailService = new \App\Services\MailService($this->twig);
        $helperService = new \App\Services\HelperService($this->twig);

        $user_id = $_SESSION['user_id'];
        $current_user = $userModel->fetchAllUserData($user_id);

        if (!$current_user) {
            $_SESSION['notification'] = ['error', 'Something went wrong!'];
            header('Location: /');
            exit;
        }

        $display_name = trim($_POST['display_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $lastfm_username = trim($_POST['lastfm_username'] ?? '');

        if (strlen($display_name) > 50) {
            $_SESSION['notification'] = ['error', 'Your display name must be less than 50 characters!'];
            header('Location: /profile');
            exit;
        }

        if (strlen($bio) > 500) {
            $_SESSION['notification'] = ['error', 'Your biography must be less than 500 characters!'];
            header('Location: /profile');
            exit;
        }

        if (strlen($location) > 100) {
            $_SESSION['notification'] = ['error', 'Your location must be less than 100 characters!'];
            header('Location: /profile');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['notification'] = ['error', 'Please enter a valid email address!'];
            header('Location: /profile');
            exit;
        }

        if (strlen($lastfm_username) > 50) {
            $_SESSION['notification'] = ['error', 'Your Last.FM username must be less than 50 characters!'];
            header('Location: /profile');
            exit;
        }

        $avatar = $current_user['avatar'];
        $banner = $current_user['banner'];

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== 4) {
            $upload_error = null;

            $new_avatar = $helperService->uploadImageFile('avatar', '/dynamic/avatars', 5000000, ['png', 'jpg', 'jpeg', 'gif', 'webp'], function($message) use (&$upload_error) {
                $upload_error = $message;
            });

            if ($upload_error) {
                $_SESSION['notification'] = ['error', $upload_error];
                header('Location: /profile');
                exit;
            }

            $avatar = $new_avatar;
        }

        if (isset($_FILES['banner']) && $_FILES['banner']['error'] !== 4) {
            $upload_error = null;

            $new_banner = $helperService->uploadImageFile('banner', '/dynamic/banners', 10000000, ['png', 'jpg', 'jpeg', 'gif', 'webp'], function($message) use (&$upload_error) {
                $upload_error = $message;
            });

            if ($upload_error) {
                $_SESSION['notification'] = ['error', $upload_error];
                header('Location: /profile');
                exit;
            }

            $banner = $new_banner;
        }

        $result = $userModel->updateProfile($user_id, $display_name, $bio, $location, $avatar, $banner, $email, $lastfm_username);

        if (!$result['success']) {
            $_SESSION['notification'] = ['error', $result['error'] ?? 'Something went wrong!'];
            header('Location: /profile');
            exit;
        }

        if ($result['email_changed']) {
            $site_name = $optionsModel->getSiteName();
            $domain = $optionsModel->getDomain();
            $verify_token = $result['verify_token'];

            $email_html = '
                <p>Hello ' . ($display_name ?: $current_user['username']) . '!</p>
                <p>Your email address for your ' . $site_name . ' account has been changed.</p>
                <p>To verify your new email address, click <a href="https://' . $domain . '/verify/' . $verify_token . '">here</a>.</p>
                <p>If this wasn\'t you, please ignore this email.</p>
            ';

            $mailService->sendEmail($email, 'Verify your new email address', $email_html);

            $_SESSION['notification'] = ['success', 'Your profile has been updated! Please check your new email for a verification link.'];
        } else {
            $_SESSION['notification'] = ['success', 'Your profile has been updated!'];
        }

        header('Location: /profile');
        exit;
    }




}