<?php
namespace App\Controllers;

use Twig\Environment;
use App\Models\PostModel;

class PostController {
    private $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function createPost() {
        $postModel = new PostModel();

        $content = trim($_POST['content']);
        $author = $_SESSION['user_id'];

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

        // send the data into the post model
        $posted = $postModel->createPost($content, $author);

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

}