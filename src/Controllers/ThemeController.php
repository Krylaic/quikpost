<?php
namespace App\Controllers;

use Twig\Environment;
use Wikimedia\CSS\Parser\Parser;
use Wikimedia\CSS\Sanitizer\StylesheetSanitizer;
use App\Models\ThemeModel;
use App\Models\UserModel;
use App\Services\HelperService;

class ThemeController {
    private $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function themes($page = 1) {
        $themeModel = new ThemeModel();
        $page = max(1, $page);

        echo $this->twig->render('core/themes/themes.twig', [
            'themes' => $themeModel->getThemes(15, ($page - 1) * 15),
            'page' => $page,
            'active_page' => 'themes'
        ]);
    }

    public function view($id) {
        $themeModel = new ThemeModel();
        $userModel = new UserModel();

        $theme = $themeModel->getTheme($id);
        if (!$theme) {
            $_SESSION['notification'] = ['error', 'Theme not found!'];
            header('Location: /themes');
            exit;
        }

        $author = $userModel->fetchAllUserData($theme['author']);

        echo $this->twig->render('core/themes/view.twig', [
            'theme' => $theme,
            'author' => $author,
            'active_page' => 'themes'
        ]);
    }

    public function createForm() {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        echo $this->twig->render('core/themes/create.twig', [
            'active_page' => 'themes'
        ]);
    }

    public function create() {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;
        $css = trim($_POST['css'] ?? '');

        if (empty($title) || strlen($title) > 100) {
            $_SESSION['notification'] = ['error', 'Title must be between 1 and 100 characters!'];
            header('Location: /themes/create');
            exit;
        }

        if (strlen($description ?? '') > 1000) {
            $_SESSION['notification'] = ['error', 'Description must be 1000 characters or less!'];
            header('Location: /themes/create');
            exit;
        }

        if (empty($css)) {
            $_SESSION['notification'] = ['error', 'CSS cannot be empty!'];
            header('Location: /themes/create');
            exit;
        }

        $image = '/assets/img/default.png';
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== 4) {
            $helperService = new HelperService($this->twig);
            $upload_error = null;

            $uploaded = $helperService->uploadImageFile('image', '/dynamic/themes', 5000000, ['png', 'jpg', 'jpeg', 'gif', 'webp'], function($message) use (&$upload_error) {
                $upload_error = $message;
            });

            if ($upload_error) {
                $_SESSION['notification'] = ['error', $upload_error];
                header('Location: /themes/create');
                exit;
            }
            $image = $uploaded;
        }

        $cleanCss = $this->sanitizeCss($css);

        $themeModel = new ThemeModel();
        $result = $themeModel->createTheme($cleanCss, $title, $description, $image, date('Y-m-d H:i:s'), $_SESSION['user_id']);

        if (!$result['success']) {
            $_SESSION['notification'] = ['error', $result['error'] ?? 'Failed to publish theme!'];
            header('Location: /themes/create');
            exit;
        }

        $_SESSION['notification'] = ['success', 'Theme published!'];
        header('Location: /themes');
        exit;
    }

    public function editForm($id) {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        $themeModel = new ThemeModel();
        $userModel = new UserModel();
        $theme = $themeModel->getTheme($id);

        if (!$theme || ($theme['author'] != $_SESSION['user_id'] && $userModel->fetchAdminStatus($_SESSION['user_id']) <= 0)) {
            $_SESSION['notification'] = ['error', 'Unauthorized!'];
            header('Location: /themes');
            exit;
        }

        echo $this->twig->render('core/themes/edit.twig', [
            'theme' => $theme,
            'active_page' => 'themes'
        ]);
    }

    public function edit($id) {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        $themeModel = new ThemeModel();
        $userModel = new UserModel();
        $theme = $themeModel->getTheme($id);

        if (!$theme || ($theme['author'] != $_SESSION['user_id'] && $userModel->fetchAdminStatus($_SESSION['user_id']) <= 0)) {
            $_SESSION['notification'] = ['error', 'Unauthorized!'];
            header('Location: /themes');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;
        $css = trim($_POST['css'] ?? '');

        if (empty($title) || strlen($title) > 100 || empty($css)) {
            $_SESSION['notification'] = ['error', 'Title and CSS are required!'];
            header('Location: /themes/edit/' . $id);
            exit;
        }

        $image = $theme['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== 4) {
            $helperService = new HelperService($this->twig);
            $upload_error = null;

            $uploaded = $helperService->uploadImageFile('image', '/dynamic/themes', 5000000, ['png', 'jpg', 'jpeg', 'gif', 'webp'], function($message) use (&$upload_error) {
                $upload_error = $message;
            });

            if ($upload_error) {
                $_SESSION['notification'] = ['error', $upload_error];
                header('Location: /themes/edit/' . $id);
                exit;
            }
            $image = $uploaded;
        }

        $cleanCss = $this->sanitizeCss($css);
        $result = $themeModel->updateTheme($cleanCss, $title, $description, $image, date('Y-m-d H:i:s'), $id);

        if (!$result['success']) {
            $_SESSION['notification'] = ['error', 'Failed to update theme!'];
            header('Location: /themes/edit/' . $id);
            exit;
        }

        $_SESSION['notification'] = ['success', 'Theme updated!'];
        header('Location: /themes/' . $id);
        exit;
    }

    public function delete($id) {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        $themeModel = new ThemeModel();
        $userModel = new UserModel();
        $theme = $themeModel->getTheme($id);

        if (!$theme || ($theme['author'] != $_SESSION['user_id'] && $userModel->fetchAdminStatus($_SESSION['user_id']) <= 0)) {
            $_SESSION['notification'] = ['error', 'Unauthorized!'];
            header('Location: /themes');
            exit;
        }

        $themeModel->deleteTheme($id);
        $_SESSION['notification'] = ['success', 'Theme deleted!'];
        header('Location: /themes');
        exit;
    }

    public function apply($id) {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        $themeModel = new ThemeModel();
        $theme = $themeModel->getTheme($id);

        if (!$theme) {
            $_SESSION['notification'] = ['error', 'Theme not found!'];
            header('Location: /themes');
            exit;
        }

        $themeModel->setTheme($_SESSION['user_id'], $id);
        $_SESSION['notification'] = ['success', 'Theme applied!'];
        header('Location: /themes/' . $id);
        exit;
    }

    public function remove() {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['notification'] = ['error', 'Please log in!'];
            header('Location: /login');
            exit;
        }

        $themeModel = new ThemeModel();
        $themeModel->unsetTheme($_SESSION['user_id']);
        $_SESSION['notification'] = ['success', 'Active theme removed!'];
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? '/themes');
        exit;
    }

    private function sanitizeCss(string $css): string {
        $parser = Parser::newFromString($css);
        $stylesheet = $parser->parseStylesheet();
        $sanitizer = StylesheetSanitizer::newDefault();
        $newCss = (string)$sanitizer->sanitize($stylesheet);

        $newCss = preg_replace('/\{\s*/', " {\n  ", $newCss);
        $newCss = preg_replace('/;\s*/', ";\n  ", $newCss);
        $newCss = preg_replace('/\s*\}\s*/', "\n}\n\n", $newCss);
        return trim($newCss);
    }
}