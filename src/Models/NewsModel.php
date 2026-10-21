<?php

namespace App\Models;

class NewsModel extends BaseModel {
    public function getNews() {
        $stmt = $this->db->prepare('SELECT * FROM news ORDER BY post_date DESC');
        if($stmt->execute()) {
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return false;
        }
    }

    public function getLatestNews() {
        $stmt = $this->db->prepare('SELECT * FROM news ORDER BY post_date DESC LIMIT 1');
        if($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row;
        } else {
            return false;
        }
    }
}