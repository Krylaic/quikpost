<?php
namespace App\Controllers;

use Twig\Environment;
use App\Models\OptionsModel;

class PageController {
    private $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function index() {
        $optionsModel = new OptionsModel();
        
        if(!empty($_SESSION['user_id'])) {
            echo $this->twig->render('core/home.twig', [
                'active_page' => "feed",
                'about' => $optionsModel->getAbout()
            ]);
        } else {
            echo $this->twig->render('core/landing.twig', [
                'active_page' => "feed",
                'about' => $optionsModel->getAbout()
            ]);
        }
    }
}