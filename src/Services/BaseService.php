<?php

namespace App\Services;

use App\Models\Database;
use Twig\Environment;

class BaseService {
    protected $db;
    protected $twig;

    public function __construct(Environment $twig) {
        $this->db = Database::getConnection();
        $this->twig = $twig;
    }
}
