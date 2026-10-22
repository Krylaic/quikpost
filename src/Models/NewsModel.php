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

    public function createNews($title, $content, $author) {
        $stmt = $this->db->prepare('INSERT INTO news (title, content, author, post_date) VALUES (?, ?, ?, ?)');
        $date = date('Y-m-d H:i:s');
        $stmt->bind_param('ssss', $title, $content, $author, $date);
        
        if ($stmt->execute()) {
            $action = "Added news:  {$title}";
            $admin_id = $_SESSION['user_id'] ?? 0;
            $stmt = $this->db->prepare("INSERT INTO audit (action, admin, date) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $action, $admin_id, $date);
            $stmt->execute();

            return true;
        }

        return false;
    }

    public function deleteNews($id) {
        $stmt = $this->db->prepare('SELECT * FROM news WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $news = $stmt->get_result()->fetch_assoc();

        $stmt = $this->db->prepare('DELETE FROM news WHERE id = ?');
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            $title = $news['title'] ?? "";
            $action = "Deleted news:  {$title}";
            $admin_id = $_SESSION['user_id'] ?? 0;
            $date = date('Y-m-d H:i:s');

            $stmt = $this->db->prepare("INSERT INTO audit (action, admin, date) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $action, $admin_id, $date);
            $stmt->execute();

            return true;
        }

        return false;
    }
}