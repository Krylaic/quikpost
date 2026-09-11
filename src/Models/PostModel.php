<?php

namespace App\Models;

class PostModel extends BaseModel {

    // functions to fetch data

    public function fetchAllUserData($user_id) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$user_id]);
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ?? false;
    }

    // functions to update data

}