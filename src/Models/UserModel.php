<?php

namespace App\Models;

class UserModel extends BaseModel {

    // functions to fetch data

    public function fetchAllUserData($user_id) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ?? false;
    }

    public function fetchAllUserDataByUsername($username) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ?? false;
    }

    public function fetchRecentUsers($limit, $offset) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE suspended = 0 ORDER BY join_date DESC LIMIT ? OFFSET ?');
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function fetchUsername($user_id) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['username'] ?? false;
    }

    public function fetchAvatar($user_id) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['avatar'] ?? false;
    }

    public function fetchAdminStatus($user_id) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['admin_level'] ?? 0;
    }

    public function fetchVerificationStatus($user_id) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['verified'] ?? false;
    }

    public function fetchBanStatus($user_id) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (bool)($row['suspended'] ?? false);
        } else {
            return true;
        }
        

    }

    public function fetchBanStatusByUsername($username) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (bool)($row['suspended'] ?? false);
        } else {
            return true;
        }
        
    }

    // functions to update data

    public function updateIp($user_id) {
        // this query was made by gemini, im not that good at sql yet
        $stmt = $this->db->prepare('INSERT INTO ip_addresses (address, user_id) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM ip_addresses WHERE address = ? AND user_id = ?)');
        $stmt->bind_param('ssss', $_SERVER['REMOTE_ADDR'], $user_id, $_SERVER['REMOTE_ADDR'], $user_id);
        return $stmt->execute();
    }

    public function fetchFollowing($user_id, $limit, $offset) {
        $stmt = $this->db->prepare('
            SELECT u.* FROM users u 
            JOIN followers f ON u.id = f.following 
            WHERE f.follower = ? 
            LIMIT ? OFFSET ?
        ');
        $stmt->bind_param('sii', $user_id, $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function fetchFollowers($user_id, $limit, $offset) {
        $stmt = $this->db->prepare('
            SELECT u.* FROM users u 
            JOIN followers f ON u.id = f.follower 
            WHERE f.following = ? 
            LIMIT ? OFFSET ?
        ');
        $stmt->bind_param('sii', $user_id, $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function isFollowing($follower, $target) {
        $stmt = $this->db->prepare('SELECT 1 FROM followers WHERE following = ? AND follower = ? LIMIT 1');
        $stmt->bind_param('ss', $target, $follower);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function followUser($target, $follower) {
        // this is copy pasted directly from quikpost pre-rewrite lmao

        if ($target === $follower) {
            return(["error" => "You cannot follow yourself."]);
        }

        $stmt = $this->db->prepare('SELECT * FROM followers WHERE following = ? AND follower = ? LIMIT 1');
        $stmt->bind_param('ss', $target, $follower);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows == 0) {
            $stmt = $this->db->prepare('INSERT INTO followers (following, follower) VALUES (?, ?)');
            $stmt->bind_param('ss', $target, $follower);
            $stmt->execute();
            $stmt->close();

            return(["status" => "following"]);
        } else {
            $stmt = $this->db->prepare('DELETE FROM followers WHERE following = ? AND follower = ?');
            $stmt->bind_param('ss', $target, $follower);
            $stmt->execute();
            $stmt->close();

            return(["status" => "follow"]);
        }
    }

}
