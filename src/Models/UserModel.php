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

    public function fetchVerificationStatus($user_id) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['verified'] ?? false;
    }


    // functions to update data

    public function updateIp($user_id) {
        // this query was made by gemini, im not that good at sql yet
        $stmt = $this->db->prepare('INSERT INTO ip_addresses (address, user_id) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM ip_addresses WHERE address = ? AND user_id = ?)');
        $stmt->bind_param('ssss', $_SERVER['REMOTE_ADDR'], $user_id, $_SERVER['REMOTE_ADDR'], $user_id);
        return $stmt->execute();
    }

    public function followUser($target, $follower) {
        // this is copy pasted directly from quikpost pre-rewrite lmao

        if ($target === $follower) {
            return(["error" => "You cannot follow yourself."]);
        }

        $stmt = $conn->prepare('SELECT * FROM followers WHERE following = ? AND follower = ? LIMIT 1');
        $stmt->bind_param('ss', $target, $follower);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows == 0) {
            $stmt = $conn->prepare('INSERT INTO followers (following, follower) VALUES (?, ?)');
            $stmt->bind_param('ss', $target, $follower);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare('UPDATE users SET followers = followers + 1 WHERE username = ?');
            $stmt->bind_param('s', $target);
            $stmt->execute();
            $stmt->close();

            return(["status" => "following"]);
        } else {
            $stmt = $conn->prepare('DELETE FROM followers WHERE following = ? AND follower = ?');
            $stmt->bind_param('ss', $target, $follower);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare('UPDATE users SET followers = GREATEST(followers - 1, 0) WHERE username = ?');
            $stmt->bind_param('s', $target);
            $stmt->execute();
            $stmt->close();

            return(["status" => "follow"]);
        }
    }

}
