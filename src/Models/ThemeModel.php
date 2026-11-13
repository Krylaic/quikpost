<?php

namespace App\Models;

class ThemeModel extends BaseModel {
    public function createTheme($css, $title, $description, $image, $creation_date, $author) {
        $date = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare('INSERT INTO themes (css, title, description, image, creation_date, author) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssss', $css, $title, $description, $image, $date, $author);

        if($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Failed to create theme'];
        }
    }

    public function updateTheme($css, $title, $description, $image, $updated_date, $id) {
        $date = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare('UPDATE themes SET css = ?, title = ?, description = ?, image = ?, updated_date = ? WHERE id = ?');
        $stmt->bind_param('sssssi', $css, $title, $description, $image, $date, $id);

        if($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Failed to create theme'];
        }
    }


    public function deleteTheme($id) {
        $stmt = $this->db->prepare('UPDATE users SET active_theme = NULL WHERE active_theme = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $stmt = $this->db->prepare('DELETE FROM themes WHERE id = ?');
        $stmt->bind_param('i', $id);
        if($stmt->execute()){
            return true;
        }

        return  false;
    }

    public function getTheme($id) {
        $stmt = $this->db->prepare('SELECT * FROM themes WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();

    }

    public function getThemes($limit = 15, $offset = 0) {
        $stmt = $this->db->prepare("SELECT * FROM themes ORDER BY creation_date DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function setTheme($user_id, $theme_id) {
        $stmt = $this->db->prepare('UPDATE users SET active_theme = ? WHERE id = ?');
        $stmt->bind_param('ii', $theme_id, $user_id);
        if($stmt->execute()) {
            $stmt = $this->db->prepare('UPDATE themes SET installs = installs + 1 WHERE id = ?');
            $stmt->bind_param('i', $theme_id);
            if ($stmt->execute()) {
                return true;
            }
            return false;
        }
        return false;

    }

    public function unsetTheme($user_id) {
        $stmt = $this->db->prepare('UPDATE users SET active_theme = null WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        if($stmt->execute()) {
            return true;
        }
        return false;

    }
}