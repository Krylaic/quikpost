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

    public function updateSite() {
        $userModel = new UserModel;
        $adminModel = new AdminModel;

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

        $site_name = trim($_POST['site_name'] ?? '') ?: null;
        $slogan = trim($_POST['slogan'] ?? '') ?: null;
        $domain = trim($_POST['domain'] ?? '') ?: null;

        if(isset($_POST['ip_check'])) {
            $ip_check = 1;
        } else {
            $ip_check = 0;
        }

        $about = trim($_POST['about'] ?? '') ?: null;
        $tos = trim($_POST['tos'] ?? '') ?: null;
        $privacy_policy = trim($_POST['privacy_policy'] ?? '') ?: null;
        $community_guidelines = trim($_POST['community_guidelines'] ?? '') ?: null;

        if(empty($domain)) {
            $_SESSION['notification'] = ['error', 'Domain requires a value'];
            header('Location: /admin/site');
            exit;
        }

        $updated = $adminModel->updateSite($site_name, $slogan, $domain, $ip_check, $about, $tos, $privacy_policy, $community_guidelines);

        if($updated == true) {
            $_SESSION['notification'] = ['success', 'Site configuration successfully updated!'];
            header('Location: /admin/site');
            exit;
        } else {
            $_SESSION['notification'] = ['error', 'Error updating site configuration'];
            header('Location: /admin/site');
            exit;
        }
    }



    public function reports($page = 1) {
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

        echo $this->twig->render('core/admin/reports.twig', [
            'tool_name' => 'reports',
            'tool_title' => 'Reports',
            'tool_desc' => 'Manage post reports',
            'reports' => $adminModel->fetchReports(15, ($page - 1)*15),
            'page' => $page
        ]);
    }

    public function users($page = 1, $query = '') {
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

        $page = max(1, (int)$page);
        $query = trim($query);

        if (!empty($query)) {
            $users = $adminModel->searchUsers($query, 15, ($page - 1) * 15);
        } else {
            $users = $adminModel->fetchAllUsers(15, ($page - 1) * 15);
        }

        echo $this->twig->render('core/admin/users.twig', [
            'tool_name' => 'users',
            'tool_title' => 'Users',
            'tool_desc' => 'Manage, view, and update information about users',
            'users' => $users,
            'page' => $page,
            'query' => $query
        ]);
    }


    public function dismissReport($id) {
        $userModel = new UserModel;
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

        $dismissed = $adminModel->dismissReport($id);

        if($dismissed) {
            $_SESSION['notification'] = ['success', 'Report successfully dismissed!'];
            header('Location: /admin/reports');
            exit;
        } else {
            $_SESSION['notification'] = ['error', 'Error dismissing report'];
            header('Location: /admin/reports');
            exit;
        }
    }

    public function editUser($username) {
        $userModel = new UserModel;
        $postModel = new PostModel;

        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in'];
            header('Location: /login');
            exit;
        }

        $current_admin_level = $userModel->fetchAdminStatus($_SESSION['user_id']);

        if ($current_admin_level <= 0) {
            $_SESSION['notification'] = ['error', 'Unauthorized'];
            header('Location: /');
            exit;
        }

        $target_user = $userModel->fetchAllUserDataByUsername($username);

        if (!$target_user) {
            $_SESSION['notification'] = ['error', 'User not found!'];
            header('Location: /admin/users');
            exit;
        }

        echo $this->twig->render('core/admin/edit_user.twig', [
            'tool_name' => 'users',
            'tool_title' => 'Edit User: ' . $target_user['username'],
            'tool_desc' => 'Manage user account details, moderation status, and permissions',
            'user' => $target_user,
            'followers_count' => count($userModel->fetchFollowers($target_user['id'], 1000, 0) ?? []),
            'post_count' => count($postModel->getPostsByUserId($target_user['id'], 1000, 0) ?? []),
            'current_admin_level' => $current_admin_level
        ]);
    }

    public function updateUser($username) {
        // this function is like mostly ai, i apologize i got lazy
        $userModel = new UserModel;
        $adminModel = new AdminModel;
        $authModel = new AuthModel;
        $helperService = new \App\Services\HelperService($this->twig);

        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in'];
            header('Location: /login');
            exit;
        }

        $current_admin_level = $userModel->fetchAdminStatus($_SESSION['user_id']);

        if ($current_admin_level < 2) {
            if ($current_admin_level == 1) {
                $_SESSION['notification'] = ['error', 'You do not have permission to edit users'];
                header("Location: /admin/users/edit/{$username}");
                exit;
            }

            $_SESSION['notification'] = ['error', 'Unauthorized'];
            header('Location: /');
            exit;
        }

        $target_user = $userModel->fetchAllUserDataByUsername($username);
        if (!$target_user) {
            $_SESSION['notification'] = ['error', 'User not found!'];
            header('Location: /admin/users');
            exit;
        }

        $displayName = trim($_POST['display_name'] ?? '') ?: null;
        $bio = trim($_POST['bio'] ?? '') ?: null;
        $location = trim($_POST['location'] ?? '') ?: null;
        $lastfm = trim($_POST['lastfm_username'] ?? '') ?: null;
        $email = trim($_POST['email'] ?? '');
        $verified = isset($_POST['verified']) ? 1 : 0;
        $modNote = trim($_POST['mod_note'] ?? '');

        $current_username = $username; 
        $new_username = trim($_POST['new_username'] ?? '');

        if ($new_username !== $current_username) {
            if (empty($new_username) || strlen($new_username) < 3 || strlen($new_username) > 20) {
                $_SESSION['notification'] = ['error', 'Your username must be greater than 3 and less than 20 characters!'];
                header("Location: /admin/users/edit/{$current_username}");
                exit;
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
                $_SESSION['notification'] = ['error', 'Your username can only contain letters, numbers, and underscores!'];
                header("Location: /admin/users/edit/{$current_username}");  
                exit;
            } elseif ($authModel->usernameExists($new_username)) {
                $_SESSION['notification'] = ['error', 'That username is taken!'];
                header("Location: /admin/users/edit/{$current_username}");
                exit;
            } 
        }

        if (empty($email)) {
            $_SESSION['notification'] = ['error', 'Email address cannot be empty.'];
            header("Location: /admin/users/edit/{$username}");
            exit;
        }

        if ($current_admin_level >= 3) {
            $adminLevel = (int)($_POST['admin_level'] ?? $target_user['admin_level']);
        } else {
            $adminLevel = (int)$target_user['admin_level']; 
        }

        $suspended = isset($_POST['suspended']) ? 1 : 0;
        if ($target_user['admin_level'] > 0 && $current_admin_level < 3) {
            if ($suspended != $target_user['suspended']) {
                $_SESSION['notification'] = ['error', 'You cannot suspend another website moderator'];
                header("Location: /admin/users/edit/{$username}");
                exit;
            }
            $suspended = (int)$target_user['suspended'];
        }

        $avatar = $target_user['avatar'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== 4) {
            $uploadError = null;
            $newAvatar = $helperService->uploadImageFile('avatar', '/dynamic/avatars', 5000000, ['png', 'jpg', 'jpeg', 'gif', 'webp'], function($msg) use (&$uploadError) {
                $uploadError = $msg;
            });

            if ($uploadError) {
                $_SESSION['notification'] = ['error', $uploadError];
                header("Location: /admin/users/edit/{$username}");
                exit;
            }

            if ($newAvatar) {
                $avatar = $newAvatar;
            }
        }

        $banner = $target_user['banner'];
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] !== 4) {
            $uploadError = null;
            $newBanner = $helperService->uploadImageFile('banner', '/dynamic/banners', 10000000, ['png', 'jpg', 'jpeg', 'gif', 'webp'], function($msg) use (&$uploadError) {
                $uploadError = $msg;
            });

            if ($uploadError) {
                $_SESSION['notification'] = ['error', $uploadError];
                header("Location: /admin/users/edit/{$username}");
                exit;
            }

            if ($newBanner) {
                $banner = $newBanner;
            }
        }

        if (!empty($new_username)) {
            $username = $new_username;
        }

        $updated = $adminModel->updateUserFull(
            $target_user['id'],
            $displayName,
            $bio,
            $location,
            $lastfm,
            $email,
            $avatar,
            $banner,
            $adminLevel,
            $verified,
            $suspended,
            $modNote,
            $username
        );

        if ($updated) {
            $_SESSION['notification'] = ['success', "User '{$username}' updated successfully!"];
        } else {
            $_SESSION['notification'] = ['error', 'Failed to update user.'];
        }

        if($new_username !== $current_username) {
            header("Location: /admin/users/edit/{$new_username}");
        } else {
            header("Location: /admin/users/edit/{$username}");
        }

        exit;
    }

    public function contact($page = 1) {
        $userModel = new UserModel;
        $adminModel = new AdminModel;

        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in'];
            header('Location: /login');
            exit;
        }

        if ($userModel->fetchAdminStatus($_SESSION['user_id']) <= 0) {
            $_SESSION['notification'] = ['error', 'Unauthorized'];
            header('Location: /');
            exit;
        }

        $page = max(1, (int)$page);

        echo $this->twig->render('core/admin/contact.twig', [
            'tool_name' => 'contact',
            'tool_title' => 'Contact',
            'tool_desc' => 'View messages submitted through the contact page',
            'messages' => $adminModel->fetchContactMessages(15, ($page - 1) * 15),
            'page' => $page
        ]);
    }

    public function userIpHistory($username) {
        $userModel = new UserModel;
        $adminModel = new AdminModel;

        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in'];
            header('Location: /login');
            exit;
        }

        $current_admin_level = $userModel->fetchAdminStatus($_SESSION['user_id']);

        if ($current_admin_level < 2) {
            $_SESSION['notification'] = ['error', 'You do not have access to this tool'];
            header('Location: /admin');
            exit;
        }

        $target_user = $userModel->fetchAllUserDataByUsername($username);
        if (!$target_user) {
            $_SESSION['notification'] = ['error', 'User not found!'];
            header('Location: /admin');
            exit;
        }

        $adminModel->insertIntoAudit('Viewed the IPs of user ID '. $target_user['id']);

        $ips = $adminModel->fetchUserIps($target_user['id']);

        foreach ($ips as &$ip_record) {
            if (in_array($ip_record['address'], ['127.0.0.1', '::1'])) {
                $ip_record['location'] = 'Localhost';
                $ip_record['isp'] = 'Internal Network';
                $ip_record['is_vpn'] = false;
                continue;
            }

            $response = @file_get_contents("https://ipwho.is/{$ip_record['address']}");
            $ip_data = json_decode($response, true);

            if ($ip_data && isset($ip_data['success']) && $ip_data['success'] === true) {
                $city = $ip_data['city'] ?? 'Unknown';
                $country = $ip_data['country'] ?? 'Unknown';
                $ip_record['location'] = $city . ', ' . $country;
                $ip_record['isp'] = $ip_data['connection']['isp'] ?? 'Unknown';
                
                $is_vpn = !empty($ip_data['security']['vpn']);
                $is_proxy = !empty($ip_data['security']['proxy']);
                $is_hosting = !empty($ip_data['security']['hosting']);

                $ip_record['is_vpn'] = ($is_vpn || $is_proxy || $is_hosting);
            } else {
                $ip_record['location'] = 'Unknown';
                $ip_record['isp'] = 'Unknown';
                $ip_record['is_vpn'] = false;
            }
        }
        unset($ip_record);

        echo $this->twig->render('core/admin/ip.twig', [
            'tool_name' => 'users',
            'tool_title' => 'IPs for: ' . $target_user['username'],
            'tool_desc' => 'A list of IP addresses for this user',
            'user' => $target_user,
            'ips' => $ips
        ]);
    }

    public function news() {
        $userModel = new UserModel;
        $newsModel = new NewsModel;

        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in'];
            header('Location: /login');
            exit;
        }

        $current_admin_level = $userModel->fetchAdminStatus($_SESSION['user_id']);
        if ($current_admin_level <= 0) {
            $_SESSION['notification'] = ['error', 'Unauthorized'];
            header('Location: /');
            exit;
        }

        echo $this->twig->render('core/admin/news.twig', [
            'tool_name' => 'news',
            'tool_title' => 'News',
            'tool_desc' => 'Publish and delete site news',
            'news' => $newsModel->getNews(),
            'current_admin_level' => $current_admin_level
        ]);
    }

    public function createNews() {
        $userModel = new UserModel;
        $newsModel = new NewsModel;

        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in'];
            header('Location: /login');
            exit;
        }

        $current_admin_level = $userModel->fetchAdminStatus($_SESSION['user_id']);
        if ($current_admin_level < 2) {
            $_SESSION['notification'] = ['error', 'You do not have access to this tool'];
            header('Location: /admin/news');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if (empty($title) || empty($content)) {
            $_SESSION['notification'] = ['error', 'Both title and content are required.'];
            header('Location: /admin/news');
            exit;
        }

        $author = $userModel->fetchUsername($_SESSION['user_id']);

        if ($newsModel->createNews($title, $content, $author)) {
            $_SESSION['notification'] = ['success', 'News published successfully!'];
        } else {
            $_SESSION['notification'] = ['error', 'Failed to publish news'];
        }

        header('Location: /admin/news');
        exit;
    }

    public function deleteNews($id) {
        $userModel = new UserModel;
        $newsModel = new NewsModel;

        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in'];
            header('Location: /login');
            exit;
        }

        $current_admin_level = $userModel->fetchAdminStatus($_SESSION['user_id']);
        if ($current_admin_level < 2) {
            $_SESSION['notification'] = ['error', 'You do not have access to this tool'];
            header('Location: /admin/news');
            exit;
        }

        if ($newsModel->deleteNews($id)) {
            $_SESSION['notification'] = ['success', 'News deleted'];
        } else {
            $_SESSION['notification'] = ['error', 'Failed to delete news'];
        }

        header('Location: /admin/news');
        exit;
    }

}