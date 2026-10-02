<?php
namespace App\Controllers;

use Twig\Environment;
use App\Models\PostModel;
use App\Models\OptionsModel;
use App\Models\UserModel;
use App\Models\AuthModel;
use App\Models\NewsModel;
use App\Services\HelperService;

class PostController {
    private $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function createPost() {
        $postModel = new PostModel();
        $helperService = new HelperService($this->twig);

        $content = trim($_POST['content']);
        $author = $_SESSION['user_id'];
        $reply_to = $_POST['reply_to'] ?? null;

        // make sure the user is authenticated
        if(empty($author)) {
            $_SESSION['notification'] = ['error', 'You need to be logged in to do that!'];
            header('Location: /login');
            exit;
        }

        // make sure the post isn't empty
        if(empty($content)) {
            $_SESSION['notification'] = ['error', 'Your post must contain something!'];
            header('Location: /');
            exit;
        }

        if(strlen($content) > 255) {
            $_SESSION['notification'] = ['error', 'Your post must be less than 255 characters!'];
            header('Location: /');
            exit;
        }

        $attachment = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== 4) {
            $upload_error = null;
            
            $attachment = $helperService->uploadImageFile(
                'attachment',                      
                '/dynamic/attachments',             
                10000000,                     
                ['png', 'jpg', 'jpeg', 'gif', 'webp'], 
                function($message) use (&$upload_error) {
                    $upload_error = $message;
                }
            );

            if ($upload_error) {
                $_SESSION['notification'] = ['error', $upload_error];
                header('Location: /');
                exit;
            }
        }

        // send the data into the post model
        $posted = $postModel->createPost($content, $author, $attachment, $reply_to);

        if($posted['success']) {
            $_SESSION['notification'] = ['success', 'Successfully posted!'];
            header('Location: /');
            exit;
        } else {
            $_SESSION['notification'] = ['error', $posted['error'] ?? 'Something went wrong'];
            header('Location: /');
            exit;
        }
        
    }   

    public function status($id, $page = 1) {
        $newsModel = new NewsModel();
        $postModel = new PostModel();
        $userModel = new UserModel();

        if(!$postModel->doesPostExist($id)){
            $_SESSION['notification'] = ['error', 'This post doesnt exist!'];
            header('Location: /');
            exit;
        }

        echo $this->twig->render('core/status.twig', [
            'news' => $newsModel->getLatestNews(),
            'post' => $postModel->getPostById($id),
            'replies' => $postModel->getPostReplies($id, 15, ($page - 1) * 15),
            'users' => $userModel->fetchRecentUsers(10, 0),
            'page' => $page
        ]);
    }

    public function reportForm($id) {
        if(empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        $postModel = new PostModel();
        echo $this->twig->render('core/report.twig', [
            'post' => $postModel->getPostById($id)
        ]);
    }

    public function report($id) {
        $postModel = new PostModel();
        $authModel = new AuthModel();

        $captcha = $_POST['captcha'] ?? '';
        $content = trim($_POST['content']) ?? '';

        if(empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        // captcha validation
        if (!$authModel->verifyCaptcha($captcha)) {
            $_SESSION['notification'] = ['error', 'Incorrect captcha'];
            header('Location: /report/' . $id);
            exit;
        }

        // content validation
        if(empty($content)) {
            $_SESSION['notification'] = ['error', 'Your report must contain a message!'];
            header('Location: /report/' . $id);
            exit;
        }

        $reported = $postModel->createReport($id, $content, $captcha);

        if($reported['success']) {
            $_SESSION['notification'] = ['success', 'Report successfully filed'];
            header('Location: /');
            exit;
        } else {
            $_SESSION['notification'] = ['error', $reported['error'] ?? 'Something went wrong'];
            header('Location: /');
            exit;
        }
    }

    public function like($id) {
        $postModel = new PostModel();

        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        $result = $postModel->likePost($id, $_SESSION['user_id']);

        if (isset($result['error'])) {
            $_SESSION['notification'] = ['error', $result['error']];
            header('Location: /');
            exit;
        }

        header('Location: /status/' . $id);
        exit;
    }

    public function delete($id) {
        $postModel = new PostModel();

        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        $result = $postModel->deletePost($id, $_SESSION['user_id']);

        if (isset($result['error'])) {
            $_SESSION['notification'] = ['error', $result['error']];
            header('Location: /');
            exit;
        }

        $_SESSION['notification'] = ['success', 'Post successfully deleted'];
        header('Location: /');
        exit;
    }

}