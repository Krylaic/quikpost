<?php

namespace App\Models;

class AdminModel extends BaseModel {

    public function fetchAuditLog($limit, $offset) {
        $stmt = $this->db->prepare('
            SELECT audit.*, users.username AS admin 
            FROM audit 
            LEFT JOIN users ON audit.admin = users.id 
            ORDER BY audit.date DESC 
            LIMIT ? OFFSET ?
        ');
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function updateSite($site_name, $slogan, $domain, $ip_check, $about, $tos, $privacy_policy, $community_guidelines) {
        $current_user = $_SESSION['user_id'];

        $stmt = $this->db->prepare("UPDATE options SET name = ?, slogan = ?, domain = ?, about = ?, tos = ?, privacy_policy = ?, community_guidelines = ?, ip_check = ?");
        $stmt->bind_param('sssssssi', $site_name, $slogan, $domain, $about, $tos, $privacy_policy, $community_guidelines, $ip_check);
        
        if($stmt->execute()) {
            $stmt = $this->db->prepare("INSERT INTO audit (action, admin, date) VALUES (?, ?, ?)");
            $action = "Updated site configuration";
            $date = date('Y-m-d H:i:s');

            $stmt->bind_param('sss', $action, $current_user, $date);

            if($stmt->execute()){
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }
    }

    public function fetchReports($limit, $offset) {
        $stmt = $this->db->prepare('
            SELECT reports.*, users.username AS user_id 
            FROM reports 
            LEFT JOIN users ON reports.user_id = users.id 
            ORDER BY reports.report_date DESC 
            LIMIT ? OFFSET ?
        ');
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function fetchAllUsers($limit, $offset) {
        $stmt = $this->db->prepare('SELECT users.*, COUNT(posts.author) AS post_count FROM users LEFT JOIN posts ON users.id = posts.author GROUP BY users.id ORDER BY users.join_date DESC LIMIT ? OFFSET ?');
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function searchUsers($query, $limit, $offset) {
        $searchTerm = '%' . $query . '%';
        $stmt = $this->db->prepare('
            SELECT users.*, COUNT(posts.author) AS post_count 
            FROM users 
            LEFT JOIN posts ON users.id = posts.author 
            WHERE users.username LIKE ? OR users.email LIKE ? OR users.display_name LIKE ?
            GROUP BY users.id 
            ORDER BY users.join_date DESC 
            LIMIT ? OFFSET ?
        ');
        $stmt->bind_param('sssii', $searchTerm, $searchTerm, $searchTerm, $limit, $offset);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function dismissReport($id) {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = (SELECT post_id FROM reports WHERE id = ?)');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $post = $stmt->get_result()->fetch_assoc();

        $stmt = $this->db->prepare('DELETE FROM reports WHERE id = ?');
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            if ($post) {
                $action = "Dismissed a report on post ID " . $post['id'];
            } else {
                $action = "Dismissed a report on a deleted post";
            }

            $stmt = $this->db->prepare("INSERT INTO audit (action, admin, date) VALUES (?, ?, ?)");
            $date = date('Y-m-d H:i:s');

            $stmt->bind_param('sss', $action, $_SESSION['user_id'], $date);
            $stmt->execute();
            
            return true;
        } else {
            return false;
        }
    }


    public function updateUserFull($userId, $displayName, $bio, $location, $lastfm, $email, $avatar, $banner, $adminLevel, $verified, $suspended, $modNote = '') {
        $stmt = $this->db->prepare('
            UPDATE users SET display_name = ?, bio = ?, location = ?, lastfm_username = ?, email = ?, avatar = ?, banner = ?, admin_level = ?, verified = ?, suspended = ? WHERE id = ?');
        
        $stmt->bind_param(
            'sssssssiiii', 
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
            $userId
        );

        if ($stmt->execute()) {
            $action = "Updated user ID " . $userId . ($modNote ? ": " . $modNote : "");
            $admin = $_SESSION['user_id'] ?? 0;
            $date = date('Y-m-d H:i:s');

            $auditStmt = $this->db->prepare("INSERT INTO audit (action, admin, date) VALUES (?, ?, ?)");
            $auditStmt->bind_param('sss', $action, $admin, $date);
            $auditStmt->execute();

            return true;
        }

        return false;
    }

    public function fetchContactMessages($limit, $offset) {
        $stmt = $this->db->prepare('SELECT * FROM contact ORDER BY contact_date DESC, id DESC LIMIT ? OFFSET ?');
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function fetchUserIps($userId) {
        $stmt = $this->db->prepare('SELECT id, address FROM ip_addresses WHERE user_id = ? ORDER BY id DESC');
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

}
